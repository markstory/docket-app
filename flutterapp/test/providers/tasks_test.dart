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

Project parseProjectDetails(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('project')) {
    throw 'Cannot parse tasks without tasks key';
  }

  return Project.fromMap(decoded['project']);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late TasksProvider provider;

  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final tasksTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  file = File('test_resources/task_create_today.json');
  final taskCreateTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/subtask_update.json');
  final subtaskUpdateResponse = file.readAsStringSync();

  var db = LocalDatabase(inTest: true);

  Future<void> setTodayView(List<Task> tasks) async {
    var taskView = TaskViewData(tasks: tasks, calendarItems: []);
    await db.tasksDaily.set(taskView);
  }
  late CallCounter listener;

  group('$TasksProvider', () {
    setUp(() async {
      listener = CallCounter();
      provider = TasksProvider(db);
      await provider.clear();
      await db.apiToken.set(ApiToken.fake());
    });

    tearDown(() {
      db.tasksDaily.removeListener(listener);
    });

    test('toggleComplete() sends request and removes local data', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/complete'));
        return Response('', 204);
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      setTodayView(tasks);

      db.tasksDaily.addListener(listener);
      var provider = TasksProvider(db);

      await provider.toggleComplete(tasks[0]);

      expect(listener.callCount, greaterThan(0));
      expect(db.tasksDaily.isExpired, isTrue);
      var viewData = await db.tasksDaily.get(today);
      expect(viewData.tasks.length, equals(1));
    });

    test('toggleComplete() sends request to incomplete and expires data', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/incomplete'));
        return Response('', 204);
      });

      var task = Task.blank();
      task.id = 1;
      task.title = "fold the towels";
      task.projectId = 1;
      task.projectSlug = 'home';
      task.dueOn = today;
      task.completed = true;

      var provider = TasksProvider(db);
      await provider.toggleComplete(task);

      expect(db.tasksDaily.isExpired, isTrue);
      expect(db.upcoming.isExpired, isTrue);
      expect(db.completedTasks.isExpired, isTrue);
      expect(db.projectDetails.isExpiredSlug(task.projectSlug), isTrue);

      var todayData = await db.tasksDaily.get(today);
      expect(todayData.tasks.length, equals(1));
    });

    test('deleteTask() removes task and clears local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/delete'));
        return Response('', 204);
      });

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var provider = TasksProvider(db);
      db.tasksDaily.addListener(listener);

      await provider.deleteTask(tasks[0]);

      expect(listener.callCount, greaterThan(0));
      expect(db.tasksDaily.isExpired, isTrue);
    });

    test('deleteTask() reduces the local project incomplete task count', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/tasks/1/delete'));
        return Response('', 204);
      });

      var project = parseProjectDetails(projectDetailsResponseFixture);
      db.projectMap.set(project);

      var tasks = parseTaskList(tasksTodayResponseFixture);
      await setTodayView(tasks);

      var provider = TasksProvider(db);
      await provider.deleteTask(tasks[0]);
      var updated = await db.projectMap.get(project.slug);
      expect(updated?.incompleteTaskCount, lessThan(project.incompleteTaskCount));
    });

    test('updateTask() call API and updates today view', () async {
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

      var todayData = await db.tasksDaily.get(today);
      expect(todayData.tasks.length, equals(2));
    });

    test('toggleSubtask() call API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks/2/toggle'));

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

      var updated = await db.taskDetails.get(task.id!);
      expect(updated, isNotNull);
      expect(updated!.subtasks[0].completed, isTrue);
    });

    test('saveSubtask() call API and update local task', () async {
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

      await provider.saveSubtask(task, subtask);

      var updated = await db.taskDetails.get(task.id!);
      var updatedSubtask = updated!.subtasks[0];
      expect(updatedSubtask, isNotNull);
      expect(updatedSubtask.completed, isFalse);
      expect(updatedSubtask.title, equals('fold big towels'));
    });

    test('saveSubtask() uses create API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks'));

        return Response(subtaskUpdateResponse, 200);
      });

      var task = Task.blank();
      task.id = 1;
      task.projectId = 1;
      task.projectSlug = 'home';
      task.title = "fold the towels";
      var subtask = Subtask(title: 'replaced by server data');
      task.subtasks.add(subtask);

      await provider.saveSubtask(task, subtask);

      var updated = await db.taskDetails.get(task.id!);
      var updatedSubtask = updated!.subtasks[0];
      expect(updatedSubtask, isNotNull);
      expect(updatedSubtask.id, equals(1));
      expect(updatedSubtask.completed, isFalse);
      expect(updatedSubtask.title, equals('fold big towels'));
    });

    test('deleteSubtask() uses API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks/2/delete'));

        return Response(subtaskUpdateResponse, 200);
      });

      var task = Task.blank();
      task.id = 1;
      task.projectId = 1;
      task.projectSlug = 'home';
      task.title = "fold the towels";
      var subtask = Subtask(id: 2, title: 'get the towels');
      task.subtasks.add(subtask);

      await provider.deleteSubtask(task, subtask);

      var updated = await db.taskDetails.get(task.id!);
      expect(updated?.subtasks.length, equals(0));
    });

    test('moveSubtask() uses API and update local task', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/1/subtasks/2/move'));
        expect(request.body, contains('ranking'));

        return Response(subtaskUpdateResponse, 200);
      });

      var task = Task.blank();
      task.id = 1;
      task.projectId = 1;
      task.projectSlug = 'home';
      task.title = "fold the towels";
      var subtask = Subtask(id: 2, title: 'replaced by server data', ranking: 3);
      task.subtasks.add(subtask);

      await provider.moveSubtask(task, subtask);

      var updated = await db.taskDetails.get(task.id!);
      var updatedSubtask = updated!.subtasks[0];
      expect(updatedSubtask, isNotNull);
      expect(updatedSubtask.ranking, equals(3));
    });
  });
}
