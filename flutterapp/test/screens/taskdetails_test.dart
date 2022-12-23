import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/taskdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());
  var file = File('test_resources/task_details.json');
  final taskDetails = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_list.json');
  final projectListResponse = file.readAsStringSync();

  group('$TaskDetailsScreen', () {
    var db = LocalDatabase(inTest: true);
    var decoded = jsonDecode(projectListResponse);
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    decoded = jsonDecode(taskDetails);
    var task = Task.fromMap(decoded['task']);

    setUp(() async {
      await db.apiToken.set(ApiToken(token: 'abc123'));
      await db.projectMap.addMany(projects);
      await db.taskDetails.set(task);
    });

    testWidgets('saves task', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/view') {
          return Response(taskDetails, 200);
        }
        if (request.url.path == '/tasks/1/edit') {
          callCount += 1;
          expect(request.body.contains("Rake leaves"), isTrue);
          return Response(taskDetails, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: TaskDetailsScreen(task),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('title')), "Rake leaves");
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    testWidgets('renders notes & subtasks', (tester) async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/view') {
          return Response(taskDetails, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: TaskDetailsScreen(task),
      ));
      await tester.pumpAndSettle();

      expect(find.text('clean the house'), findsOneWidget);
      expect(find.text('vacuum'), findsOneWidget);
      expect(find.text('clean bathrooms'), findsOneWidget);
    });
  });
}
