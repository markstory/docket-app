import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';
import 'package:docket/formatters.dart' as formatters;

class TasksDailyRepo extends Repository<TaskViewData> {
  static const String name = 'today';

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
    late DateTime? date;
    if (viewData.tasks.isNotEmpty) {
      date = viewData.tasks.first.dueOn;
    }
    var key = dateKey(date ?? DateUtils.dateOnly(clock.now()));
    current[key] = viewData.toMap();
    current[key]['updatedAt'] = clock.now().toIso8601String();

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
    var state = this.state;
    if (state == null) {
      return false;
    }
    if (duration == null) {
      return true;
    }
    var key = dateKey(date);
    var taskData = state['data'][key];
    if (taskData == null) {
      return false;
    }
    var updated = taskData["updatedAt"];
    // No updatedAt means we need to refresh.
    if (updated == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
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
    expire();

    notifyListeners();
  }

  /// Expire a single day's data
  Future<void> expireDay(DateTime? date, {bool notify = false}) async {
    if (date == null) {
      return;
    }
    var key = dateKey(date);
    var current = await getMap();
    if (current == null || current[key] == null) {
      return;
    }
    current[key]['updatedAt'] = clock.now().subtract(duration!);
    await setMap(current);

    if (notify) {
      notifyListeners();
    }
  }

  /// Update a task. Will either add/update or remove 
  /// a task from the TasksDaily view depending on the task details.
  /// Will notify on changes.
  Future<void> updateTask(Task task, {bool expire = true}) async {
    var data = await getOrCreate(task.dueOn);
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (task.isToday) {
      // Add or replace depending
      if (index == -1) {
        data.tasks.add(task);
      } else {
        data.tasks.removeAt(index);
        data.tasks.insert(index, task);
      }
    } else if (index != -1) {
      // Task is no longer in today view remove it.
      data.tasks.removeAt(index);
    }
    await set(data);
    if (expire) {
      this.expire();
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
      expire(notify: true);
    }
  }
}
