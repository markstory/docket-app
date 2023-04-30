import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/task.dart';
import 'package:docket/screens/upcoming.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final todayResponse = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));
  var decoded = jsonDecode(todayResponse) as Map<String, dynamic>;

  group('$UpcomingScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      var viewdata = TaskViewData.fromMap(decoded);
      await db.dailyTasks.set(viewdata.groupByDay());
      await db.apiToken.set(ApiToken.fake());
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
          child: const UpcomingScreen(),
      ));

      // tap the floating add button. Should go to task add
      await tester.tap(find.byKey(const ValueKey('floating-task-add')));
      await tester.pumpAndSettle();

      expect(navigated, isTrue);
    });

    testWidgets('shows upcoming tasks', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const UpcomingScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('clean dishes'), findsOneWidget);
      expect(find.text('cut grass'), findsOneWidget);
    });

    testWidgets('shows upcoming tasks in evening', (tester) async {
      var viewdata = TaskViewData.fromMap(decoded);
      viewdata.tasks.add(Task(
          id: 4,
          projectId: 1,
          projectColor: 1,
          projectSlug: 'home',
          projectName: 'Home',
          title: 'evening item',
          body: '',
          dueOn: today,
          evening: true,
          dayOrder: 0,
          childOrder: 10,
          completed: false));
      await db.dailyTasks.set(viewdata.groupByDay());

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const UpcomingScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('evening item'), findsOneWidget);
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
          child: const UpcomingScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.text('clean dishes'));
      await tester.pumpAndSettle();

      expect(navigated, isTrue);
    });
  });
}
