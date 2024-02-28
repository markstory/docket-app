import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/main.dart';
import 'package:docket/models/task.dart';
import 'package:docket/screens/today.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());
  var urlDate = formatters.dateString(today);

  var file = File('test_resources/tasks_today.json');
  final todayResponse = file.readAsStringSync().replaceAll('__TODAY__', urlDate);
  var decoded = jsonDecode(todayResponse) as Map<String, dynamic>;

  group('$TodayScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.dailyTasks.clear();

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
        child: const TodayScreen(),
      ));

      // tap the floating add button. Should go to task add
      await tester.tap(find.byKey(const ValueKey('floating-task-add')));
      await tester.pumpAndSettle();

      expect(navigated, isTrue);
    });

    testWidgets('shows loading error', (tester) async {
      await db.dailyTasks.clearSilent();

      actions.client = MockClient((request) async {
        return Response('{"error": "Server unavailable"}', 500);
      });

      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const TodayScreen(),
      ));
      await tester.pump();
      await tester.pump(const Duration(seconds: 1));

      expect(find.text('Could not load data from server.'), findsOneWidget);
    });

    testWidgets('shows today tasks', (tester) async {
      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const TodayScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('clean dishes'), findsOneWidget);
      expect(find.text('cut grass'), findsOneWidget);
    });

    testWidgets('shows overdue tasks', (tester) async {
      var yesterday = today.subtract(const Duration(days: 2));
      var viewdata = TaskViewData.fromMap(decoded);
      viewdata.tasks.add(Task(
          id: 3,
          projectId: 1,
          projectColor: 1,
          projectSlug: 'home',
          projectName: 'Home',
          title: 'overdue item',
          body: '',
          dueOn: yesterday,
          evening: false,
          dayOrder: 0,
          childOrder: 10,
          subtasks: [],
          completed: false));
      var rangeView = TaskRangeView.fromLists(
        tasks: viewdata.tasks,
        calendarItems: viewdata.calendarItems,
        start: today,
      );
      await db.dailyTasks.setRange(rangeView);

      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const TodayScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('overdue item'), findsOneWidget);
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
        child: const TodayScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.text('clean dishes'));
      await tester.pumpAndSettle();

      expect(navigated, isTrue);
    });

    testWidgets('task item can be completed', (tester) async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/tasks/1/complete') {
          requestCount += 1;
          return Response('', 200);
        }
        if (request.url.path == '/api/tasks/day/$urlDate') {
          return Response(todayResponse, 200);
        }
        throw Exception('Unmocked request to ${request.url.path}');
      });
      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const TodayScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.byType(Checkbox).first);
      await tester.pump(const Duration(milliseconds: 250));
      expect(
          tester.getSemantics(find.byType(Checkbox).first),
          matchesSemantics(
            hasTapAction: true,
            isChecked: true,
            isEnabled: true,
            isFocusable: true,
            hasCheckedState: true,
            hasEnabledState: true,
          ));
      await tester.pump(const Duration(seconds: 1));
      await tester.pumpAndSettle();
      expect(requestCount, equals(1));
    });

    testWidgets('task item can be deleted', (tester) async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/tasks/1/delete') {
          requestCount += 1;
          return Response('', 200);
        }
        if (request.url.path == '/api/tasks/day/$urlDate') {
          return Response(todayResponse, 200);
        }
        throw Exception('Unmocked request to ${request.url.path}');
      });
      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const TodayScreen(),
      ));
      await tester.pumpAndSettle();

      // open action menu
      await tester.tap(find.byKey(const ValueKey('task-actions')).first);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Delete'));
      await tester.pumpAndSettle();

      expect(find.text('Are you sure?'), findsOneWidget);
      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(requestCount, equals(1));
    });
  });
}
