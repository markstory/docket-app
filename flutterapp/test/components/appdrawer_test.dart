import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:network_image_mock/network_image_mock.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/routes.dart';

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

    testWidgets('project items navigate on tap', (tester) async {
      var navigated = false;
      final scaffoldKey = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(EntryPoint(
          database: database,
          routes: {
            Routes.projectDetails: (context) {
              navigated = true;
              var arguments = ModalRoute.of(context)!.settings.arguments as ProjectDetailsArguments;
              expect(arguments.project.slug, equals('work'));

              return const Text('Project View');
            }
          },
          child: Scaffold(
            key: scaffoldKey,
            body: const AppDrawer(),
          )));

      await mockNetworkImagesFor(() async {
        scaffoldKey.currentState!.openDrawer();
        await tester.pumpAndSettle();
        await tester.pump(const Duration(seconds: 1));
      });
      await tester.tap(find.text('Work'));
      await tester.pumpAndSettle();
      expect(navigated, isTrue);
    });

    testWidgets('profile navigates on tap', (tester) async {
      var navigated = false;
      final scaffoldKey = GlobalKey<ScaffoldState>();
      await tester.pumpWidget(EntryPoint(
          database: database,
          routes: {
            Routes.profileSettings: (context) {
              navigated = true;
              return const Text('Profile settings');
            }
          },
          child: Scaffold(
            key: scaffoldKey,
            body: const AppDrawer(),
          )));

      await mockNetworkImagesFor(() async {
        scaffoldKey.currentState!.openDrawer();
        await tester.pumpAndSettle();
        await tester.pump(const Duration(seconds: 1));
      });

      await tester.tap(find.text('mark@example.com'));
      await tester.pumpAndSettle();
      expect(navigated, isTrue);
    });
  });
}
