import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/screens/profilesettings.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/profile.json');
  final profileResponseFixture = file.readAsStringSync();

  group('$ProfileSettingsScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.profile.clearSilent();

      var decoded = jsonDecode(profileResponseFixture);
      var profile = UserProfile.fromMap(decoded['user']);
      await db.profile.set(profile);
      await db.apiToken.set(ApiToken.fake());
    });

    testWidgets('shows form', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const ProfileSettingsScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('Profile Settings'), findsOneWidget);
      expect(find.text('Mark Story'), findsOneWidget);
    });

    testWidgets('form can submit', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/users/profile') {
          callCount += 1;
          expect(request.body.contains('New Name'), isTrue);

          return Response(profileResponseFixture, 200);
        }
        throw Exception('Request made to unmocked ${request.url.path}');
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const ProfileSettingsScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('name')), 'New Name!');
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
