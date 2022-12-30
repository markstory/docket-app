import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_list.json');
  final projectsResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectViewResponseFixture = file.readAsStringSync();

  group('$ProjectsProvider project methods', () {
    late ProjectsProvider provider;

    setUp(() async {
      var db = LocalDatabase();
      provider = ProjectsProvider(db);
      await provider.clear();
      await db.apiToken.set(ApiToken.fake());
    });

    test('fetchProjects() and getAll() work together', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        requestCounter += 1;
        return Response(projectsResponseFixture, 200);
      });

      var listener = CallCounter();
      provider.addListener(listener);

      await provider.fetchProjects();
      provider.removeListener(listener);

      expect(listener.callCount, greaterThan(0));
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

      var listener = CallCounter();
      var db = LocalDatabase();
      await db.projectMap.set(stale);
      provider.addListener(listener);

      await provider.fetchProjects();
      expect(listener.callCount, greaterThan(0));
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
      expect(details.isEmpty, equals(true));
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
      expect(details.isEmpty, equals(true));
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
    });
  });

  group("$ProjectsProvider section methods", () {
    late ProjectsProvider provider;

    setUp(() async {
      var db = LocalDatabase();
      provider = ProjectsProvider(db);
      await provider.clear();
      await db.apiToken.set(ApiToken.fake());
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
      expect(details.isEmpty, equals(true));
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
      expect(details.isEmpty, equals(true));
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
      expect(details.isEmpty, equals(true));
    });
  });
}
