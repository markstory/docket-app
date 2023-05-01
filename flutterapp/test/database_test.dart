import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/formatters.dart' as formatters;

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var database = LocalDatabase(inTest: true);
  var today = DateUtils.dateOnly(DateTime.now());
  var tomorrow = today.add(const Duration(days: 1));
  var todayStr = formatters.dateString(today);
  var tomorrowStr = formatters.dateString(tomorrow);

  var project = Project.blank();
  project.id = 1;
  project.slug = 'home';
  project.name = 'Home';

  group('LocalDatabase', () {
    setUp(() async {
      await database.clearSilent();
    });

    test('data read when fresh', () async {
      await database.projectMap.set(project);
      var value = await database.projectMap.get('not-there');
      expect(value, isNull);

      value = await database.projectMap.get('home');
      expect(value, isNotNull);
      expect(value!.slug, equals('home'));
    });

    test('data read when stale', () async {
      await database.projectMap.set(project);
      expect(database.projectMap.isFresh(), isTrue, reason: 'Should be fresh');
      var expires = DateTime.now().add(const Duration(hours: 2));

      withClock(Clock.fixed(expires), () async {
        var value = await database.projectMap.get('home');
        expect(value, isNotNull, reason: 'stale reads are ok');
        expect(database.projectMap.isFresh(), isFalse, reason: 'no longer fresh.');
      });
    });

    test('session data has no expiration', () async {
      var token = ApiToken.fromMap({'token': 'abc123', 'lastUsed': null});
      await database.apiToken.set(token);

      var expires = DateTime.now().add(const Duration(hours: 2));
      withClock(Clock.fixed(expires), () async {
        var value = await database.apiToken.get();
        expect(value, isNotNull);
      });

      expires = DateTime.now().add(const Duration(days: 2));
      withClock(Clock.fixed(expires), () async {
        var value = await database.apiToken.get();
        expect(value, isNotNull);
      });
    });

    test('expireTask() expires dailyTasks', () async {
      var dailyCounter = CallCounter();
      var detailsCounter = CallCounter();
      database.dailyTasks.addListener(dailyCounter);
      database.projectDetails.addListener(detailsCounter);
      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.dueOn = today;

      database.expireTask(task);

      database.dailyTasks.removeListener(dailyCounter);
      database.projectDetails.removeListener(detailsCounter);

      expect(dailyCounter.callCount, equals(1));
      expect(detailsCounter.callCount, equals(1));
      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.projectDetails.isFreshSlug(task.projectSlug), isFalse);
    });

    test('expireTask() expires only dailyTasks', () async {
      var dailyCounter = CallCounter();
      database.dailyTasks.addListener(dailyCounter);
      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.dueOn = tomorrow;

      database.expireTask(task);
      database.dailyTasks.removeListener(dailyCounter);

      expect(dailyCounter.callCount, equals(1));

      // Day is empty and empty ~= expired
      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.dailyTasks.isDayExpired(tomorrow), isTrue);
      expect(database.projectDetails.isFreshSlug(task.projectSlug), isFalse);
    });

    test('expireTask() removes from trashbin', () async {
      var todayCounter = CallCounter();
      var trashCounter = CallCounter();
      database.dailyTasks.addListener(todayCounter);
      database.trashbin.addListener(trashCounter);

      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.dueOn = today;
      task.deletedAt = clock.now();

      database.expireTask(task);
      database.dailyTasks.removeListener(todayCounter);
      database.trashbin.removeListener(trashCounter);

      expect(todayCounter.callCount, equals(1));
      expect(trashCounter.callCount, equals(1));
    });

    test('updateTask() notifies taskDetails', () async {
      var listener = CallCounter();
      // This is important as it ensures that taskDetails refreshes.
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      database.taskDetails.addListener(listener);
      await database.updateTask(task);

      expect(listener.callCount, greaterThan(0));
      database.taskDetails.removeListener(listener);
    });

    test('updateTask() updates daily view with new task', () async {
      var todayListener = CallCounter();
      var task = Task.blank(projectId: project.id);
      task.id = 7;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      database.dailyTasks.addListener(todayListener);
      await database.updateTask(task);

      database.dailyTasks.removeListener(todayListener);
      expect(todayListener.callCount, greaterThan(0));

      var todayData = await database.dailyTasks.getDate(today);
      expect(todayData[todayStr]?.tasks.length, equals(1));
      expect(todayData[todayStr]?.tasks[0].title, equals(task.title));

      // While create is generally safe we should refetch
      // to ensure dayOrder/childOrder are synced.
      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.projectDetails.isFreshSlug(task.projectSlug), isFalse);
    });

    test('updateTask() updates existing daily task', () async {
      var other = Task.blank();
      other.id = 2;
      other.title = 'first task';
      other.dueOn = today;
      other.projectSlug = 'home';
      other.dayOrder = 0;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';
      task.dayOrder = 1;

      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [other, task], calendarItems: [])
      });

      task.title = 'Updated pay bills';
      await database.updateTask(task);

      var todayData = await database.dailyTasks.getDate(today);
      expect(todayData[todayStr]?.tasks.length, equals(2));
      expect(todayData[todayStr]?.tasks[1].title, equals(task.title));

      // View should be expired so that we refetch
      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.dailyTasks.isExpired, isFalse);
    });

    test('updateTask() moves to empty day', () async {
      var other = Task.blank();
      other.id = 2;
      other.dueOn = today;
      other.projectSlug = 'home';

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [other, task], calendarItems: [])
      });

      task.previousDueOn = task.dueOn;
      task.dueOn = tomorrow;
      await database.updateTask(task);

      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.dailyTasks.isExpired, isFalse);

      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr]?.tasks.length, equals(1));
      expect(taskData[todayStr]?.tasks[0].title, equals(other.title));
      expect(taskData[tomorrowStr]?.tasks.length, equals(1));
      expect(taskData[tomorrowStr]?.tasks[0].title, equals(task.title));
    });

    test('updateTask() moves within a day', () async {
      var other = Task.blank();
      other.id = 2;
      other.dueOn = today;
      other.projectSlug = 'home';
      other.dayOrder = 1;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';
      task.dayOrder = 0;

      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [task, other], calendarItems: []),
      });

      task.dayOrder = 1;
      await database.updateTask(task);

      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.dailyTasks.isExpired, isFalse);

      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr]?.tasks.length, equals(2));
      // Moved task should be last.
      expect(taskData[todayStr]?.tasks[0].title, equals(other.title));
      expect(taskData[todayStr]?.tasks[1].title, equals(task.title));
    });

    test('updateTask() moves to occupied day', () async {
      var other = Task.blank();
      other.id = 2;
      other.dueOn = today;
      other.projectSlug = 'home';
      other.dayOrder = 0;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';
      task.dayOrder = 0;

      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [task], calendarItems: []),
        tomorrowStr: TaskViewData(tasks: [other], calendarItems: []),
      });

      task.previousDueOn = task.dueOn;
      task.dueOn = tomorrow;
      task.dayOrder = 0;
      await database.updateTask(task);

      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.dailyTasks.isDayExpired(tomorrow), isTrue);
      expect(database.dailyTasks.isExpired, isFalse);

      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr]?.tasks.length, equals(0));
      expect(taskData[tomorrowStr]?.tasks.length, equals(2));
      // Moved task should be first.
      expect(taskData[tomorrowStr]?.tasks[0].title, equals(task.title));
      expect(taskData[tomorrowStr]?.tasks[1].title, equals(other.title));
    });

    test('updateTask() removes from today with null dueOn value', () async {
      var other = Task.blank();
      other.id = 2;
      other.dueOn = today;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [other, task], calendarItems: [])
      });

      task.previousDueOn = task.dueOn;
      task.dueOn = null;
      await database.updateTask(task);

      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr]?.tasks.length, equals(1));
      expect(taskData[todayStr]?.tasks[0].title, equals(other.title));
      expect(database.dailyTasks.isDayExpired(today), isTrue);
    });

    test('updateTask() adds task to dailyTasks view', () async {
      var callListener = CallCounter();
      var tomorrow = today.add(const Duration(days: 1));
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectSlug = 'home';

      database.dailyTasks.addListener(callListener);
      await database.updateTask(task);

      expect(callListener.callCount, greaterThan(0));
      database.dailyTasks.removeListener(callListener);

      expect(database.dailyTasks.isExpired, isFalse);

      var taskData = await database.dailyTasks.get();
      expect(taskData[tomorrowStr]?.tasks.length, equals(1));
      expect(taskData[tomorrowStr]?.tasks[0].title, equals(task.title));
      expect(taskData[todayStr], isNull);
    });

    test('updateTask() removes task from dailyTasks', () async {
      var callListener = CallCounter();
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.projectSlug = 'home';
      task.dueOn = tomorrow;

      await database.dailyTasks.set({
        tomorrowStr: TaskViewData(tasks: [task], calendarItems: [])
      });

      // Simulate moving from upcoming -> none
      task.dueOn = null;
      task.previousDueOn = tomorrow;

      database.dailyTasks.addListener(callListener);
      await database.updateTask(task);

      expect(callListener.callCount, greaterThan(0));
      database.dailyTasks.removeListener(callListener);

      expect(database.dailyTasks.isExpired, isFalse);
      expect(database.dailyTasks.isDayExpired(tomorrow), isTrue);

      var taskData = await database.dailyTasks.get();
      expect(taskData[tomorrowStr]?.tasks.length, equals(0));
      expect(taskData[todayStr], isNull);
    });

    test('updateTask() inserts tasks into projectDetails', () async {
      await database.projectDetails
          .set(ProjectWithTasks(
            project: Project.fromMap({'slug': 'home', 'id': 1, 'name': 'home'}),
            tasks: []
          ));

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectId = 1;
      task.projectSlug = 'home';

      await database.updateTask(task);

      var home = await database.projectDetails.get('home');
      expect(home.tasks.length, equals(1));
      expect(home.tasks[0].title, equals('Dig up potatoes'));
      expect(database.projectDetails.isFreshSlug('home'), isFalse);
    });

    test('updateTask() moves tasks between projects', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectId = 1;
      task.projectSlug = 'home';

      await database.projectDetails
          .set(ProjectWithTasks(
            project: Project.fromMap({'slug': 'home', 'id': 1, 'name': 'home'}),
            tasks: [task]
          ));
      await database.projectDetails
          .set(ProjectWithTasks(
            project: Project.fromMap({'slug': 'work', 'id': 2, 'name': 'work'}),
            tasks: []
          ));

      // Simulate response data changes from update request.
      task.projectId = 2;
      task.projectSlug = 'work';
      task.title = 'Do accounting';
      task.previousProjectSlug = 'home';

      await database.updateTask(task);

      var home = await database.projectDetails.get('home');
      expect(home.tasks.length, equals(0));
      expect(database.projectDetails.isFreshSlug('home'), isFalse);

      var work = await database.projectDetails.get('work');
      expect(work.tasks.length, equals(1));
      expect(work.tasks[0].title, equals('Do accounting'));
      expect(database.projectDetails.isFreshSlug('work'), isFalse);
    });

    test('createTask() adds task to today view', () async {
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.createTask(task);

      expect(database.dailyTasks.isDayExpired(today), isTrue);

      var todayTasks = await database.dailyTasks.getDate(today);
      expect(todayTasks[todayStr]?.tasks.length, equals(1));
      expect(todayTasks[todayStr]?.tasks[0].title, equals(task.title));
    });

    test('createTask() adds task to taskDaily view', () async {
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectSlug = 'home';

      await database.createTask(task);

      expect(database.dailyTasks.isExpired, isFalse);
      expect(database.dailyTasks.isDayExpired(tomorrow), isTrue);

      var taskData = await database.dailyTasks.get();
      expect(taskData[tomorrowStr]?.tasks.length, equals(1));
      expect(taskData[tomorrowStr]?.tasks[0].title, equals(task.title));
    });

    test('createTask() with no date', () async {
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = null;
      task.projectSlug = 'home';

      await database.createTask(task);

      expect(database.dailyTasks.isExpired, isFalse);
      expect(database.dailyTasks.isDayExpired(today), isTrue);

      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr], isNull);
    });

    test('createTask() adds task to projectDetails view', () async {
      var callListener = CallCounter();
      var project = Project.blank();
      project.id = 4;
      project.slug = 'home';
      await database.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.projectSlug = 'home';

      database.projectDetails.addListener(callListener);
      await database.createTask(task);

      expect(callListener.callCount, greaterThan(0));
      database.projectDetails.removeListener(callListener);

      var details = await database.projectDetails.get(task.projectSlug);
      expect(details.tasks.length, equals(1));
      expect(details.tasks[0].title, equals(task.title));
      expect(database.projectDetails.isFreshSlug('home'), isFalse);
    });

    test('deleteTask() removes from date views', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectId = 1;
      task.projectSlug = 'home';

      await database.taskDetails.set(task);
      await database.dailyTasks.set({
        todayStr: TaskViewData(tasks: [task], calendarItems: [])
      });

      await database.deleteTask(task);
      var taskData = await database.dailyTasks.get();
      expect(taskData[todayStr]?.tasks.length, equals(0));

      var details = await database.taskDetails.get(task.id!);
      expect(details, isNotNull);
    });

    test('deleteTask() removes from project details', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectId = 1;
      task.projectSlug = project.slug;

      await database.projectDetails.set(ProjectWithTasks(project: project, tasks: [task]));

      await database.deleteTask(task);
      var result = await database.projectDetails.get(project.slug);
      expect(result.tasks.length, equals(0));
      expect(database.projectDetails.isFreshSlug(project.slug), isFalse);
    });

    test('undeleteTask() adds task to view and expires trash', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectId = 1;
      task.projectSlug = project.slug;
      task.deletedAt = DateTime.now();

      await database.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

      await database.undeleteTask(task);
      expect(database.trashbin.isExpired, isTrue);
      expect(database.dailyTasks.isDayExpired(today), isTrue);
      expect(database.projectDetails.isFreshSlug(project.slug), isFalse);

      var result = await database.projectDetails.get(project.slug);
      expect(result.tasks.length, equals(1));
      expect(result.tasks[0].deletedAt, isNull);
    });
  });
}
