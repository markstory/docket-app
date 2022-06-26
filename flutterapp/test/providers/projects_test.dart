import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/projects.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late ProjectsProvider provider;
  int listenerCallCount = 0;
  String apiToken = 'api-token';

  var file = File('test_resources/project_list.json');
  final projectsResponseFixture = file.readAsStringSync();

  file = File('test_resources/project_details.json');
  final projectViewResponseFixture = file.readAsStringSync();

  group('$ProjectsProvider', () {
    setUp(() async {
      var db = LocalDatabase();
      listenerCallCount = 0;
      provider = ProjectsProvider(db)
          ..addListener(() {
            listenerCallCount += 1;
          });
      await provider.clear();
    });

    test('getProjects() loads from the API and then database.', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        requestCounter += 1;
        return Response(projectsResponseFixture, 200);
      });

      await provider.getProjects(apiToken);
      var projects = await provider.getProjects(apiToken);
      expect(listenerCallCount, greaterThan(0));
      expect(requestCounter, equals(1));

      expect(projects.length, equals(2));
      expect(projects[0].slug, equals('work'));
      expect(projects[1].slug, equals('home'));
    });

    test('getProjects() handles error on server error', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects'));
        return Response('{"errors": ["bad things"]}', 400);
      });

      try {
        await provider.getProjects(apiToken);
        fail('Should throw');
      } catch (exc) {
        expect(exc.toString(), contains('Could not load projects'));
      }
    });

    test('getBySlug() loads from the API and database.', () async {
      int requestCounter = 0;
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        requestCounter += 1;
        return Response(projectViewResponseFixture, 200);
      });

      await provider.getBySlug(apiToken, 'home');
      var project = await provider.getBySlug(apiToken, 'home');
      expect(listenerCallCount, greaterThan(0));

      // Only one API call made.
      expect(requestCounter, equals(1));
      expect(project.slug, equals('home'));
    });

    test('getBySlug() raises on unknown slug', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        return Response('{"error":"Not found"}', 404);
      });

      try {
        await provider.getBySlug(apiToken, 'home');
        fail('Should not succeed');
      } catch (exc) {
        expect(exc.toString(), contains('Could not load project'));
      }
    });

    test('getBySlug() loads updates task data', () async {
      actions.client = MockClient((request) async {
        expect(request.url.path, contains('/projects/home'));
        return Response(projectViewResponseFixture, 200);
      });

      await provider.getBySlug(apiToken, 'home');

      var db = LocalDatabase();
      var tasks = await db.fetchProjectTasks('home');
      expect(tasks.length, equals(2));
    });
  });
}
