import 'package:clock/clock.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';
import 'package:docket/formatters.dart' as formatters;

/// Task storage for the day views (today + upcoming)
/// Has special keys for `overdue` tasks and methods to incrementally
/// garbage collect previous day buckets.
class DailyTasksRepo extends Repository<DailyTasksData> {
  static const String name = 'dailytasks';
  final Map<String, DateTime> _lastUpdate = {};

  DailyTasksRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  String dateKey(DateTime date) {
    return formatters.dateString(date);
  }

  /// Set the provided data into the repo.
  /// Will overwrite any keys present in `data`
  /// with the provided collections.
  @override
  Future<void> set(DailyTasksData data) async {
    var current = await getMap() ?? {};
    data.forEach((key, dayTasks) {
      _lastUpdate[key] = clock.now();
      current[key] = dayTasks.toMap();
    });

    return setMap(current);
  }

  /// Set the view data for a single day
  Future<void> setDay(DateTime day, TaskViewData data) async {
    var current = await getMap() ?? {};
    var key = dateKey(day);
    current[key] = data;

    return setMap(current);
  }

  /// Get all stored data. If no data is available an empty list
  /// will be returned.
  Future<DailyTasksData> get() async {
    var data = await getMap();
    if (data == null || data.isEmpty || data.runtimeType == List) {
      return {};
    }
    DailyTasksData result = {};
    data.forEach((key, viewData) {
      result[key] = TaskViewData.fromMap(viewData);
    });

    return result;
  }

  /// Read the tasks for a single date.
  /// Use `overdue` to also include tasks in the overdue bucket.
  Future<DailyTasksData> getDate(DateTime date, {bool overdue = false}) async {
    var data = await getMap();
    if (data == null || data.isEmpty) {
      return {};
    }
    DailyTasksData result = {};
    if (overdue && data.containsKey(TaskViewData.overdueKey)) {
      result[TaskViewData.overdueKey] = TaskViewData.fromMap(data[TaskViewData.overdueKey]);
    }
    var key = dateKey(date);
    if (data.containsKey(key)) {
      result[key] = TaskViewData.fromMap(data[key]);
    }

    return result;
  }

  /// Get a list of dates starting from `start` and continuing for `days`
  /// If there are holes in the data, empty TaskViews will be inserted.
  Future<TaskRangeView> getRange(DateTime start, {bool overdue = false, int days = 28}) async {
    var end = start.add(Duration(days: days));
    var data = await getMap();
    if (data == null || data.isEmpty) {
      return TaskRangeView.blank(start: start, days: days);
    }
    TaskViewData? overdueView;
    if (overdue && data.containsKey(TaskViewData.overdueKey)) {
      overdueView = data[TaskViewData.overdueKey];
    }

    List<TaskViewData> views = [];
    var current = start;
    while (current.isBefore(end) || current == end) {
      var datekey = formatters.dateString(current);
      if (data.containsKey(datekey)) {
        views.add(TaskViewData.fromMap(data[datekey]));
      } else {
        views.add(TaskViewData(tasks: [], calendarItems: []));
      }
      current = current.add(const Duration(days: 1));
    }

    return TaskRangeView(
      start: start,
      days: days,
      overdue: overdueView,
      views: views,
    );
  }

  /// Add a task to the collection
  Future<void> append(Task task) async {
    var data = await get();

    var taskDate = task.dateKey;
    var dateView = data[taskDate] ?? TaskViewData(tasks: [task], calendarItems: []);
    data[taskDate] = dateView;

    await set(data);
    expireDay(task.dueOn, notify: false);

    notifyListeners();
  }

  // Update a task. Will either add/remove/update the
  // task based on its state. Will notify on changes.
  Future<void> updateTask(Task task, {expire = true}) async {
    var data = await get();

    var changed = false;
    var previousDue = task.previousDueOn;
    if (previousDue != null) {
      var previousKey = formatters.dateString(previousDue);
      var previousView = data[previousKey] ?? TaskViewData(tasks: [task], calendarItems: []);

      // Moved between days, remove from old view.
      previousView.tasks.removeWhere((item) => item.id == task.id);
      data[previousKey] = previousView;
      changed = true;
    }

    var dueOn = task.dueOn;
    if (dueOn != null) {
      changed = true;
      var taskDate = formatters.dateString(dueOn);
      var dateView = data[taskDate] ?? TaskViewData(tasks: [], calendarItems: []);

      // Remove from the new view as we might not have a day change.
      var currentIndex = dateView.tasks.indexWhere((item) => item.id == task.id);
      if (currentIndex > -1) {
        dateView.tasks.removeAt(currentIndex);
      }

      if (task.dayOrder <= dateView.tasks.length) {
        dateView.tasks.insert(task.dayOrder, task);
      } else {
        dateView.tasks.add(task);
      }

      data[taskDate] = dateView;
    }

    if (changed) {
      await set(data);
    }

    if (expire) {
      expireDay(previousDue, notify: false);
      expireDay(task.dueOn, notify: false);
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await get();

    var taskDate = task.dateKey;
    var dateView = data[taskDate] ?? TaskViewData(tasks: [task], calendarItems: []);

    var index = dateView.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      dateView.tasks.removeAt(index);
      data[taskDate] = dateView;

      await set(data);

      expireDay(task.dueOn, notify: true);
    }
  }

  /// Remove all day views older than date
  /// Used to garbage collect old data.
  /// Will not remove 'overdue' as that day view is special.
  Future<void> removeOlderThan(DateTime date) async {
    var data = await getMap() ?? {};
    List<String> removeKeys = [];
    for (var key in data.keys) {
      if (key == TaskViewData.overdueKey) {
        continue;
      }
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

  /// Check if a daily view is fresh.
  bool isDayFresh(DateTime date) {
    if (state == null || duration == null) {
      return false;
    }
    var key = dateKey(date);
    var lastUpdate = _lastUpdate[key];
    if (lastUpdate == null) {
      return false;
    }
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return lastUpdate.isAfter(expires);
  }

  /// Check if a daily view is expired
  bool isDayExpired(DateTime date) {
    return !isDayFresh(date);
  }


  /// Expire a single day's data
  Future<void> expireDay(DateTime? date, {bool notify = false}) async {
    if (date == null) {
      return;
    }
    var key = dateKey(date);
    _lastUpdate.remove(key);
    if (notify) {
      notifyListeners();
    }
  }

}
