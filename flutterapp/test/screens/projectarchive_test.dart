import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/projectarchive.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_list.json');
  final projectList = file.readAsStringSync();
  var decoded = jsonDecode(projectList) as Map<String, dynamic>;

  group('$ProjectArchiveScreen', () {
    var db = LocalDatabase(inTest: true);
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectMap.replace(projects);
      await db.projectArchive.set(projects);
    });

    testWidgets('shows empty state', (tester) async {
      await db.projectArchive.clearSilent();
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/archived') {
          return Response('{"projects":[]}', 200);
        }
        throw Exception('Request made to unmocked ${request.url.path}');
      });
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const ProjectArchiveScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('No archived projects'), findsOneWidget);
    });

    testWidgets('shows projects', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const ProjectArchiveScreen(),
      ));
      await tester.pumpAndSettle();

      expect(find.text('Home'), findsOneWidget);
      expect(find.text('Work'), findsOneWidget);
    });

    testWidgets('can unarchive', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects') {
          return Response(projectList, 200);
        }
        if (request.url.path == '/projects/home/unarchive') {
          callCount += 1;
          return Response('', 200);
        }
        if (request.url.path == '/projects/archived') {
          return Response(projectList, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: const ProjectArchiveScreen(),
      ));
      await tester.pumpAndSettle();

      // Open context menu
      var menu = find.byKey(const ValueKey('archive-actions')).first;
      await tester.tap(menu);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Un-archive'));
      await tester.pumpAndSettle();
      expect(callCount, equals(1));
    });
  });
}
