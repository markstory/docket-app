import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/viewmodels/projectdetails.dart';

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
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectDetails.clear();
    });

    test('loadData() reads from local db', () async {
      actions.client = MockClient((request) async {
        throw "Unexpected request to ${request.url.path}";
      });
      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.project, isNotNull);
    });

    test('loadData() refreshes on stale local db', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.project, isNotNull);
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

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.loading, isFalse);
      expect(viewmodel.project, isNotNull);
      expect(viewmodel.taskLists.length, equals(3));
    });

    test('reorderTask() updates state', () async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/tasks/1/move') {
          callCount += 1;
          expect(request.body, contains('child_order":1'));
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);
      db.projectDetails.expireSlug('home');

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.reorderTask(0, 0, 1, 0);
      expect(callCount, equals(1));
    });

    test('refresh() loads data from the server', () async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          callCount += 1;
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects') {
          return Response(projectListResponseFixture, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      expect(viewmodel.taskLists.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.taskLists[0].tasks.length, equals(2));
      expect(callCount, equals(1));
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

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.moveInto(overdue, 0, 0);
      // The added task gets wiped by the fixture being reloaded.
      expect(viewmodel.taskLists.length, equals(3));
    });

    test('archive() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/archive'));
        return Response("", 200);
      });

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.archive();

      var projectMap = await db.projectMap.get('home');
      expect(projectMap, isNull);

      var details = await db.projectDetails.get('home');
      expect(details.isEmpty, equals(true));
    });
  });

  group("$ProjectDetailsViewModel section methods", () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectDetails.clearSilent();
      await db.projectMap.clearSilent();
    });

    test('createSection() makes API request, and refreshes local db', () async {
      var fetchCount = 0;
      var addCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          fetchCount++;
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects/home/sections') {
          addCount += 1;
          expect(request.body, contains('name":"Repairs"'));
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });
      var section = Section(id: 1, name: 'Repairs', ranking: 1);

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.createSection(section);

      expect(addCount, equals(1));
      expect(fetchCount, greaterThan(0));

      var projectMap = await db.projectMap.get('home');
      expect(projectMap, isNotNull);

      var details = await db.projectDetails.get('home');
      expect(details.isEmpty, isFalse);
      // Is two because of server fixture.
      expect(details.project.sections.length, equals(2));
    });

    test('deleteSection() makes API request and refreshes local db', () async {
      var deleteCount = 0;
      var fetchCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          fetchCount++;
          return Response(projectDetailsResponseFixture, 200);
        }
        deleteCount++;
        expect(request.url.path, contains('/projects/home/sections/1/delete'));
        return Response("", 200);
      });
      var section = Section(id: 1, name: 'Repairs', ranking: 1);
      var project = Project(id: 1, slug: 'home', name: 'Home');

      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.deleteSection(section);

      expect(deleteCount, equals(1));
      expect(fetchCount, greaterThan(0));

      var details = await db.projectDetails.get('home');
      expect(details.isEmpty, isFalse);
      // Will be 2 because of the refresh from server.
      expect(details.project.sections.length, equals(2));
    });

    test('updateSection() makes API request and refreshes local db', () async {
      var editCount = 0;
      var fetchCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          fetchCount++;
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects/home/sections/1/edit') {
          editCount++;
          expect(request.body, contains('name":"Repairs"'));
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });
      var section = Section(id: 1, name: 'Repairs', ranking: 1);

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.updateSection(section);

      expect(editCount, equals(1));
      expect(fetchCount, greaterThan(0));

      var projectMap = await db.projectMap.get('home');
      expect(projectMap, isNotNull);

      var details = await db.projectDetails.get('home');
      expect(details.isEmpty, isFalse);
    });

    test('moveSection() sends a request', () async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home') {
          return Response(projectDetailsResponseFixture, 200);
        }
        if (request.url.path == '/projects/home/sections/1/move') {
          callCount += 1;
          expect(request.body, contains('ranking":1'));
          return Response('', 200);
        }
        throw "Unknown request to ${request.url.path}";
      });

      var data = parseData(projectDetailsResponseFixture);
      await setViewdata(db, data);

      var viewmodel = ProjectDetailsViewModel(db)..setSlug('home');
      await viewmodel.loadData();
      await viewmodel.moveSection(1, 2);
      expect(callCount, equals(1));
    });
  });
}
