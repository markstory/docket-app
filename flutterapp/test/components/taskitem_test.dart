import 'dart:io';
import 'dart:convert';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/components/taskitem.dart';
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/project_list.json');
  var projectResponse = file.readAsStringSync();

  file = File('test_resources/task_details.json');
  var taskDetails = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  var db = LocalDatabase(inTest: true);

  group("$TaskItem", () {
    var decoded = jsonDecode(projectResponse);
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    decoded = jsonDecode(taskDetails);
    var task = Task.fromMap(decoded['task']);

    setUp(() async {
      db.apiToken.set(ApiToken.fake());
      await db.projectMap.addMany(projects);
    });

    Future<void> renderWidget(WidgetTester tester, Task task) async {
      return tester.pumpWidget(EntryPoint(
          database: db,
          child: Scaffold(
            body: TaskItem(task: task),
          )
      ));
    }

    testWidgets('render showDate', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: Scaffold(
            body: TaskItem(task: task, showDate: true),
          )
      ));
      await tester.pumpAndSettle();

      expect(find.text('Today'), findsOneWidget);
      expect(find.byIcon(Icons.calendar_today), findsOneWidget);
    });

    testWidgets('render showProject', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: Scaffold(
            body: TaskItem(task: task, showProject: true),
          )
      ));
      await tester.pumpAndSettle();

      expect(find.text('home'), findsOneWidget);
    });

    testWidgets('render showRestore', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: Scaffold(
            body: TaskItem(task: task, showRestore: true),
          )
      ));
      await tester.pumpAndSettle();

      expect(find.text('Restore'), findsOneWidget);
      expect(find.byKey(const ValueKey('task-actions')), findsNothing);
    });

    testWidgets('render text information', (tester) async {
      await renderWidget(tester, task);
      await tester.pumpAndSettle();

      expect(find.text('clean the house'), findsOneWidget);
      expect(find.byIcon(Icons.done), findsOneWidget);
      expect(find.text('1/2'), findsOneWidget);
      expect(find.byKey(const ValueKey('task-actions')), findsOneWidget);
      expect(find.byType(Checkbox), findsOneWidget);
    });

    testWidgets('delete action confirms and sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/delete') {
          callCount += 1;
          return Response('', 200);
        }
        throw Exception("Request received for ${request.url.path} but no mock was set");
      });
      await renderWidget(tester, task);

      // open task action menu
      await tester.tap(find.byKey(const ValueKey('task-actions')));
      await tester.pumpAndSettle();

      // click option
      await tester.tap(find.text('Delete'));
      await tester.pumpAndSettle();

      // Accept confirm
      expect(find.text('Are you sure?'), findsOneWidget);
      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    testWidgets('change project action shows dialog and sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/edit') {
          callCount += 1;
          expect(request.body.contains('project_id":2'), isTrue);
          return Response(taskDetails, 200);
        }
        throw Exception("Request received for ${request.url.path} but no mock was set");
      });
      await renderWidget(tester, task);

      // open task action menu
      await tester.tap(find.byKey(const ValueKey('task-actions')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Change Project'));
      await tester.pumpAndSettle();

      // Change the project.
      await tester.tap(find.text('Work'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    testWidgets('reschedule action shows dialog and sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/edit') {
          callCount += 1;
          expect(request.body.contains('evening":true'), isTrue);
          return Response(taskDetails, 200);
        }
        throw Exception("Request received for ${request.url.path} but no mock was set");
      });
      await renderWidget(tester, task);

      // open task action menu
      await tester.tap(find.byKey(const ValueKey('task-actions')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Schedule'));
      await tester.pumpAndSettle();

      // Change the due date.
      await tester.tap(find.text('This evening'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
