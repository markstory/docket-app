import 'dart:developer' as developer;

import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:json_cache/json_cache.dart';
import 'package:localstorage/localstorage.dart';

import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';

import 'package:docket/db/apitoken.dart';
import 'package:docket/db/calendarproviderlist.dart';
import 'package:docket/db/calendarproviderdetails.dart';
import 'package:docket/db/completedtasks.dart';
import 'package:docket/db/profile.dart';
import 'package:docket/db/projectarchive.dart';
import 'package:docket/db/projectdetails.dart';
import 'package:docket/db/projectmap.dart';
import 'package:docket/db/taskdetails.dart';
import 'package:docket/db/today.dart';
import 'package:docket/db/trashbin.dart';
import 'package:docket/db/upcoming.dart';

enum TaskCollections {
  today, upcoming, projectDetails, trashBin
}

/// Utility class that makes testing listeners easier.
class CallCounter {
  int callCount = 0;
  CallCounter(): callCount = 0;

  void call() {
    callCount += 1;
  }
}

class LocalDatabase {
  static final LocalDatabase _instance = LocalDatabase();

  // Configuration
  static const String dbName = 'docket-localstorage';

  /// Key used to lazily expire data.
  /// Contains a structure of `{key: timestamp}`
  /// Where key is one of the keys above, and timestamp
  /// is the expiration time for the key.
  static const String expiredKey = 'v1:expired';

  JsonCache? _database;

  late TodayRepo today;
  late UpcomingRepo upcoming;
  late TaskDetailsRepo taskDetails;
  late ProjectMapRepo projectMap;
  late ProjectDetailsRepo projectDetails;
  late ProjectArchiveRepo projectArchive;
  late CompletedTasksRepo completedTasks;
  late TrashbinRepo trashbin;
  late ApiTokenRepo apiToken;
  late ProfileRepo profile;
  late CalendarProviderListRepo calendarList;
  late CalendarProviderDetailsRepo calendarDetails;

  LocalDatabase({bool inTest = false}) {
    var db = database(inTest: inTest);
    today = TodayRepo(db, const Duration(hours: 1));
    upcoming = UpcomingRepo(db, const Duration(hours: 1));
    taskDetails = TaskDetailsRepo(db, const Duration(hours: 1));
    projectMap = ProjectMapRepo(db, const Duration(hours: 1));
    projectDetails = ProjectDetailsRepo(db, const Duration(hours: 1));
    projectArchive = ProjectArchiveRepo(db, const Duration(hours: 1));
    completedTasks = CompletedTasksRepo(db, const Duration(hours: 1));
    trashbin = TrashbinRepo(db, const Duration(hours: 1));
    apiToken = ApiTokenRepo(db, null);
    profile = ProfileRepo(db, const Duration(hours: 1));
    calendarList = CalendarProviderListRepo(db, const Duration(days: 1));
    calendarDetails = CalendarProviderDetailsRepo(db, const Duration(days: 1));
  }

  factory LocalDatabase.instance() {
    return LocalDatabase._instance;
  }

  /// Lazily create the database.
  JsonCache database({bool inTest = false}) {
    if (_database != null) {
      return _database!;
    }
    JsonCache adapter;
    if (inTest) {
      developer.log("! Using fake json_cache backend.");
      adapter = JsonCacheFake();
    } else {
      final LocalStorage storage = LocalStorage(dbName);
      adapter = JsonCacheLocalStorage(storage);
    }
    _database = JsonCacheMem(adapter);
    return _database!;
  }

