import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/viewmodels/projectcompleted.dart';

ProjectWithTasks parseProjectDetails(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('project')) {
    throw 'Cannot parse data without project key';
  }

  return ProjectWithTasks.fromMap(decoded);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_completed.json');
  final projectCompletedResponse = file.readAsStringSync();

  group('$ProjectCompletedViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.completedTasks.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectCompletedViewModel(db);
      expect(viewmodel.tasks.length, equals(0));

      viewmodel.setSlug('home');
      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));
    });

    test('loadData() refreshes with stale data', () async {
      var callCounter = CallCounter();
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          callCounter();
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectCompletedViewModel(db);
      expect(viewmodel.tasks.length, equals(0));

      viewmodel.setSlug('home');
      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));

      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));

      db.completedTasks.expireSlug('home');
      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));
      expect(callCounter.callCount, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var counter = CallCounter();
      var viewmodel = ProjectCompletedViewModel(db);
      viewmodel.addListener(counter);
      expect(viewmodel.tasks.length, equals(0));

      viewmodel.setSlug('home');
      await viewmodel.refresh();
      expect(viewmodel.tasks.length, equals(2));
      expect(counter.callCount, equals(1));
    });

    test('refreshSilent() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectCompletedViewModel(db);
      var counter = CallCounter();
      viewmodel.addListener(counter);
      expect(viewmodel.tasks.length, equals(0));

      viewmodel.setSlug('home');
      await viewmodel.refreshSilent();
      expect(viewmodel.tasks.length, equals(2));
      expect(counter.callCount, equals(0));
    });
  });
}
