import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TasksProvider provider;
  int listenerCallCount = 0;
  String apiToken = 'api-token';
  String todayTasksFixture = """
{"tasks": [1, 2]}
""";

  String taskMapFixture = """
{
  "1": {
    "id": 1,
    "project": {"slug": "home", "name": "home", "color": 1}, 
    "title": "clean dishes",
    "body": "",
    "evening": false,
    "completed": false,
    "due_on": null,
    "child_order": 0,
    "day_order": 0
  },
  "2": {
    "id": 2,
    "project": {"slug": "home", "name": "home", "color": 1}, 
    "title": "cut grass",
    "body": "",
    "evening": false,
    "completed": false,
    "due_on": null,
    "child_order": 1,
    "day_order": 1
  }
}
  """;

  var file = File('test_resources/tasks_today.json');
  final tasksTodayResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  group('$TasksProvider', () {
    var db = LocalDatabase();

    setUp(() async {
      db = LocalDatabase();
      listenerCallCount = 0;
      provider = TasksProvider(db)
          ..addListener(() {
            listenerCallCount += 1;
          });
      await provider.clear();
    });

    tearDown(() {
      db.close();
    });

    test('getToday() and fetchToday() work together', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/today'));

        return Response(tasksTodayResponseFixture, 200);
      });
      try {
        await provider.getToday();
        fail('Should raise on no data.');
      } on StaleDataError catch (_) {
        expect(true, equals(true));
      }

      var result = await provider.fetchToday(apiToken);
      expect(result.length, equals(2));

      var tasks = await provider.getToday();
      expect(tasks.length, equals(2));
      expect(tasks[0].title, equals('clean dishes'));
    });

    test('fetchToday() handles server errors', () async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["bad things"]}', 400);
      });

      try {
        await provider.fetchToday(apiToken);
      } on actions.ValidationError catch (e) {
        expect(e.toString(), contains('Could not load'));
      }
    });

    test('getUpcoming() and fetchUpcoming() work together', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/upcoming'));

        return Response(tasksTodayResponseFixture, 200);
      });
      try {
        await provider.getUpcoming();
        fail('Should raise on no data.');
      } on StaleDataError catch (_) {
        expect(true, equals(true));
      }

      var result = await provider.fetchUpcoming(apiToken);
      expect(result.length, equals(2));

      var tasks = await provider.getUpcoming();
      expect(tasks.length, equals(2));
      expect(tasks[0].title, equals('clean dishes'));
    });

    test('toggleComplete() sends complete request', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/complete'));
        return Response('', 204);
      });

      var taskData = json.decode(taskMapFixture);
      var db = LocalDatabase();
      await db.set(LocalDatabase.taskMapKey, taskData);
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var task = Task.fromMap(taskData['1']);
      await provider.toggleComplete(apiToken, task);

      expect(listenerCallCount, greaterThan(0));

      var updated = await db.fetchTodayTasks(useStale: true);
      expect(updated.length, equals(2));
      expect(updated[0].completed, equals(true));

      // Data should not be expired as the task wasn't on today.
      var withExpired = await db.fetchTodayTasks(useStale: false);
      expect(withExpired.length, equals(2));
    });

    test('toggleComplete() expires local data', () async {
      actions.client = MockClient((request) async {
        return Response('', 204);
      });

      var taskData = json.decode(taskMapFixture);
      // Make the task due today.
      taskData['1']['due_on'] = DateTime.now().toIso8601String();

      var db = LocalDatabase();
      await db.set(LocalDatabase.taskMapKey, taskData);
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var task = Task.fromMap(taskData['1']);
      await provider.toggleComplete(apiToken, task);

      var updated = await db.fetchTodayTasks(useStale: true);
      expect(updated.length, equals(2));

      // Data should be expired as task was due today
      try {
        await db.fetchTodayTasks(useStale: false);
      } on StaleDataError catch (_) {
        expect(true, equals(true));
      }
    });

    test('deleteTask() removes task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/delete'));
        return Response('', 204);
      });

      var db = LocalDatabase();
      var taskData = json.decode(taskMapFixture);
      await db.set(LocalDatabase.taskMapKey, taskData);
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var task = Task.fromMap(taskData['1']);
      await provider.deleteTask(apiToken, task);

      expect(listenerCallCount, greaterThan(0));
      var updated = await db.fetchTodayTasks(useStale: true);

      // The task should be removed locally
      expect(updated.length, equals(1));
      expect(updated[0].id, isNot(equals(task.id)));
    });

    test('getById() reads tasks', () async {
      actions.client = MockClient((request) async {
        throw Exception('No request should be sent.');
      });

      var taskData = json.decode(taskMapFixture);
      var db = LocalDatabase();
      await db.set(LocalDatabase.taskMapKey, taskData);
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var task = await provider.getById(apiToken, 1);
      expect(task.id, equals(1));
    });

    test('getById() throws error on network failure', () async {
      actions.client = MockClient((request) async {
        return Response('error', 500);
      });

      try {
        await provider.getById(apiToken, 1);
        fail('Should not get here');
      } catch (err) {
        expect(err.toString(), contains('Could not load'));
      }
    });

    test('projectTasks() fetch from the API and database', () async {
      int requestCounter = 0;

      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        requestCounter += 1;
        return Response(projectDetailsResponseFixture, 200);
      });

      await provider.fetchProjectTasks(apiToken, 'home');
      var tasks = await provider.projectTasks('home');

      expect(requestCounter, equals(1));
      expect(tasks.length, equals(2));
    });
  });
}