  /// Locate which date based views a task would be involved in.
  ///
  /// When tasks are added/removed we need to update or expire
  /// the view entries those tasks will be displayed in.
  ///
  /// In a SQL based storage you'd be able to remove/update the row
  /// individually. Because our local database is view-based. We need
  /// custom logic to locate the views and then update those views.
  Set<TaskCollections> _taskViews(Task task) {
    var now = DateUtils.dateOnly(clock.now());
    Set<TaskCollections> views = {};

    // If the task has a due date expire upcoming and possibly
    // today views.
    if (task.dueOn != null) {
      var delta = task.dueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TaskCollections.today);
      }
      views.add(TaskCollections.upcoming);
    }

    if (task.previousDueOn != null) {
      var delta = task.previousDueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TaskCollections.today);
      }
      views.add(TaskCollections.upcoming);
    }

    if (task.deletedAt != null) {
      views.add(TaskCollections.trashBin);
    }

    return views;
  }

  /// Directly set a key. Avoid use outside of tests.
  Future<void> set(String key, Map<String, Object?> value) async {
    await database().refresh(key, value);
  }
  // }}}

  // Task Methods. {{{

  /// Create a task in the local database.
  ///
  /// Each task will added to the relevant date/project
  /// views as well as the task lookup map
  ///
  Future<void> createTask(Task task) async {
    List<Future> futures = [];

    futures.add(taskDetails.set(task));
    futures.add(projectDetails.append(task));
    futures.add(projectMap.increment(task.projectSlug));

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          futures.add(today.append(task));
          break;
        case TaskCollections.upcoming:
          futures.add(upcoming.append(task));
          break;
        default:
          throw Exception('Unknown view to clear "$view"');
      }
    }

    await Future.wait(futures);
  }

  /// Update a task in the local database.
  ///
  /// This will update all task views with the new data.
  Future<void> updateTask(Task task) async {
    List<Future> futures = [];
    futures.add(taskDetails.set(task));
    futures.add(projectDetails.updateTask(task));

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          futures.add(today.updateTask(task, expire: true));
          break;
        case TaskCollections.upcoming:
          futures.add(upcoming.updateTask(task, expire: true));
          break;
        default:
          throw Exception('Unknown view to clear "$view"');
      }
    }
    // TODO update project counters

    await Future.wait(futures);
  }

  /// Remove a task from the local database
  /// Will expire and notify the relevant view caches.
  Future<void> deleteTask(Task task) async {
    var id = task.id;
    if (id == null) {
      return;
    }
    // We intentionally don't delete from task details as
    // completed tasks (which are roughly deleted) still
    // exist.
    List<Future> futures = [];
    futures.add(projectDetails.removeTask(task.projectSlug, task));
    futures.add(projectMap.decrement(task.projectSlug));

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          futures.add(today.removeTask(task));
          break;
        case TaskCollections.upcoming:
          futures.add(upcoming.removeTask(task));
          break;
        default:
          throw Exception('Cannot expire view of $view');
      }
    }
    await Future.wait(futures);

    return expireTask(task);
  }

  Future<void> undeleteTask(Task task) async {
    task.deletedAt = null;
    trashbin.expire(notify: true);

    return updateTask(task);
  }

  /// Expire the views for the relevant task
  /// Will notify each view.
  void expireTask(Task task) {
    projectDetails.expireSlug(task.projectSlug, notify: true);

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          today.expire(notify: true);
          break;
        case TaskCollections.upcoming:
          upcoming.expire(notify: true);
          break;
        case TaskCollections.trashBin:
          trashbin.expire(notify: true);
          break;
        default:
          throw Exception('Cannot expire view of $view');
      }
    }
  }

  // }}}

  // Project methods {{{

  /// Add a list of projects to the local database.
  Future<void> addProjects(List<Project> projects) async {
    await Future.wait(projects.map((item) => projectMap.set(item)).toList());
  }

  /// Update a project in the project list state.
  Future<void> updateProject(Project project) async {
    await Future.wait([
      projectMap.set(project),
      projectDetails.remove(project.slug),
    ]);
  }
  // }}}

  // Clearing methods {{{
  Future<List<void>> clearSilent() async {
    return Future.wait([
      today.clearSilent(),
      upcoming.clearSilent(),
      taskDetails.clearSilent(),
      projectMap.clearSilent(),
      projectDetails.clearSilent(),
      projectArchive.clearSilent(),
      completedTasks.clearSilent(),
      trashbin.clearSilent(),
      profile.clearSilent(),
      calendarList.clearSilent(),
      calendarDetails.clearSilent(),
    ]);
  }

  Future<List<void>> clearTasks() async {
    return Future.wait([
      taskDetails.clear(),
      today.clear(),
      upcoming.clear(),
      projectDetails.clear(),
      completedTasks.clear(),
    ]);
  }

  Future<List<void>> clearProjects() async {
    return Future.wait([
      projectMap.clear(),
      projectDetails.clear(),
      projectArchive.clear(),
      completedTasks.clear(),
    ]);
  }
  // }}}
}
