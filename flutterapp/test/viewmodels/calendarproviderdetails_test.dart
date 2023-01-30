import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/viewmodels/calendarproviderdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_provider_details.json');
  final calendarDetailsResponse = file.readAsStringSync();

  file = File('test_resources/calendar_source.json');
  final calendarSourceResponse = file.readAsStringSync();

  group('$CalendarProviderDetailsViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.calendarDetails.clear();
    });

    test('loadData() fetches from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      var updateCount = 0;
      viewmodel.addListener(() {
        updateCount += 1;
      });
      viewmodel.setId(5);

      await viewmodel.loadData();

      expect(updateCount, greaterThan(1));
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.provider.id, equals(5));
      expect(viewmodel.provider.kind, equals('google'));
      expect(viewmodel.provider.sources.length, equals(3));
      var source = viewmodel.provider.sources[0];
      expect(source.name, equals('mark@example.com'));
      expect(source.color, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      viewmodel.setId(5);

      await viewmodel.refresh();
      expect(viewmodel.provider.kind, equals('google'));
      expect(viewmodel.loading, isFalse);
    });

    test('syncEvents() makes a request', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }

        if (request.url.path == '/calendars/5/sources/28/sync') {
          return Response(calendarSourceResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      viewmodel.setId(5);
      await viewmodel.loadData();

      await viewmodel.syncEvents(viewmodel.provider.sources[0]);
      expect(viewmodel.loading, isFalse);
      expect(db.tasksDaily.isExpired, isTrue);
      expect(db.upcoming.isExpired, isTrue);
    });

    test('removeSource() makes a request', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }

        if (request.url.path == '/calendars/5/sources/28/delete') {
          return Response('', 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      viewmodel.setId(5);
      await viewmodel.loadData();

      await viewmodel.removeSource(viewmodel.provider.sources[0]);
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.provider.sources.length, equals(2));
    });

    test('linkSource() makes a request', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }

        if (request.url.path == '/calendars/5/sources/add') {
          return Response(calendarSourceResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      viewmodel.setId(5);
      await viewmodel.loadData();

      await viewmodel.linkSource(viewmodel.provider.sources[1]);
      expect(viewmodel.loading, isFalse);
    });

    test('updateSource() makes a request, and updates local state', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }
        if (request.url.path == '/calendars/5/sources/28/edit') {
          return Response(calendarSourceResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db);
      viewmodel.setId(5);
      await viewmodel.loadData();

      var source = viewmodel.provider.sources[0];
      source.color = 3;

      await viewmodel.updateSource(source);
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.provider.sources.length, equals(3));
    });
  });
}
