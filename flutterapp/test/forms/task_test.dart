import 'package:docket/viewmodels/taskadd.dart';
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
  var work = Project(
    id: 2, 
    slug: 'work',
    name: 'Work',
    color: 1,
    ranking: 1,
    sections: [
      Section(id: 1, name: 'Soon', ranking: 0),
      Section(id: 2, name: 'Eventually', ranking: 1),
    ],
  );
  var database = LocalDatabase(inTest: true);
  var viewmodel = TaskAddViewModel(database);

  // Rendering helper.
  Widget renderForm(TaskAddViewModel viewmodel, GlobalKey<FormState> formKey) {
    return EntryPoint(
      database: database, 
      child: Scaffold(
        body: Portal(
          child: SingleChildScrollView(
            child: TaskForm(viewmodel: viewmodel, formKey: formKey)
          )
        )
      )
    );
  }

  setUpAll(() async {
    await database.clearProjects();
    await database.projectMap.addMany([home, work]);
    await database.projectDetails.set(ProjectWithTasks(project: home, tasks: []));
    await database.projectDetails.set(ProjectWithTasks(project: work, tasks: []));
  });

  group('$TaskForm', () {
    setUp(() {
      viewmodel.reset();
    });

    testWidgets('can edit blank task', (tester) async {
      var formKey = GlobalKey<FormState>();

      await tester.pumpWidget(renderForm(viewmodel, formKey));
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

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.title, equals('Do dishes'));
      expect(task.projectId, equals(1));
      expect(task.body, equals('Use lots of soap'));
    });

    testWidgets('can use mention for due date', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      expect(viewmodel.task.projectId, isNull);

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      var title = find.byKey(const ValueKey('title'));
      await tester.enterText(title, 'Do dishes &Tod');
      await tester.pumpAndSettle();

      var mention = find.text('Today').first;
      await tester.tap(mention);
      await tester.pumpAndSettle();

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.title, equals('Do dishes'));
      expect(task.projectId, equals(1));
      expect(task.evening, isTrue);
    });

    testWidgets('can use mention for project', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      expect(viewmodel.task.projectId, isNull);

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      var title = find.byKey(const ValueKey('title'));
      await tester.enterText(title, 'Do dishes #wo');
      await tester.pumpAndSettle();

      var mention = find.text('Work').first;
      await tester.tap(mention);
      await tester.pumpAndSettle();

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.title, equals('Do dishes'));
      expect(task.projectId, equals(2));
    });

    testWidgets('project and date value has a default', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      expect(viewmodel.task.projectId, isNull);

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Fill out the title and use default project and notes
      await tester.enterText(find.byKey(const ValueKey('title')), 'Do dishes');

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.title, equals('Do dishes'));
      expect(task.projectId, equals(1));
      expect(task.dueOn, isNull);
    });

    testWidgets('can edit task with contents', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      viewmodel.task.title = "Original title";
      viewmodel.task.projectId = 2;
      viewmodel.task.body = 'Original notes';
      await tester.pumpWidget(renderForm(viewmodel, formKey));
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

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.title, equals('Do dishes'));
      expect(task.projectId, equals(1));
      expect(task.body, equals('Use lots of soap'));
    });

    testWidgets('can update due on date', (tester) async {
      var today = DateUtils.dateOnly(DateTime.now());
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Open the due date picker.
      await tester.tap(find.text('Later'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Today').last);
      await tester.pumpAndSettle();

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.dueOn, equals(today));
    });

    testWidgets('can set section', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Select a project
      await tester.tap(find.byKey(const ValueKey('project')));
      await tester.pumpAndSettle();

      // Choose the project
      await tester.tap(find.text('Work').last);
      await tester.pumpAndSettle();

      // Select a section
      await tester.tap(find.byKey(const ValueKey('section')));
      await tester.pumpAndSettle();

      // Choose the section
      await tester.tap(find.text('Eventually').last);
      await tester.pumpAndSettle();

      formKey.currentState!.save();
      var task = viewmodel.task;
      expect(task.sectionId, equals(work.sections[1].id));
    });

    testWidgets('change project clears section', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      var task = viewmodel.task;

      task.projectId = 2;
      task.projectSlug = 'work';
      task.sectionId = 1;

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Open project selector
      await tester.tap(find.byKey(const ValueKey('project')));
      await tester.pumpAndSettle();

      // Choose a new project
      await tester.tap(find.text('Home').last);
      await tester.pumpAndSettle();

      // Section input should hide.
      expect(find.byKey(const ValueKey('section')), findsNothing);

      formKey.currentState!.save();
      expect(task.sectionId, isNull, reason: 'Task section should be removed on project change');
    });

    testWidgets('can add substasks to new task', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      // Enter subtask title.
      var subtaskTitle = find.byKey(const ValueKey('new-subtask'));
      await tester.enterText(subtaskTitle, 'New subtask');
      await tester.testTextInput.receiveAction(TextInputAction.done);
      await tester.pumpAndSettle();

      var task = viewmodel.task;
      expect(task.subtasks.length, equals(1));
      expect(task.subtasks[0].title, equals('New subtask'));
    });

    testWidgets('can remove subtasks', (tester) async {
      var formKey = GlobalKey<FormState>();
      viewmodel.reset();
      viewmodel.task.subtasks.add(Subtask(title: 'remove me'));

      await tester.pumpWidget(renderForm(viewmodel, formKey));
      await tester.pumpAndSettle();

      var subtaskTitle = find.text('remove me');
      await tester.tap(subtaskTitle);
      await tester.pumpAndSettle();

      var remove = find.byKey(const ValueKey('remove-subtask'));
      await tester.tap(remove);
      await tester.pumpAndSettle();

      // Confirm removal
      expect(find.text('Are you sure?'), findsOneWidget);
      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(viewmodel.task.subtasks.length, equals(0));
    });
  });
}
