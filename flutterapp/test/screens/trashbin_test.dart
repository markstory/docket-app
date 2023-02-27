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
import 'package:docket/screens/trashbin.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final todayResponse = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));
  var decoded = jsonDecode(todayResponse) as Map<String, dynamic>;

  group('$TrashbinScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.trashbin.clearSilent();
      var viewdata = TaskViewData.fromMap(decoded);
      await db.apiToken.set(ApiToken.fake());
      await db.trashbin.set(viewdata);
    });

    testWidgets('shows empty state', (tester) async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/deleted') {
          return Response('{"tasks":[]}', 200);
        }
        throw Exception('Request made to unmocked ${request.url.path}');
      });
      await db.trashbin.clearSilent();

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const TrashbinScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('Trash Bin'), findsOneWidget);
      expect(find.text('No items in trash'), findsOneWidget);
    });

    testWidgets('shows items', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const TrashbinScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('Trash Bin'), findsOneWidget);

      expect(find.text('clean dishes'), findsOneWidget);
      expect(find.text('cut grass'), findsOneWidget);
    });

    testWidgets('item restore sends a request', (tester) async {
      // This is skipped because it is locking on the database operations.
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/undelete') {
          callCount += 1;
          return Response("", 200);
        }
        if (request.url.path == '/tasks/deleted') {
          return Response(todayResponse, 200);
        }
        throw Exception('Request made to unmocked ${request.url.path}');
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const TrashbinScreen(),
      ));
      await tester.pumpAndSettle();
      expect(find.text('Trash Bin'), findsOneWidget);

      expect(find.text('Restore'), findsNWidgets(2));
      await tester.tap(find.text('Restore').first);
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
