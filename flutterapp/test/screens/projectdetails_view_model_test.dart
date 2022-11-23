import 'dart:convert';
import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/screens/projectdetails_view_model.dart';

// Parse a list response into a list of tasks.
ProjectWithTasks parseData(String data) {
  var decoded = jsonDecode(data);
  if (!decoded.containsKey('tasks')) {
    throw 'Cannot parse tasks without tasks key';
  }
  return ProjectWithTasks.fromMap(decoded);
}

String extractTitle(Task task) {
  return task.title;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();


  var file = File('test_resources/project_details.json');
  final projectDetailsResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_list.json');
  final projectListResponseFixture = file.readAsStringSync();

  Future<void> setViewdata(LocalDatabase db, ProjectWithTasks data) async {
    await db.projectDetails.set(data);
  }

  group('$ProjectDetailsViewModel', () {
    var db = LocalDatabase.instance();
    var session = SessionProvider(db, token: 'api-token');

    setUp(() async {
      await db.projectDetails.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects') {
          return Response(projectListResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = ProjectDetailsViewModel(db, session)..setSlug('home');

      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.project, isNotNull);
      expect(viewmodel.taskLists.length, equals(3));
    });

    test('reorderTask() updates state', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          expect(request.body, contains('child_order":1'));
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db, session)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.reorderTask(0, 0, 1, 0);
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects') {
          return Response(projectListResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = ProjectDetailsViewModel(db, session)..setSlug('home');
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.taskLists[0].tasks.length, equals(2));
    });

    test('moveInto() can add tasks', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/tasks/99/move') {
          expect(request.body, contains('child_order":0'));

          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var overdue = Task.blank();
      overdue.id = 99;
      overdue.projectId = 1;
      overdue.projectSlug = 'home';
      overdue.title = 'something old';
      overdue.dueOn = DateTime.now().subtract(const Duration(days: 2));

      var data = parseData(projectDetailsResponseFixture);
      data.tasks.add(overdue);
      setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db, session)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.moveInto(overdue, 0, 0);
      // The added task gets wiped by the fixture being reloaded.
      expect(viewmodel.taskLists.length, equals(3));
    });
  });
}
