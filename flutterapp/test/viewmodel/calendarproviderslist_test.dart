import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/viewmodel/calendarproviderlist.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_provider_list.json');
  final calendarListResponse = file.readAsStringSync();

  group('$CalendarProviderListViewModel', () {
    var db = LocalDatabase.instance();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.calendarList.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars') {
          return Response(calendarListResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderListViewModel(db, session);
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

      var viewmodel = CalendarProviderListViewModel(db, session);
      expect(viewmodel.providers.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.providers.length, equals(2));
    });
  });
}
