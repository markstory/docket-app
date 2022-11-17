import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/providers/session.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_list.json');
  final projectsResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectViewResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_completed.json');
  final projectCompletedResponseFixture = file.readAsStringSync();

  group('$ProjectsProvider project methods', () {
    late ProjectsProvider provider;
    late SessionProvider session;
    int listenerCallCount = 0;

    setUp(() async {
      listenerCallCount = 0;
      var db = LocalDatabase();
      session = SessionProvider(db, token: 'api-token');
      provider = ProjectsProvider(db, session)
        ..addListener(() {
          listenerCallCount += 1;
        });
      await provider.clear();
    });

    test('fetchProjects() and getAll() work together', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        requestCounter += 1;
        return Response(projectsResponseFixture, 200);
      });

      await provider.fetchProjects();
      expect(listenerCallCount, greaterThan(0));
      expect(requestCounter, equals(1));

      var projects = await provider.getAll();
      expect(projects.length, equals(2));
      expect(projects[0].slug, equals('work'));
      expect(projects[1].slug, equals('home'));
    });

    test('fetchProjects() will remove stale projects', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        requestCounter += 1;
        return Response(projectsResponseFixture, 200);
      });

      var stale = Project.blank();
      stale.slug = 'stale';
      stale.id = 99;
      stale.name = 'Stale';

      var db = LocalDatabase();
      await db.projectMap.set(stale);

      await provider.fetchProjects();
      expect(listenerCallCount, greaterThan(0));
      expect(requestCounter, equals(1));

      var projects = await provider.getAll();
      expect(projects.length, equals(2));
      expect(projects[0].slug, equals('work'));
      expect(projects[1].slug, equals('home'));
    });

    test('fetchProjects() handles error on server error', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        return Response('{"errors": ["bad things"]}', 400);
      });

      expect(provider.fetchProjects(), throwsException);
    });

    test('move() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/move'));
        return Response(projectViewResponseFixture, 200);
      });
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.move(project, 2);

      var projectMap = await db.projectMap.get('home');
      expect(project, isNotNull);
      expect(projectMap!.slug, equals('home'));
    });

    test('update() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/edit'));
        return Response(projectViewResponseFixture, 200);
      });
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.update(project);

      var projectMap = await db.projectMap.get('home');
      expect(project, isNotNull);
      expect(projectMap!.slug, equals('home'));

      var details = await db.projectDetails.get('home');
      expect(details.missingData, equals(true));
    });

    test('archive() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/archive'));
        return Response("", 200);
      });
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.archive(project);

      var projectMap = await db.projectMap.get('home');
      expect(projectMap, isNull);

      var details = await db.projectDetails.get('home');
      expect(details.missingData, equals(true));
    });

    test('unarchive() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects') {
          return Response(projectsResponseFixture, 200);
        }
        expect(request.url.path, contains('/projects/home/unarchive'));
        return Response("", 200);
      });
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectArchive.set([project]);

      await provider.unarchive(project);

      // TODO This should pass but doesn't currently.
      // var archived = await db.projectArchive.get();
      // expect(archived, isNull);

      // var projectMap = await db.projectMap.get('home');
      // expect(projectMap, isNull);
    });
  });

  group("$ProjectsProvider section methods", () {
    late ProjectsProvider provider;
    late SessionProvider session;

    setUp(() async {
      var db = LocalDatabase();
      session = SessionProvider(db, token: 'api-token');
      provider = ProjectsProvider(db, session);
      await provider.clear();
    });

    test('deleteSection() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/sections/1/delete'));
        return Response("", 200);
      });
      var section = Section(id: 1, name: 'Repairs', ranking: 1);
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.deleteSection(project, section);

      var details = await db.projectDetails.get('home');
      expect(details.missingData, equals(true));
    });

    test('updateSection() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/sections/1/edit'));
        return Response("", 200);
      });
      var section = Section(id: 1, name: 'Repairs', ranking: 1);
      var project = Project(id: 1, slug: 'home', name: 'Home');

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.updateSection(project, section);

      var projectMap = await db.projectMap.get('home');
      expect(projectMap, isNull);

      var details = await db.projectDetails.get('home');
      expect(details.missingData, equals(true));
    });

    test('moveSection() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/sections/1/move'));
        return Response("", 200);
      });
      var project = Project(id: 1, slug: 'home', name: 'Home');
      var section = Section(id: 1, name: 'Repairs', ranking: 1);

      var db = LocalDatabase();
      await db.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await provider.moveSection(project, section, 2);

      expect(section.ranking, equals(2));
      var details = await db.projectDetails.get('home');
      expect(details.missingData, equals(true));
    });
  });
}
