import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/project.dart';
import 'package:docket/viewmodels/trashbin.dart';

ProjectWithTasks parseProjectDetails(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('project')) {
    throw 'Cannot parse tasks without tasks key';
  }

  return ProjectWithTasks.fromMap(decoded);
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/tasks_today.json');
  final tasksResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  group('$TrashbinViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.trashbin.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/tasks/deleted') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TrashbinViewModel(db);
      expect(viewmodel.tasks.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.tasks.length, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/tasks/deleted') {
          return Response(tasksResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TrashbinViewModel(db);
      expect(viewmodel.tasks.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.tasks.length, equals(2));
    });
  });
}
