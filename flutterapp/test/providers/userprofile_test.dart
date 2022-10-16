import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/userprofile.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  int listenerCallCount = 0;

  var db = LocalDatabase();
  late UserProfileProvider provider;

  var file = File('test_resources/profile.json');
  final profileResponseFixture = file.readAsStringSync();

  group('$UserProfileProvider', () {
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      listenerCallCount = 0;
      provider = UserProfileProvider(db, session)
        ..addListener(() {
          listenerCallCount += 1;
        });
      await provider.clear();
    });

    test('get() fetches from server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/users/profile'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });

      var profile = await provider.get();
      expect(listenerCallCount, equals(1));
      expect(profile.email, equals('mark@mark-story.com'));
      expect(profile.name, equals('Mark Story'));

      // Should get the same data.
      profile = await provider.get();
      expect(profile.email, equals('mark@mark-story.com'));
      expect(requestCount, equals(1), reason: 'Only one request should be made');
    });

    test('refresh() loads from server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/users/profile'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });

      await provider.refresh();
      await provider.refresh();
      expect(requestCount, equals(2), reason: 'Only one request should be made');
    });

    test('update() sends request to server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/users/profile'));
        expect(request.method, equals('POST'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });

      var profile = UserProfile(
          name: 'mark', email: 'mark@example.com', timezone: 'America/New_York', theme: 'system', avatarHash: '');
      await provider.update(profile);
      expect(requestCount, equals(1), reason: 'One request should be made');
    });
  });
}
