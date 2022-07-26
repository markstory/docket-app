import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';


// Parse a list response into a list of tasks.
List<Task> parseTaskList(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('tasks')) {
    throw 'Cannot parse tasks without tasks key';
  }
  List<Task> tasks = [];
  for (var item in decoded['tasks']) {
    tasks.add(Task.fromMap(item));
  }
  return tasks;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TasksProvider provider;
  int listenerCallCount = 0;
  const apiToken = 'api-token';
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final tasksTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  file = File('test_resources/task_create_today.json');
  final taskCreateTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

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
      await provider.fetchToday(apiToken);

      var taskData = await provider.getToday();
      expect(taskData.tasks.length, equals(2));
      expect(taskData.tasks[0].title, equals('clean dishes'));
      expect(taskData.calendarItems.length, equals(1));
      expect(taskData.calendarItems[0].title, equals('Get haircut'));
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

      await provider.fetchUpcoming(apiToken);

      var taskData = await provider.getUpcoming();
      expect(taskData.tasks.length, equals(2));
      expect(taskData.tasks[0].title, equals('clean dishes'));
      expect(taskData.calendarItems.length, equals(1));
      expect(taskData.calendarItems[0].title, equals('Get haircut'));
    });

    test('toggleComplete() sends complete request', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/complete'));
        return Response('', 204);
      });

      var db = LocalDatabase();

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await db.addTasks(tasks);
      var provider = TasksProvider(db);

      await provider.toggleComplete(apiToken, tasks[0]);

      expect(listenerCallCount, greaterThan(0));

      var updated = await db.fetchTodayTasks(useStale: true);
      expect(updated.length, equals(1));
      expect(updated[0].completed, equals(false));

      // Data should not be expired as the task wasn't on today.
      var withExpired = await db.fetchTodayTasks(useStale: false);
      expect(withExpired.length, equals(1));
    });

    test('toggleComplete() expires local data', () async {
      actions.client = MockClient((request) async {
        return Response('', 204);
      });

      var db = LocalDatabase();
      var tasks = parseTaskList(tasksTodayResponseFixture);
      await db.addTasks(tasks);
      var provider = TasksProvider(db);

      await provider.toggleComplete(apiToken, tasks[0]);

      var updated = await db.fetchTodayTasks(useStale: true);
      expect(updated.length, equals(1));

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
      var tasks = parseTaskList(tasksTodayResponseFixture);
      await db.addTasks(tasks);
      var provider = TasksProvider(db);

      var task = tasks[0];
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

      var db = LocalDatabase();
      var tasks = parseTaskList(tasksTodayResponseFixture);
      await db.addTasks(tasks);
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

    test('createTask() calls API, updates today view & project view', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/add'));

        return Response(taskCreateTodayResponseFixture, 200);
      });

      var task = Task.blank();
      task.title = "clean dishes";
      task.projectId = 1;
      task.dueOn = today;

      var created = await provider.createTask(apiToken, task);
      expect(created.id, equals(1));

      var todayData = await provider.getToday();
      expect(todayData.tasks.length, equals(1));
      expect(todayData.tasks[0].title, equals(task.title));

      var projectTasks = await provider.projectTasks('home');
      expect(projectTasks.length, equals(1));
      expect(projectTasks[0].title, equals(task.title));
    });

    test('createTask() update API, and upcoming view', () async {
    });

    test('createTask() update API, and project view', () async {
    });

    test('updateTask() update API, and today view', () async {
    });

    test('updateTask() update API, and project view', () async {
    });
  });
}
