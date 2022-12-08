import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/screens/calendarproviderdetails_view_model.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/calendar_provider_details.json');
  final calendarDetailsResponse = file.readAsStringSync();

  group('$CalendarProviderDetailsViewModel', () {
    var db = LocalDatabase.instance();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.completedTasks.clear();
    });

    test('provider property throws without data', () async {
      var viewmodel = CalendarProviderDetailsViewModel(db, session);
      viewmodel.setId(5);
      expect(() => viewmodel.provider, throwsException);
    });

    test('loadData() fetches from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db, session);
      var updateCount = 0;
      viewmodel.addListener(() {
        updateCount += 1;
      });
      viewmodel.setId(5);

      await viewmodel.loadData();
      expect(updateCount, greaterThan(1));
      expect(viewmodel.provider.id, equals(5));
      expect(viewmodel.provider.kind, equals('google'));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/calendars/5/view') {
          return Response(calendarDetailsResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = CalendarProviderDetailsViewModel(db, session);
      viewmodel.setId(5);

      await viewmodel.refresh();
      expect(viewmodel.provider.kind, equals('google'));
    });
  });
}
