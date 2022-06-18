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

    test('refreshTodayTasks fetches from server', () async {
      actions.client = MockClient((request) async {
        return Response(todayTasksFixture, 200);
      });

      await provider.refreshTodayTasks(apiToken);
      expect(listenerCallCount, greaterThan(0));
    });

    test('refreshTodayTasks handles error on server error', () async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["bad things"]}', 400);
      });

      try {
        await provider.refreshTodayTasks(apiToken);
        fail('Should throw');
      } catch (exc) {
        expect(exc.toString(), contains('Could not load tasks'));
      }
    });

    test('todayTasks loads from local db', () async {
      actions.client = MockClient((request) async {
        throw Exception('Should not use network');
      });

      var db = LocalDatabase();
      await db.set(LocalDatabase.todayTasksKey, json.decode(todayTasksFixture));
      var provider = TasksProvider(db);

      var tasks = await provider.todayTasks(apiToken);
      expect(tasks.length, equals(1));
      expect(tasks[0].title, equals('clean dishes'));
    });

    test('todayTasks fetches from server', () async {
      actions.client = MockClient((request) async {
        return Response(todayTasksFixture, 200);
      });

      var tasks = await provider.todayTasks(apiToken);
      expect(listenerCallCount, greaterThan(0));
      expect(tasks.length, equals(1));
      expect(tasks[0], isA<Task>());
      expect(tasks[0].title, equals('clean dishes'));
    });

    test('todayTasks handles server errors', () async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["bad things"]}', 400);
      });

      var tasks = await provider.todayTasks(apiToken);
      expect(tasks.length, equals(0));
    });
  });
}
