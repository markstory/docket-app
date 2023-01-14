import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_appauth/flutter_appauth.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/viewmodels/calendarproviderlist.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_provider_list.json');
  final calendarListResponse = file.readAsStringSync();

  file = File('test_resources/calendar_provider_details.json');
  final calendarDetailsResponse = file.readAsStringSync();

  group('$CalendarProviderListViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.calendarList.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars') {
          return Response(calendarListResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderListViewModel(db);
      expect(viewmodel.providers.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.providers.length, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars') {
          return Response(calendarListResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderListViewModel(db);
      expect(viewmodel.providers.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.providers.length, equals(2));
    });

    test('remove() send request to server, and remove from db', () async {
      var deleted = CallCounter();
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars') {
          return Response(calendarListResponse, 200);
        }
        if (request.url.path == '/calendars/5/delete') {
          deleted();
          return Response('', 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderListViewModel(db);

      await viewmodel.loadData();
      var provider = viewmodel.providers[0];

      await db.calendarDetails.set(provider);
      await viewmodel.delete(provider);

      expect(deleted.callCount, equals(1));
      expect(await db.calendarDetails.get(provider.id), isNull);
    });

    test('createFromGoogle() ', () async {
      var token = AuthorizationTokenResponse(
        'access-token',
        'refresh-token',
        DateTime.now().add(const Duration(hours: 2)),
        'id-token',
        'type',
        [],
        {},
        {}
      );

      var created = CallCounter();
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/google/new') {
          created();
          return Response(calendarDetailsResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });
      var updated = CallCounter();
      var viewmodel = CalendarProviderListViewModel(db);
      viewmodel.addListener(updated);

      await viewmodel.createFromGoogle(token);
      var provider = viewmodel.providers[0];
      expect(provider.id, equals(1));

      expect(created.callCount, equals(1));
      expect(updated.callCount, equals(1));
    });
  });
}
