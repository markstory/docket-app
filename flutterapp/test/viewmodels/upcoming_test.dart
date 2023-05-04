import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/viewmodels/upcoming.dart';

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

Project parseProjectDetails(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('project')) {
    throw 'Cannot parse tasks without tasks key';
  }

  return Project.fromMap(decoded['project']);
}

String extractTitle(Task task) {
  return task.title;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());
  var tomorrow = today.add(const Duration(days: 1));
  var file = File('test_resources/tasks_upcoming.json');
  final tasksResponseFixture = file.readAsStringSync()
      .replaceAll('__TOMORROW__', formatters.dateString(tomorrow))
      .replaceAll('__TODAY__', formatters.dateString(today));

  Future<void> setUpcomingView(LocalDatabase db, List<Task> tasks) async {
    var taskView = TaskViewData(tasks: tasks, calendarItems: []);
    await db.dailyTasks.set(taskView.groupByDay());
  }

  group('$UpcomingViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.dailyTasks.clearSilent();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = UpcomingViewModel(db);

      expect(viewmodel.taskLists.length, equals(0));
      expect(viewmodel.overdue, isNull);

      await viewmodel.loadData();
      expect(viewmodel.taskLists.length, equals(28));

      // Check today
      expect(viewmodel.taskLists[0].title, equals('Today'));
      expect(viewmodel.taskLists[0].showButton, isTrue);
    });

    test('loadData() reads local data', () async {
      actions.client = MockClient((request) async {
        throw "Unexpected request to ${request.url.path}";
      });
      var tasks = parseTaskList(tasksResponseFixture);
      await setUpcomingView(db, tasks);

      var viewmodel = UpcomingViewModel(db);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.taskLists.length, equals(28));
    });

    test('loadData() refresh from server when expired', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var tasks = parseTaskList(tasksResponseFixture);
      await setUpcomingView(db, tasks);
      db.dailyTasks.expire();

      var viewmodel = UpcomingViewModel(db);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.taskLists.length, equals(28));
    });

    test('loadData() refresh from server when there are stale days', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });
      var tasks = parseTaskList(tasksResponseFixture);
      await setUpcomingView(db, tasks);
      db.dailyTasks.expireDay(tomorrow);

      var viewmodel = UpcomingViewModel(db);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.taskLists.length, equals(28));
    });

    test('reorderTask() updates state', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          var payload = jsonDecode(request.body);
          expect(payload['due_on'], equals(formatters.dateString(tomorrow)));
          expect(payload['day_order'], equals(0));
          expect(payload['evening'], isFalse);

          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var viewmodel = UpcomingViewModel(db);
      await viewmodel.loadData();

      var initialOrder = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      await viewmodel.reorderTask(0, 0, 0, 1);

      var updated = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      expect(updated, isNot(equals(initialOrder)));
    });

    test(
      skip: 'need a fixture with a task in evening as you cannot drag into regions that do not exist',
      'reorderTask() moves tasks into evening', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          var payload = jsonDecode(request.body);
          expect(payload['day_order'], equals(0));
          expect(payload['evening'], isTrue);

          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var tasks = parseTaskList(tasksResponseFixture);
      await setUpcomingView(db, tasks);

      var viewmodel = UpcomingViewModel(db);
      await viewmodel.loadData();

      equals(viewmodel.taskLists[0].tasks.length, 1);
      equals(viewmodel.taskLists[1].tasks.length, 0);
      await viewmodel.reorderTask(0, 0, 0, 1);

      equals(viewmodel.taskLists[0].tasks.length, 0);
      equals(viewmodel.taskLists[1].tasks.length, 1);
    });

    test('reorderTask() moves tasks between days', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          var payload = jsonDecode(request.body);
          expect(payload['day_order'], equals(0));
          expect(payload['evening'], isFalse);

          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var tasks = parseTaskList(tasksResponseFixture);
      await setUpcomingView(db, tasks);

      var viewmodel = UpcomingViewModel(db);
      await viewmodel.loadData();

      var initialOrder = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      await viewmodel.reorderTask(0, 0, 0, 1);

      var updated = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      expect(updated, isNot(equals(initialOrder)));
  });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var counter = CallCounter();
      var viewmodel = UpcomingViewModel(db);
      viewmodel.addListener(counter);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.taskLists.length, equals(28));
      expect(counter.callCount, equals(1));
    });

    test('refreshTasks() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var counter = CallCounter();
      var viewmodel = UpcomingViewModel(db);
      viewmodel.addListener(counter);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.refreshTasks();
      expect(viewmodel.taskLists.length, equals(28));
      expect(counter.callCount, equals(1));
    });

    test('insertAt() can add tasks', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/upcoming') {
          return Response(tasksResponseFixture, 200);
        }
        if (request.url.path == '/tasks/99/move') {
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var fresh = Task.blank();
      fresh.id = 99;
      fresh.projectId = 1;
      fresh.projectSlug = 'home';
      fresh.title = 'something old';
      fresh.dueOn = DateTime.now().subtract(const Duration(days: 2));

      var tasks = parseTaskList(tasksResponseFixture);
      tasks.add(fresh);
      await setUpcomingView(db, tasks);

      var viewmodel = UpcomingViewModel(db);
      await viewmodel.loadData();
      await viewmodel.insertAt(fresh, 0, 0);
      // We can't assert the list state afterwards, as data is refreshed
      // from the server and I'm too lazy to do use more mocks.
    });
  });
}
