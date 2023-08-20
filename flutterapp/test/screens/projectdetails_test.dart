import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/projectdetails.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/project_details.json');
  final detailsResponse = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));
  var decoded = jsonDecode(detailsResponse) as Map<String, dynamic>;

  group('$ProjectDetailsScreen', () {
    var db = LocalDatabase(inTest: true);
    var viewdata = ProjectWithTasks.fromMap(decoded);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
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

      // tap the floating add button. Should go to task add
      await tester.tap(find.byKey(const ValueKey('floating-task-add')));
      await tester.pumpAndSettle();

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

      await tester.tap(find.text('clean dishes'));
      await tester.pumpAndSettle();

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

    testWidgets('rename section sends requests', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(detailsResponse, 200);
        }
        if (request.url.path == '/projects/home/sections/1/edit') {
          expect(request.body.contains('Renamed section'), isTrue);
          callCount += 1;

          return Response('', 200);
        }
        throw Exception('Unmocked request to ${request.url.path} made');
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      // open menu
      await tester.tap(find.byKey(const ValueKey('section-actions')).first);
      await tester.pumpAndSettle();

      // Open rename dialog
      await tester.tap(find.text('Rename'));
      await tester.pumpAndSettle();

      // Fill out dialog
      await tester.enterText(find.byKey(const ValueKey('section-name')), 'Renamed section');
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    testWidgets('delete section sends requests', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home/sections/1/delete') {
          callCount += 1;

          return Response('', 200);
        }
        throw Exception('Unmocked request to ${request.url.path} made');
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectDetailsScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      // open menu
      await tester.tap(find.byKey(const ValueKey('section-actions')).first);
      await tester.pumpAndSettle();

      // Open Delete dialog
      await tester.tap(find.text('Delete'));
      await tester.pumpAndSettle();

      // Confirm dialog
      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
