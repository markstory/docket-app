import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/viewmodel/taskdetails.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());

  var file = File('test_resources/task_create_today.json');
  final taskResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/task_create_today.json');
  final taskCreateTodayResponseFixture = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  group('$TaskDetailsViewModel', () {
    var db = LocalDatabase(inTest: true);
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.taskDetails.clearSilent();
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

    test('create() sends request, updates local', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, equals('/tasks/add'));

        return Response(taskCreateTodayResponseFixture, 200);
      });

      var viewmodel = TaskDetailsViewModel(db, session);
      viewmodel.setId(0);
      var task = Task.blank();

      // This data has to match the fixture file.
      task.title = "fold the towels";
      task.projectId = 1;
      task.dueOn = today;

      var created = await viewmodel.create(task);
      expect(created.id, equals(1));
      expect(viewmodel.id, equals(created.id));
      expect(viewmodel.task.id, equals(created.id));
    });

    test('reorderSubtask() sends request, updates local', () async {
      // TODO
    });
  });
}
