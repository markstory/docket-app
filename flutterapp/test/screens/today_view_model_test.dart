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
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/screens/today_view_model.dart';

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

  late TasksProvider provider;

  int listenerCallCount = 0;
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final tasksTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_list.json');
  final projectListResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  file = File('test_resources/task_create_today.json');
  final taskCreateTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/subtask_update.json');
  final subtaskUpdateResponse = file.readAsStringSync();

  Future<void> setTodayView(LocalDatabase db, List<Task> tasks) async {
    var taskView = TaskViewData(tasks: tasks, calendarItems: []);
    await db.today.set(taskView);
  }

  group('$TodayViewModel', () {
    var db = LocalDatabase();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.today.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/today') {
          return Response(tasksTodayResponseFixture, 200);
        }
        if (request.url.path == '/projects') {
          return Response(projectListResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TodayViewModel(db, session);

      expect(viewmodel.taskLists.length, equals(0));
      expect(viewmodel.overdue, isNull);

      await viewmodel.loadData();
      expect(viewmodel.taskLists.length, equals(2));

      // Check today
      expect(viewmodel.taskLists[0].title, isNull);
      expect(viewmodel.taskLists[0].showButton, isNull);

      // Check evening
      expect(viewmodel.taskLists[1].title, equals('This Evening'));
      expect(viewmodel.taskLists[1].showButton, isTrue);
    });

    test('reorderTask() updates state', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/today') {
          return Response(tasksTodayResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      setTodayView(db, tasks);

      var viewmodel = TodayViewModel(db, session);
      await viewmodel.loadData();

      var initialOrder = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      await viewmodel.reorderTask(0, 0, 1, 0);

      var updated = viewmodel.taskLists[0].tasks.map(extractTitle).toList();
      expect(updated, isNot(equals(initialOrder)));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/today') {
          return Response(tasksTodayResponseFixture, 200);
        }
        if (request.url.path == '/projects') {
          return Response(projectListResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TodayViewModel(db, session);
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.taskLists[0].tasks.length, equals(2));
    });

    test('moveOverdue() can add tasks', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/99/move') {
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var overdue = Task.blank();
      overdue.id = 99;
      overdue.projectId = 1;
      overdue.projectSlug = 'home';
      overdue.title = 'something old';
      overdue.dueOn = DateTime.now().subtract(const Duration(days: 2));

      var tasks = parseTaskList(tasksTodayResponseFixture);
      tasks.add(overdue);
      setTodayView(db, tasks);

      var viewmodel = TodayViewModel(db, session);
      await viewmodel.loadData();

      await viewmodel.moveOverdue(overdue, 0, 0);
      expect(viewmodel.taskLists[0].tasks[0].title, equals('something old'));
    });
  });
}
