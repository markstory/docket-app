import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/viewmodels/userprofile.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/profile.json');
  final profileResponseFixture = file.readAsStringSync();

  group('$UserProfileViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.profile.clear();
    });

    test('loadData() fetches from server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/api/users/profile'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });
      var mockListener = CallCounter();
      var viewmodel = UserProfileViewModel(db);
      viewmodel.addListener(mockListener);

      await viewmodel.loadData();
      var profile = viewmodel.profile;

      expect(requestCount, equals(1));
      expect(mockListener.callCount, greaterThan(0));
      expect(profile.email, equals('mark@example.com'));
      expect(profile.name, equals('Mark Story'));
    });

    test('refresh() loads from server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/api/users/profile'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });
      var viewmodel = UserProfileViewModel(db);

      await viewmodel.refresh();
      await viewmodel.refresh();
      expect(requestCount, equals(2), reason: 'Each refresh sends a request');
    });

    test('update() sends request to server', () async {
      var requestCount = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/api/users/profile'));
        expect(request.method, equals('POST'));
        requestCount++;

        return Response(profileResponseFixture, 200);
      });

      var profile = UserProfile(
          name: 'mark', email: 'mark@example.com', timezone: 'America/New_York', theme: 'system', avatarHash: '');
      var viewmodel = UserProfileViewModel(db);

      await viewmodel.update(profile);
      expect(requestCount, equals(1), reason: 'One request should be made');
    });
  });
}
