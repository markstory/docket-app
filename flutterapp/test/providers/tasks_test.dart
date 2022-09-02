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
import 'package:docket/providers/session.dart';
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
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final tasksTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  file = File('test_resources/task_create_today.json');
  final taskCreateTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/subtask_update.json');
  final subtaskUpdateResponse = file.readAsStringSync();

  Future<void> setTodayView(List<Task> tasks) async {
    var db = LocalDatabase();
    var taskView = TaskViewData(tasks: tasks, calendarItems: []);
    await db.today.set(taskView);
  }

  var db = LocalDatabase();

  group('$TasksProvider', () {
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      listenerCallCount = 0;
      provider = TasksProvider(db, session)
        ..addListener(() {
          listenerCallCount += 1;
        });
      await provider.clear();
    });

    test('getToday() and fetchToday() work together', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/today'));

        return Response(tasksTodayResponseFixture, 200);
      });

      await provider.fetchToday();
      var taskData = await provider.getToday();

      expect(taskData.pending, equals(false));
      expect(taskData.tasks.length, equals(2));
      expect(taskData.tasks[0].title, equals('clean dishes'));
      expect(taskData.calendarItems.length, equals(1));
      expect(taskData.calendarItems[0].title, equals('Get haircut'));
      expect(listenerCallCount, greaterThanOrEqualTo(1));
    });

    test('fetchToday() handles server errors', () async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["bad things"]}', 400);
      });

      try {
        await provider.fetchToday();
      } on actions.ValidationError catch (e) {
        expect(e.toString(), contains('Could not load'));
      }
    });

    test('getUpcoming() and fetchUpcoming() work together', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/upcoming'));

        return Response(tasksTodayResponseFixture, 200);
      });

      await provider.fetchUpcoming();
      var taskData = await provider.getUpcoming();
      expect(taskData.tasks.length, equals(2));
      expect(taskData.tasks[0].title, equals('clean dishes'));
      expect(taskData.calendarItems.length, equals(1));
      expect(taskData.calendarItems[0].title, equals('Get haircut'));
      expect(listenerCallCount, greaterThanOrEqualTo(1));
    });

    test('toggleComplete() sends complete request', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/complete'));
        return Response('', 204);
      });

      var db = LocalDatabase();

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await db.addTasks(tasks);
      var provider = TasksProvider(db, session);

      await provider.toggleComplete(tasks[0]);

      expect(listenerCallCount, greaterThan(0));

      var updated = await db.today.get();
      expect(updated.tasks.length, equals(0));
      expect(updated.calendarItems.length, equals(0));
    });

    test('toggleComplete() expires local data', () async {
      actions.client = MockClient((request) async {
        return Response('', 204);
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var provider = TasksProvider(db, session);
      await provider.toggleComplete(tasks[0]);

      // Data should be expired as task was from today.
      var updated = await db.today.get();
      expect(updated.tasks.length, equals(0));
    });

    test('deleteTask() removes task and clears local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/delete'));
        return Response('', 204);
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var provider = TasksProvider(db, session);
      await provider.deleteTask(tasks[0]);

      expect(listenerCallCount, greaterThan(0));

      var todayData = await db.today.get();
      expect(todayData.missingData, equals(true));
      expect(todayData.tasks.length, equals(0));
    });

    test('getById() reads tasks', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/view'));
        return Response(taskCreateTodayResponseFixture, 200);
      });

      var db = LocalDatabase();
      var provider = TasksProvider(db, session);

      await provider.fetchById(1);

      var task = await provider.getById(1);
      expect(task, isNotNull);
      expect(task!.id, equals(1));
    });

    test('fetchById() throws error on network failure', () async {
      actions.client = MockClient((request) async {
        return Response('error', 500);
      });

      try {
        await provider.fetchById(1);
        fail('Should not get here');
      } catch (err) {
        expect(err.toString(), contains('Could not load'));
      }
    });

    test('createTask() calls API, clears date views & project view', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/add'));

        return Response(taskCreateTodayResponseFixture, 200);
      });

      // Seed the today view
      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var task = Task.blank();
      // This data has to match the fixture file.
      task.title = "fold the towels";
      task.projectId = 1;
      task.dueOn = today;

      var created = await provider.createTask(task);
      expect(created.id, equals(1));
    });

    test('updateTask() call API, and clears today view', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/edit'));

        return Response(taskCreateTodayResponseFixture, 200);
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var task = Task.blank();
      // This data has to match the fixture file.
      task.id = 1;
      task.title = "fold the towels";
      task.projectId = 1;
      task.projectSlug = 'home';
      task.dueOn = today;

      var updated = await provider.updateTask(task);
      expect(updated.id, equals(1));
      expect(updated.title, equals('fold the towels'));

      var todayData = await db.today.get();
      expect(todayData.tasks.length, equals(0));
    });

    test('toggleSubtask() call API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks/2/complete'));

        return Response('', 200);
      });

      var task = Task.blank();
      task.id = 1;
      task.projectId = 1;
      task.projectSlug = 'home';
      task.title = "fold the towels";
      var subtask = Subtask(id: 2, title: 'subtask');
      task.subtasks.add(subtask);

      await provider.toggleSubtask(task, subtask);

      // Should notify listeners.
      expect(listenerCallCount, greaterThan(1));
      var updated = await provider.getById(task.id!);
      expect(updated, isNotNull);
      expect(updated!.subtasks[0].completed, isTrue);
    });

    test('updateSubtask() call API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks/1/edit'));

        return Response(subtaskUpdateResponse, 200);
      });

      var task = Task.blank();
      task.id = 1;
      task.projectId = 1;
      task.projectSlug = 'home';
      task.title = "fold the towels";
      var subtask = Subtask(id: 1, title: 'replaced by server data');
      task.subtasks.add(subtask);

      await provider.updateSubtask(task, subtask);

      // Should notify listeners.
      expect(listenerCallCount, greaterThan(1));
      var updated = await provider.getById(task.id!);

      var updatedSubtask = updated!.subtasks[0];
      expect(updatedSubtask, isNotNull);
      expect(updatedSubtask.completed, isFalse);
      expect(updatedSubtask.title, equals('replaced by server data'));
    });
  });
}
