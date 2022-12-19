import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/projectdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/project_details.json');
  final todayResponse = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));
  var decoded = jsonDecode(todayResponse) as Map<String, dynamic>;

  group('$ProjectDetailsScreen', () {
    var db = LocalDatabase.instance();
    var viewdata = ProjectWithTasks.fromMap(decoded);

    setUp(() async {
      await db.apiToken.set(ApiToken(token: 'abc123'));
      await db.projectDetails.set(viewdata);
    });

    testWidgets('floating add button navigates to task add', (tester) async {
      var navigated = false;
      await tester.pumpWidget(EntryPoint(
          database: db,
          routes: {
            "/tasks/add": (context) {
              navigated = true;
              return const Text('Task add');
            }
          },
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      await tester.runAsync(() async {
        // tap the floating add button. Should go to task add
        await tester.tap(find.byKey(const ValueKey('floating-task-add')));
        await tester.pumpAndSettle();
      });

      expect(navigated, isTrue);
    });

    testWidgets('shows tasks', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      expect(find.text('clean dishes'), findsOneWidget);
      expect(find.text('cut grass'), findsOneWidget);
    });

    testWidgets('task item navigates to task details', (tester) async {
      var navigated = false;
      await tester.pumpWidget(EntryPoint(
          database: db,
          routes: {
            "/tasks/view": (context) {
              navigated = true;
              return const Text("Task Details");
            }
          },
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      await tester.runAsync(() async {
        await tester.tap(find.text('clean dishes'));
        await tester.pumpAndSettle();
      });

      expect(navigated, isTrue);
    });

    testWidgets('edit menu action navigates', (tester) async {
      var navigated = false;
      await tester.pumpWidget(EntryPoint(
          database: db,
          routes: {
            "/projects/edit": (context) {
              navigated = true;
              return const Text("Project Edit");
            }
          },
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      // open menu
      await tester.tap(find.byKey(const ValueKey('project-actions')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Edit Project'));
      await tester.pumpAndSettle();
      expect(navigated, isTrue);
    });

    testWidgets('add section shows dialog', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      // open menu
      await tester.tap(find.byKey(const ValueKey('project-actions')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Add Section'));
      await tester.pumpAndSettle();

      expect(find.byKey(const ValueKey('section-name')), findsOneWidget);
    });
  });
}
