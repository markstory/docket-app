import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/screens/calendarproviderlist.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_provider_list.json');
  final calendarsResponse = file.readAsStringSync();
  var decoded = jsonDecode(calendarsResponse) as Map<String, dynamic>;
  List<CalendarProvider> providers =
      (decoded["providers"] as List).map<CalendarProvider>((item) => CalendarProvider.fromMap(item)).toList();

  group('$CalendarProviderListScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.calendarList.set(providers);
    });

    testWidgets('render items', (tester) async {
      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const CalendarProviderListScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text("(mark@example.io)"), findsOneWidget);
      expect(find.text("(mark.story@example.com)"), findsOneWidget);
    });

    testWidgets('tap item navigates', (tester) async {
      var navigated = false;
      await tester.pumpWidget(EntryPoint(
        database: db,
        routes: {
          "/calendars/view": (context) {
            navigated = true;
            return const Text("CalendarProvider details");
          }
        },
        child: const CalendarProviderListScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.text("(mark.story@example.com)"));
      await tester.pumpAndSettle();
      expect(navigated, isTrue);
    });

    testWidgets('delete action confirms and sends request', (tester) async {
      var deleted = CallCounter();
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars') {
          return Response(calendarsResponse, 200);
        }
        if (request.url.path == '/calendars/5/delete') {
          deleted();
          return Response('', 200);
        }
        throw Exception('Request to unmocked ${request.url.path}');
      });

      await tester.pumpWidget(EntryPoint(
        database: db,
        child: const CalendarProviderListScreen(),
      ));
      await tester.pumpAndSettle();

      // open actions menu
      await tester.tap(find.byKey(const ValueKey('provider-actions')).first);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Delete'));
      await tester.pumpAndSettle();

      expect(find.text('Are you sure?'), findsOneWidget);
      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(deleted.callCount, equals(1));
    });
  });
}
