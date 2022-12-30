import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/screens/login.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var file = File('test_resources/login.json');
  var loginResponse = file.readAsStringSync();

  group('$LoginScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.clearSilent();
    });

    testWidgets('shows form with empty database', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginScreen(),
      ));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      expect(find.text('E-Mail'), findsOneWidget);
      expect(find.text('Password'), findsOneWidget);
    });

    testWidgets('request made on submit', (tester) async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/mobile/login') {
          requestCount += 1;
          return Response(loginResponse, 200);
        }
        throw Exception('Unmocked URL ${request.url.path}');
      });
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginScreen(),
      ));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      await tester.enterText(find.byKey(const ValueKey('email')), 'mark@example.com');
      await tester.enterText(find.byKey(const ValueKey('password')), 'password12');
      await tester.runAsync(() async {
        await tester.tap(find.text('Log in'));
        await tester.pumpAndSettle();
      });
      expect(requestCount, equals(1));
    });
  });

  group('$LoginRequired', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.clearSilent();
    });

    testWidgets('show child with session', (tester) async {
      await db.apiToken.set(ApiToken.fake());
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginRequired(child: Text('Content Text'))
        ));
      await tester.pumpAndSettle();
      expect(find.text('Content Text'), findsOneWidget);
    });

    testWidgets('show Login without session', (tester) async {
      await db.apiToken.set(ApiToken.fake());
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginRequired(child: Text('Content Text'))
        ));
      expect(find.text('Content Text'), findsNothing);
    });
  });
}
