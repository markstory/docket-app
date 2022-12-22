import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';

class CallCounter {
  int callCount = 0;
  CallCounter(): callCount = 0;

  void call() {
    callCount += 1;
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var database = LocalDatabase.instance();
  var today = DateUtils.dateOnly(DateTime.now());
  var tomorrow = today.add(const Duration(days: 1));

  var project = Project.blank();
  project.id = 1;
  project.slug = 'home';
  project.name = 'Home';

  group('database.LocalViewCache', () {
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

    test('expireTask() expires all views for today task', () async {
      var todayCounter = CallCounter();
      var upcomingCounter = CallCounter();
      var detailsCounter = CallCounter();
      database.today.addListener(todayCounter);
      database.upcoming.addListener(upcomingCounter);
      database.projectDetails.addListener(detailsCounter);
      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.dueOn = today;

      database.expireTask(task);

      database.today.removeListener(todayCounter);
      database.upcoming.removeListener(upcomingCounter);
      database.projectDetails.removeListener(detailsCounter);

      expect(todayCounter.callCount, equals(1));
      expect(upcomingCounter.callCount, equals(1));
      expect(detailsCounter.callCount, equals(1));
      expect(database.today.isExpired, isTrue);
      expect(database.upcoming.isExpired, isTrue);
      expect(database.projectDetails.isExpiredSlug(task.projectSlug), isTrue);
    });

    test('expireTask() expires only upcoming', () async {
      var todayCounter = CallCounter();
      var upcomingCounter = CallCounter();
      database.today.addListener(todayCounter);
      database.upcoming.addListener(upcomingCounter);
      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.dueOn = tomorrow;

      database.expireTask(task);
      database.today.removeListener(todayCounter);
      database.upcoming.removeListener(upcomingCounter);

      expect(todayCounter.callCount, equals(0));
      expect(upcomingCounter.callCount, equals(1));

      expect(database.today.isExpired, isFalse);
      expect(database.upcoming.isExpired, isTrue);
      expect(database.projectDetails.isExpiredSlug(task.projectSlug), isTrue);
    });

    test('expireTask() removes from today when moving out', () async {
      var todayCounter = CallCounter();
      var upcomingCounter = CallCounter();
      database.today.addListener(todayCounter);
      database.upcoming.addListener(upcomingCounter);

      var task = Task.blank();
      task.id = 1;
      task.projectSlug = 'home';
      task.previousDueOn = today;
      task.dueOn = tomorrow;

      database.expireTask(task);
      database.today.removeListener(todayCounter);
      database.upcoming.removeListener(upcomingCounter);

      expect(todayCounter.callCount, equals(1));
      expect(upcomingCounter.callCount, equals(1));
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

    test('updateTask() with new task, updates today & upcoming view', () async {
      var todayListener = CallCounter();
      var upcomingListener = CallCounter();
      var task = Task.blank(projectId: project.id);
      task.id = 7;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      database.upcoming.addListener(upcomingListener);
      database.today.addListener(todayListener);
      await database.updateTask(task);

      database.today.removeListener(todayListener);
      database.today.removeListener(upcomingListener);
      expect(todayListener.callCount, greaterThan(0));
      expect(upcomingListener.callCount, greaterThan(0));

      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(1));
      expect(todayData.tasks[0].title, equals(task.title));

      // While create is generally safe we should refetch
      // to ensure dayOrder/childOrder are synced.
      expect(database.today.isExpired, isTrue);
      expect(database.projectDetails.isExpiredSlug(task.projectSlug), isTrue);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));
      expect(upcoming.tasks[0].title, equals(task.title));
      expect(database.upcoming.isExpired, isTrue);
    });

    test('updateTask() with existing updates today & upcoming', () async {
      var other = Task.blank();
      other.id = 2;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.today.set(TaskViewData(tasks: [other, task], calendarItems: []));

      task.title = 'Updated pay bills';
      await database.updateTask(task);

      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(2));
      expect(todayData.tasks[1].title, equals(task.title));
      // View should be expired so that we refetch
      expect(database.today.isExpired, isTrue);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));
      expect(upcoming.tasks[0].title, equals(task.title));
      // View should be expired so that we refetch
      expect(database.upcoming.isExpired, isTrue);
    });

    test('updateTask() removes from today view', () async {
      var other = Task.blank();
      other.id = 2;

      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Pay bills';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.today.set(TaskViewData(tasks: [other, task], calendarItems: []));

      task.previousDueOn = task.dueOn;
      task.dueOn = tomorrow;
      await database.updateTask(task);

      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(1));
      expect(todayData.tasks[0].title, equals(other.title));
      expect(database.today.isExpired, isTrue);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));
      expect(upcoming.tasks[0].title, equals(task.title));
      expect(database.upcoming.isExpired, isTrue);
    });

    test('updateTask() adds task to upcoming view', () async {
      var callListener = CallCounter();
      var tomorrow = today.add(const Duration(days: 1));
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectSlug = 'home';

      database.upcoming.addListener(callListener);
      await database.updateTask(task);

      expect(callListener.callCount, greaterThan(0));
      database.upcoming.removeListener(callListener);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));
      expect(upcoming.tasks[0].title, equals(task.title));
      expect(database.upcoming.isExpired, isTrue);

      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(0));
      expect(database.today.isExpired, isFalse);
    });

    test('updateTask() removes task from upcoming', () async {
      var callListener = CallCounter();
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.projectSlug = 'home';
      task.dueOn = null;

      // Simulate moving from upcoming -> none
      task.previousDueOn = tomorrow;

      database.upcoming.addListener(callListener);
      await database.updateTask(task);

      expect(callListener.callCount, greaterThan(0));
      database.upcoming.removeListener(callListener);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(0));
      expect(database.upcoming.isExpired, isTrue);

      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(0));
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
      expect(database.projectDetails.isExpiredSlug('home'), isTrue);
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
      expect(database.projectDetails.isExpiredSlug('home'), isTrue);

      var work = await database.projectDetails.get('work');
      expect(work.tasks.length, equals(1));
      expect(work.tasks[0].title, equals('Do accounting'));
      expect(database.projectDetails.isExpiredSlug('work'), isTrue);
    });

    test('createTask() adds task to today view', () async {
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectSlug = 'home';

      await database.createTask(task);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));

      var todayTasks = await database.today.get();
      expect(todayTasks.tasks.length, equals(1));
      expect(todayTasks.tasks[0].title, equals(task.title));
      expect(database.today.isExpired, isTrue);
    });

    test('createTask() adds task to upcoming view', () async {
      var tomorrow = today.add(const Duration(days: 1));
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = tomorrow;
      task.projectSlug = 'home';

      await database.createTask(task);

      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(1));
      expect(upcoming.tasks[0].title, equals(task.title));
      expect(database.upcoming.isExpired, isTrue);
    });

    test('createTask() with no date', () async {
      var task = Task.blank(projectId: project.id);
      task.title = 'Dig up potatoes';
      task.dueOn = null;
      task.projectSlug = 'home';

      await database.createTask(task);

      var taskData = await database.upcoming.get();
      expect(taskData.tasks.length, equals(0));
      expect(database.upcoming.isExpired, isFalse);

      taskData = await database.today.get();
      expect(taskData.tasks.length, equals(0));
      expect(database.today.isExpired, isFalse);
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
      expect(database.projectDetails.isExpiredSlug('home'), isTrue);
    });

    test('deleteTask() removes from date views', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectId = 1;
      task.projectSlug = 'home';

      await database.today.set(TaskViewData(tasks: [task], calendarItems: []));
      await database.upcoming.set(TaskViewData(calendarItems: [], tasks: [task]));

      await database.deleteTask(task);
      var todayData = await database.today.get();
      expect(todayData.tasks.length, equals(0));
      var upcoming = await database.upcoming.get();
      expect(upcoming.tasks.length, equals(0));
    });

    test('deleteTask() removes from task details', () async {
      var task = Task.blank(projectId: project.id);
      task.id = 1;
      task.title = 'Dig up potatoes';
      task.dueOn = today;
      task.projectId = 1;
      task.projectSlug = 'home';

      await database.taskDetails.set(task);

      await database.deleteTask(task);
      var result = await database.taskDetails.get(task.id!);
      expect(result, isNull);
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
      expect(database.projectDetails.isExpiredSlug(project.slug), isTrue);
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
      expect(database.today.isExpired, isTrue);
      expect(database.projectDetails.isExpiredSlug(project.slug), isTrue);

      var result = await database.projectDetails.get(project.slug);
      expect(result.tasks.length, equals(1));
      expect(result.tasks[0].deletedAt, isNull);
    });
  });
}
