import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/viewmodel/projectcompleted.dart';

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
    var db = LocalDatabase.instance();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.completedTasks.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectCompletedViewModel(db, session);
      expect(viewmodel.tasks.length, equals(0));

      viewmodel.setSlug('home');
      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectCompletedResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var counter = CallCounter();
      var viewmodel = ProjectCompletedViewModel(db, session);
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

      var viewmodel = ProjectCompletedViewModel(db, session);
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
