import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences_platform_interface/shared_preferences_async_platform_interface.dart';
import 'package:shared_preferences_platform_interface/in_memory_shared_preferences_async.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/forms/project.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  SharedPreferencesAsyncPlatform.instance = InMemorySharedPreferencesAsync.empty();

  var database = LocalDatabase(inTest: true);

  Widget renderForm(Project project, Future<void> Function(Project project) onSave) {
    return EntryPoint(database: database, child: Scaffold(body: ProjectForm(project: project, onSave: onSave)));
  }

  group('ProjectForm', () {
    setUp(() async {
      return database.clearProjects();
    });

    testWidgets('Can update and save a new project', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Project project) {
        onSaveCalled = true;
        expect(project.name, equals('Home'));
        expect(project.color, equals(8));

        return Future.value();
      }

      final project = Project.blank();
      await tester.pumpWidget(renderForm(project, onSave));

      await tester.enterText(find.byType(TextField), 'Home');

      // Open the dropdown, and select berry
      await tester.tap(find.byKey(const ValueKey('color')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('berry').last);
      await tester.pumpAndSettle();

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('can display existing data and update a project', (tester) async {
      Future<void> onSave(Project project) {
        return Future.value();
      }
      final project = Project.blank();
      project.name = 'Hobbies';
      project.color = 8;

      await tester.pumpWidget(renderForm(project, onSave));

      expect(find.text('Hobbies'), findsOneWidget);
      expect(find.text('berry'), findsOneWidget);
    });

    testWidgets('name is required', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Project project) {
        onSaveCalled = true;
        return Future.value();
      }

      final project = Project.blank();
      project.color = 8;

      await tester.pumpWidget(renderForm(project, onSave));
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(onSaveCalled, equals(false));
      expect(find.text('Project name required'), findsOneWidget);
    });
  });
}
