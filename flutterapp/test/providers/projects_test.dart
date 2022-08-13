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

  late ProjectsProvider provider;
  late SessionProvider session;
  int listenerCallCount = 0;

  var file = File('test_resources/project_list.json');
  final projectsResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectViewResponseFixture = file.readAsStringSync();

  Matcher throwsStaleData() {
    return throwsA(const TypeMatcher<StaleDataError>());
  }

  group('$ProjectsProvider', () {
    setUp(() async {
      listenerCallCount = 0;
      var db = LocalDatabase();
      session = SessionProvider(db)..set('api-token');
      provider = ProjectsProvider(db, session)
        ..addListener(() {
          listenerCallCount += 1;
        });
      await provider.clear();
    });

    test('fetchProject() and getAll() work together', () async {
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

    test('fetchProjects() handles error on server error', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        return Response('{"errors": ["bad things"]}', 400);
      });

      expect(provider.fetchProjects(), throwsException);
    });

    test('fetchBySlug() and getBySlug() work together', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        requestCounter += 1;
        return Response(projectViewResponseFixture, 200);
      });

      await provider.fetchBySlug('home');
      var view = await provider.getBySlug('home');
      expect(view, isNotNull);
      expect(view!.project.slug, equals('home'));

      // Only one API call made.
      expect(requestCounter, equals(1));
      expect(listenerCallCount, greaterThan(0));
    });

    test('fetchBySlug() raises on unknown slug', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        return Response('{"error":"Not found"}', 404);
      });

      expect(provider.fetchBySlug('home'), throwsException);
    });

    test('getBySlug() loads from API and updates task data', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        return Response(projectViewResponseFixture, 200);
      });
      await provider.fetchBySlug('home');

      var view = await provider.getBySlug('home');
      expect(view!.tasks.length, equals(2));
    });

    test('move() makes API request and expires local db', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home/move'));
        return Response(projectViewResponseFixture, 200);
      });

      var project = Project.blank();
      project.id = 1;
      project.slug = 'home';
      project.name = 'Home';
      project.ranking = 1;

      await provider.move(project, 2);

      var db = LocalDatabase();
      var reloaded = await db.projectMap.get('home');
      expect(reloaded, isNull);
    });
  });
}
