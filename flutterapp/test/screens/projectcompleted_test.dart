import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/projectcompleted.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_details.json');
  final projectDetails = file.readAsStringSync();
  var decoded = jsonDecode(projectDetails) as Map<String, dynamic>;

  group('$ProjectCompletedScreen', () {
    var db = LocalDatabase(inTest: true);
    var viewdata = ProjectWithTasks.fromMap(decoded);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.completedTasks.set(viewdata);
    });

    testWidgets('shows tasks', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectCompletedScreen(viewdata.project),
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
          child: ProjectCompletedScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.text('clean dishes'));
      await tester.pumpAndSettle();

      expect(navigated, isTrue);
    });
  });
}
