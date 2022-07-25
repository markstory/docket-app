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
  var database = LocalDatabase();

  setUp(() async {
    await database.clearProjects();
    await database.addProjects([home, work]);
  });

  group('Create Task', () {
    testWidgets('Renders form for new task and can update the task', (tester) async {
      var onSaveCalled = false;
      void onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
        expect(task.body, equals('Use lots of soap'));
      }
      final task = Task.blank();
      await tester.pumpWidget(EntryPoint(
        database: database,
        child: Scaffold(
          body: TaskForm(task: task, onSave: onSave)
        )
      ));
      // database.addProjects([home, work]);
      await tester.pumpAndSettle();

      // Fill out the title and description
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');
      await tester.enterText(find.byKey(const ValueKey('body')), 'Use lots of soap');

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
