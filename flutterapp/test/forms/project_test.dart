import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/forms/project.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Create Project', () {
    testWidgets('Renders an empty form', (tester) async {
      var database = LocalDatabase();

      var onSaveCalled = false;
      void onSave(Project project) {
        onSaveCalled = true;
        expect(project.name, equals('Home'));
        expect(project.color, equals(8));
      }
      final project = Project.blank();
      await tester.pumpWidget(EntryPoint(
        database: database,
        child: Scaffold(
          body: ProjectForm(project: project, onSave: onSave)
        )
      ));

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

    testWidgets('Renders an edit form', (tester) async {
      var database = LocalDatabase();

      void onSave(Project project) {}
      final project = Project.blank();
      project.name = 'Hobbies';
      project.color = 8;

      await tester.pumpWidget(EntryPoint(
        database: database,
        child: Scaffold(
          body: ProjectForm(project: project, onSave: onSave)
        )
      ));

      expect(find.text('Hobbies'), findsOneWidget);
      expect(find.text('berry'), findsOneWidget);
    });

    testWidgets('Name required', (tester) async {
      var database = LocalDatabase();

      var onSaveCalled = false;
      void onSave(Project project) {
        onSaveCalled = true;
      }
      final project = Project.blank();
      project.color = 8;

      await tester.pumpWidget(EntryPoint(
        database: database,
        child: Scaffold(
          body: ProjectForm(project: project, onSave: onSave)
        )
      ));

      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(onSaveCalled, equals(false));
      expect(find.text('Project name required'), findsOneWidget);
    });
  });
}
