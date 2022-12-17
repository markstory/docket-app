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
import 'package:docket/screens/calendarproviderdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_source.json');
  final calendarSourceResponse = file.readAsStringSync();

  file = File('test_resources/calendar_provider_details.json');
  final calendarResponse = file.readAsStringSync();
  var decoded = jsonDecode(calendarResponse) as Map<String, dynamic>;
  CalendarProvider provider = CalendarProvider.fromMap(decoded["provider"]);

  group('$CalendarProviderDetailsScreen', () {
    var db = LocalDatabase.instance();

    setUp(() async {
      await db.apiToken.set(ApiToken(token: 'abc123'));
      await db.calendarDetails.set(provider);
    });

    testWidgets('render sources', (tester) async {
      await tester.pumpWidget(EntryPoint(
        database: db,
        child: CalendarProviderDetailsScreen(provider),
      ));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      expect(find.text("mark@example.com"), findsOneWidget);
    });

    testWidgets('sync makes request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/sources/28/sync') {
          callCount += 1;
          return Response(calendarSourceResponse, 200);
        }
        throw Exception('Request made to ${request.url.path} has no response');
      });

      await tester.pumpWidget(EntryPoint(
        database: db,
        child: CalendarProviderDetailsScreen(provider),
      ));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      // Open menu
      var menu = find.byKey(const ValueKey('source-actions')).first;
      await tester.tap(menu);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Sync'));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      expect(callCount, equals(1));
    });
  });
}
