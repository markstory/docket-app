import 'dart:io';
import 'dart:convert';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/components/subtaskitem.dart';
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/task.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/task_details.json');
  var taskDetails = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/subtask_update.json');
  var subtaskUpdate = file.readAsStringSync();

  var db = LocalDatabase(inTest: true);

  group("$SubtaskItem", () {
    var decoded = jsonDecode(taskDetails);
    var task = Task.fromMap(decoded['task']);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
    });

    Future<int> renderWidget(WidgetTester tester, Task task, Subtask subtask) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: Scaffold(
            body: SubtaskItem(task: task, subtask: subtask),
          )));

      return tester.pumpAndSettle();
    }

    testWidgets('complete checkbox sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        callCount = 1;
        if (request.url.path == '/tasks/1/subtasks/1/toggle') {
          return Response('', 200);
        }
        throw Exception('Request to ${request.url.path} has no response defined');
      });
      await renderWidget(tester, task, task.subtasks[0]);

      await tester.tap(find.byType(Checkbox));
      await tester.pumpAndSettle();

      expect(
          tester.getSemantics(find.byType(Checkbox)),
          matchesSemantics(
              hasTapAction: true, isEnabled: true, isFocusable: true, hasCheckedState: true, hasEnabledState: true));
      expect(callCount, 1);
    });

    testWidgets('edit mode sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        callCount = 1;
        if (request.url.path == '/tasks/1/subtasks/1/edit') {
          return Response(subtaskUpdate, 200);
        }
        throw Exception('Request to ${request.url.path} has no response defined');
      });
      await renderWidget(tester, task, task.subtasks[0]);

      // Tap the text to enter edit mode.
      await tester.tap(find.text('vacuum'));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('subtask-title')), 'new title');
      await tester.testTextInput.receiveAction(TextInputAction.done);
      await tester.pumpAndSettle();

      expect(callCount, 1);
    });

    testWidgets('delete button shows confirm and sends request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        callCount = 1;
        if (request.url.path == '/tasks/1/subtasks/1/delete') {
          return Response('', 200);
        }
        throw Exception('Request to ${request.url.path} has no response defined');
      });
      await renderWidget(tester, task, task.subtasks[0]);

      // Tap the text to enter edit mode.
      await tester.tap(find.text('vacuum'));
      await tester.pumpAndSettle();

      // Click delete action
      await tester.tap(find.byIcon(Icons.delete));
      await tester.pumpAndSettle();

      // Go through confirm window.
      expect(find.text('Are you sure?'), findsOneWidget);
      await tester.tap(find.text('Yes'));

      expect(callCount, 1);
    });
  });
}
