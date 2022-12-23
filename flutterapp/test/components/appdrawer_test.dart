import 'dart:convert';
import 'dart:io';

import 'package:docket/models/project.dart';
import 'package:docket/models/userprofile.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:network_image_mock/network_image_mock.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/components/appdrawer.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/profile.json');
  final profileResponse = file.readAsStringSync();

  file = File('test_resources/project_list.json');
  final projectListResponse = file.readAsStringSync();

  var database = LocalDatabase(inTest: true);

  group('$AppDrawer', () {
    var decoded = jsonDecode(profileResponse);
    var profile = UserProfile.fromMap(decoded['user']);

    decoded = jsonDecode(projectListResponse);
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    setUp(() async {
      await database.profile.set(profile);
      await database.projectMap.addMany(projects);
    });

    testWidgets('render drawer', (tester) async {
      final scaffoldKey = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(EntryPoint(
          database: database,
          child: Scaffold(
            key: scaffoldKey,
            body: const AppDrawer(),
          )));

      await mockNetworkImagesFor(() async {
        scaffoldKey.currentState!.openDrawer();
        await tester.pumpAndSettle();
        await tester.pump(const Duration(seconds: 1));
      });

      // Menu items.
      expect(find.text('Today'), findsOneWidget);
      expect(find.text('Upcoming'), findsOneWidget);
      expect(find.text('Add Project'), findsOneWidget);

      // project items
      expect(find.text('Work'), findsOneWidget);
      expect(find.text('Home'), findsOneWidget);

      // User profile
      expect(find.text('mark@example.com'), findsOneWidget);
    });

    // TODO add tests for moving projects
  });
}
