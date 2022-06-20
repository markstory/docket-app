import 'dart:convert';
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

  String tasksTodayResponseFixture = """
{
  "tasks": [
    {
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
    {
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
  ]
}
""";

  group('$TasksProvider', () {
    setUp(() async {
      var db = LocalDatabase();
      listenerCallCount = 0;
      provider = TasksProvider(db)
          ..addListener(() {
            listenerCallCount += 1;
          });
      await provider.clear();
    });

    test('refreshTodayTasks() fetches from server', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/today'));
        return Response(tasksTodayResponseFixture, 200);
      });

      await provider.refreshTodayTasks(apiToken);
      expect(listenerCallCount, greaterThan(0));
    });

    test('refreshTodayTasks() handles error on server error', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/today'));
        return Response('{"errors": ["bad things"]}', 400);
      });

      try {
        await provider.refreshTodayTasks(apiToken);
        fail('Should throw');
      } catch (exc) {
        expect(exc.toString(), contains('Could not load tasks'));
      }
    });

    test('todayTasks() loads from local db', () async {
      actions.client = MockClient((request) async {
        throw Exception('Should not use network');
      });

      var db = LocalDatabase();
      await db.set(LocalDatabase.taskMapKey, json.decode(taskMapFixture));
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var tasks = await provider.todayTasks(apiToken);
      expect(tasks.length, equals(2));
      expect(tasks[0].title, equals('clean dishes'));
    });

    test('todayTasks() fetches from server', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/today'));
        return Response(tasksTodayResponseFixture, 200);
      });

      var tasks = await provider.todayTasks(apiToken);
      expect(listenerCallCount, greaterThan(0));
      expect(tasks.length, equals(2));
      expect(tasks[0], isA<Task>());
      expect(tasks[0].title, equals('clean dishes'));
      expect(tasks[1].title, equals('cut grass'));
    });

    test('todayTasks() handles server errors', () async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["bad things"]}', 400);
      });

      var tasks = await provider.todayTasks(apiToken);
      expect(tasks.length, equals(0));
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
      var withExpired = await db.fetchTodayTasks(useStale: false);
      expect(withExpired.length, equals(0));
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
  });
}
