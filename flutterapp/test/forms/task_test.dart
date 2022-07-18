import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/forms/task.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var home = Project(id: 1, slug: 'home', name: 'Home', color: 0, ranking: 0);
  var work = Project(id: 2, slug: 'work', name: 'Work', color: 1, ranking: 1);

  group('Create Task', () {
    testWidgets('Renders an empty form', (tester) async {
      var database = LocalDatabase();
      // TODO this should wait for the db to update.
      database.addProjects([home, work]);

      var onSaveCalled = false;
      void onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
      }
      final task = Task.blank();
      await tester.pumpWidget(EntryPoint(
        database: database,
        child: Scaffold(
          body: TaskForm(task: task, onSave: onSave)
        )
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      // Open the project dropdown and select home
      await tester.tap(find.byKey(const ValueKey('project')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Home').last);
      await tester.pumpAndSettle();

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });
  });
}
