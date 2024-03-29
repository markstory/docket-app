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

    testWidgets('shows form with no session', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('E-Mail'), findsOneWidget);
      expect(find.text('Password'), findsOneWidget);
    });

    testWidgets('shows validation errors on blank data', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Log in'));
      await tester.pumpAndSettle();
      expect(find.text('E-mail is required'), findsOneWidget);
      expect(find.text('Password is required'), findsOneWidget);
    });

    testWidgets('shows error on login failure', (tester) async {
      actions.client = MockClient((request) async {
        return Response('{"errors": ["Authentication required"]}', 401);
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('email')), 'mark@example.com');
      await tester.enterText(find.byKey(const ValueKey('password')), 'password12');
      await tester.tap(find.text('Log in'));

      await tester.pump();
      await tester.pump(const Duration(seconds: 1));

      expect(find.byType(SnackBar), findsOneWidget);
      expect(find.text('Authentication failed.'), findsOneWidget);
    });

    testWidgets('request made on submit', (tester) async {
      var requestCount = 0;
      var navigated = false;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/tokens/add') {
          requestCount += 1;
          return Response(loginResponse, 200);
        }
        throw Exception('Unmocked URL ${request.url.path}');
      });
      await tester.pumpWidget(EntryPoint(
          database: db,
          routes: {
            "/tasks/today": (context) {
              navigated = true;
              return const Text('Today screen');
            }
          },
          child: const LoginScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('email')), 'mark@example.com');
      await tester.enterText(find.byKey(const ValueKey('password')), 'password12');
      await tester.tap(find.text('Log in'));
      await tester.pumpAndSettle();

      expect(find.text('Today screen'), findsOneWidget);
      expect(requestCount, equals(1));
      expect(navigated, isTrue);
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
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const LoginRequired(child: Text('Content Text'))
        ));
      expect(find.text('Content Text'), findsNothing);
    });
  });
}
