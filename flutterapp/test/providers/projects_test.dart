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
  String projectsResponseFixture = """
{"projects": [
  {
    "id": 1,
    "name": "Home",
    "slug": "home",
    "color": 1,
    "ranking": 2,
    "incomplete_task_count": 3,
    "sections": [
      {
        "id": 1,
        "name": "Chores",
        "ranking": 1
      },
      {
        "id": 2,
        "name": "Bills",
        "ranking": 0
      }
    ]
  },
  {
    "id": 2,
    "name": "Work",
    "slug": "work",
    "color": 2,
    "ranking": 1,
    "incomplete_task_count": 3,
    "sections": []
  }
]}
""";
  String projectViewResponseFixture = """
{
  "project": {
    "id": 1,
    "name": "Home",
    "slug": "home",
    "color": 1,
    "ranking": 2,
    "incomplete_task_count": 3,
    "sections": [
      {
        "id": 1,
        "name": "Chores",
        "ranking": 1
      },
      {
        "id": 2,
        "name": "Bills",
        "ranking": 0
      }
    ]
  }
}
""";

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
  });
}
