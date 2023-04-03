import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';
import 'package:docket/formatters.dart' as formatters;

class TasksDailyRepo extends Repository<TaskViewData> {
  static const String name = 'today';
  final Map<String, DateTime> _expired = {};

  TasksDailyRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  String dateKey(DateTime date) {
    return formatters.dateString(date);
  }

  /// Refresh the data stored for the 'today' view.
  /// The date value will be inferred from the first task in the list
  /// or the present time will be used.
  @override
  Future<void> set(TaskViewData viewData) async {
    var current = await getMap() ?? {};
    var date = DateUtils.dateOnly(clock.now());
    if (viewData.tasks.isNotEmpty) {
      var dueOn = viewData.tasks.first.dueOn;
      date = dueOn ?? date;
    }
    var key = dateKey(date);
    current[key] = viewData.toMap();
    _expired.remove(key);

    return setMap(current);
  }

  /// Get the view data for a specific date.
  Future<TaskViewData> get(DateTime date) async {
    var key = dateKey(date);
    var current = await getMap();
    if (current == null || current[key] == null) {
      return TaskViewData.blank(isEmpty: true);
    }
    return TaskViewData.fromMap(current[key]);
  }

  /// Check if a daily view is fresh.
  bool isDayFresh(DateTime date) {
    if (state == null) {
      return false;
    }
    if (duration == null) {
      return true;
    }
    var key = dateKey(date);

    return _expired[key] == null;
  }

  /// Check if a daily view is expired
  bool isDayExpired(DateTime date) {
    return !isDayFresh(date);
  }

  Future<TaskViewData> getOrCreate(DateTime? date) async {
    if (date == null) {
      throw Exception('Cannot work with tasks that have no date.');
    }
    return await get(date);
  }

  /// Add a task to the end of the today view. Will notify
  Future<void> append(Task task) async {
    var data = await getOrCreate(task.dueOn);
    data.tasks.add(task);
    await set(data);
    expireDay(task.dueOn);

    notifyListeners();
  }

  /// Expire a single day's data
  Future<void> expireDay(DateTime? date, {bool notify = false}) async {
    if (date == null) {
      return;
    }
    var key = dateKey(date);
    _expired[key] = clock.now();
    if (notify) {
      notifyListeners();
    }
  }

  /// Update a task. Will either add/update or remove 
  /// a task from the TasksDaily view depending on the task details.
  /// Will notify on changes.
  Future<void> updateTask(Task task, {bool expire = true}) async {
    var previousDueOn = task.previousDueOn;
    if (previousDueOn != null) {
      // Remove from the previous view if the task is there.
      // It might not be because the view is new/empty.
      var data = await getOrCreate(previousDueOn);
      var index = data.tasks.indexWhere((item) => item.id == task.id);
      if (index > -1) {
        data.tasks.removeAt(index);
        await set(data);
      }
    }

    // Update or add to the new view.
    var data = await getOrCreate(task.dueOn);
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    // Add or replace depending
    if (index == -1) {
      data.tasks.add(task);
    } else {
      data.tasks.removeAt(index);
      data.tasks.insert(index, task);
    }
    await set(data);
    if (expire) {
      if (previousDueOn != null) {
        expireDay(previousDueOn);
      }
      expireDay(task.dueOn);
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await getOrCreate(task.dueOn);
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      data.tasks.removeAt(index);
      await set(data);
      expireDay(task.dueOn, notify: true);
    }
  }

  /// Remove all day views older than date
  /// Used to garbage collect old data.
  Future<void> removeOlderThan(DateTime date) async {
    var data = await getMap() ?? {};
    List<String> removeKeys = [];
    for (var key in data.keys) {
      var keyDate = formatters.parseToLocal(key);
      if (keyDate.isBefore(date)) {
        removeKeys.add(key);
      }
    }
    if (removeKeys.isNotEmpty) {
      for (var key in removeKeys) {
          data.remove(key);
      }
      await setMap(data);
    }
  }
}
