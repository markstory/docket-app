import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/providers/session.dart';
import 'package:docket/viewmodel/taskdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/task_create_today.json');
  final taskResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  group('$TaskDetailsViewModel', () {
    var db = LocalDatabase.instance();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.taskDetails.clear();
    });

    test('task property throws without data', () async {
      var viewmodel = TaskDetailsViewModel(db, session);
      viewmodel.setId(1);
      expect(() => viewmodel.task, throwsException);
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/view') {
          return Response(taskResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TaskDetailsViewModel(db, session);
      viewmodel.setId(1);

      await viewmodel.loadData();
      expect(viewmodel.task.id, equals(1));
    });

    test('update() sends server request', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/tasks/1/view') {
          return Response(taskResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/edit') {
          return Response(taskResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = TaskDetailsViewModel(db, session);
      viewmodel.setId(1);
      var updateCount = 0;
      viewmodel.addListener(() {
        updateCount += 1;
      });

      await viewmodel.loadData();
      var task = viewmodel.task;

      await viewmodel.update(task);
      expect(updateCount, greaterThan(0));
    });
  });
}
