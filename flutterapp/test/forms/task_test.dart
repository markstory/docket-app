import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/forms/task.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var home = Project(id: 1, slug: 'home', name: 'Home', color: 0, ranking: 0);
  var work = Project(id: 2, slug: 'work', name: 'Work', color: 1, ranking: 1);
  var database = LocalDatabase(inTest: true);

  // Rendering helper.
  Widget renderForm(Task task, Future<void> Function(Task task) onSave) {
    return EntryPoint(database: database, child: Scaffold(body: Portal(child: TaskForm(task: task, onSave: onSave))));
  }

  setUpAll(() async {
    await database.clearProjects();
    await database.projectMap.addMany([home, work]);
  });

  group('$TaskForm', () {
    testWidgets('can edit blank task', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
        expect(task.body, equals('Use lots of soap'));

        return Future.value();
      }

      final task = Task.blank();
      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Fill out the title and description
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      // Toggle description and fill it out
      var bodyFinder = find.text('Tap to edit');
      await tester.ensureVisible(bodyFinder);
      await tester.tap(bodyFinder);
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const ValueKey('markdown-input')), 'Use lots of soap');

      // Open the project dropdown and select home
      await tester.tap(find.byKey(const ValueKey('project')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Home').last);
      await tester.pumpAndSettle();

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('can use mention for due date', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
        expect(task.evening, isTrue);

        return Future.value();
      }

      final task = Task.blank();
      expect(task.projectId, isNull);

      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      var title = find.byKey(const ValueKey('title'));
      await tester.enterText(title, 'Do dishes &Tod');
      await tester.pumpAndSettle();

      var mention = find.text('Today').first;
      await tester.tap(mention);
      await tester.pumpAndSettle();

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('can use mention for project', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(2));
        return Future.value();
      }

      final task = Task.blank();
      expect(task.projectId, isNull);

      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      var title = find.byKey(const ValueKey('title'));
      await tester.enterText(title, 'Do dishes #wo');
      await tester.pumpAndSettle();

      var mention = find.text('Work').first;
      await tester.tap(mention);
      await tester.pumpAndSettle();

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('project and date value has a default', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;
        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
        expect(task.dueOn, isNull);
        return Future.value();
      }

      final task = Task.blank();
      expect(task.projectId, isNull);

      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      // Save onSaveCalled is mutated by callback.
      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('cancel does not apply changes', (tester) async {
      Future<void> onSave(Task task) {
        throw "Should not be called";
      }

      final task = Task.blank();
      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Fill out the title
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      // Cancel creation, no changes made
      await tester.tap(find.text('Cancel'));
      expect(task.title, equals(''));
    });

    testWidgets('can edit task with contents', (tester) async {
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;

        expect(task.title, equals('Do dishes'));
        expect(task.projectId, equals(1));
        expect(task.body, equals('Use lots of soap'));
        return Future.value();
      }

      var task = Task.blank();
      task.title = "Original title";
      task.projectId = 2;
      task.body = 'Original notes';
      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Existing data should display.
      expect(find.text('Original title'), findsOneWidget);
      expect(find.text('Original notes'), findsOneWidget);

      // Fill out the title
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      // Fill out body
      var bodyFinder = find.text('Original notes');
      await tester.ensureVisible(bodyFinder);
      await tester.tap(bodyFinder);
      await tester.pumpAndSettle();
      await tester.enterText(find.byKey(const ValueKey('markdown-input')), 'Use lots of soap');

      // Open the project dropdown and select home
      await tester.tap(find.byKey(const ValueKey('project')));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Home').last);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });

    testWidgets('can update due on date', (tester) async {
      var today = DateUtils.dateOnly(DateTime.now());
      var onSaveCalled = false;
      Future<void> onSave(Task task) {
        onSaveCalled = true;

        expect(task.dueOn, equals(today));
        return Future.value();
      }

      var task = Task.blank();
      await tester.pumpWidget(renderForm(task, onSave));
      await tester.pumpAndSettle();

      // Open the due date picker.
      await tester.tap(find.text('Later'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Today').last);
      await tester.pumpAndSettle();

      await tester.tap(find.text('Save'));
      expect(onSaveCalled, equals(true));
    });
  });
}
