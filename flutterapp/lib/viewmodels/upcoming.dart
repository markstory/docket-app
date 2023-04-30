import 'package:clock/clock.dart';
import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/formatters.dart' as formatters;


class UpcomingViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;
  bool _silentLoading = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  /// Any overdue tasks
  TaskSortMetadata? _overdue;

  UpcomingViewModel(LocalDatabase database) {
    _database = database;
    _database.dailyTasks.addListener(listener);
  }

  @override
  void dispose() {
    _database.dailyTasks.removeListener(listener);
    super.dispose();
  }

  void listener() {
    loadData();
  }

  bool get loading => (_loading && !_silentLoading);
  TaskSortMetadata? get overdue => _overdue;
  List<TaskSortMetadata> get taskLists => _taskLists;

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    var taskViews = await _database.dailyTasks.get();
    if (taskViews.isNotEmpty) {
      _buildTaskLists(taskViews);
    }
    if (!_loading && taskViews.isEmpty) {
      return refresh();
    }
    if (!_loading && !_database.dailyTasks.isFresh()) {
      return refreshTasks();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var taskViews = await actions.fetchUpcomingTasks(_database.apiToken.token);
    await _database.dailyTasks.set(taskViews);
    _buildTaskLists(taskViews);
  }

  /// Refresh tasks from server state. Does not use loading
  /// state.
  Future<void> refreshTasks() async {
    _loading = _silentLoading = true;

    var taskViews = await actions.fetchUpcomingTasks(_database.apiToken.token);
    _database.dailyTasks.set(taskViews);

    _buildTaskLists(taskViews);
  }

  void _buildTaskLists(DailyTasksData data) {
    _taskLists = [];

    // Our DB data structure doesn't have the start/end times yet.
    // Workaround that by burning CPU.
    DateTime start = clock.now();
    DateTime end = start;
    for (var entry in data.entries) {
      var dateVal = DateTime.parse('${entry.key} 00:00:00');
      if (dateVal.isBefore(start)) {
        start = dateVal;
      }
      if (dateVal.isAfter(end)) {
        end = dateVal;
      }
    }

    var i = 0;
    var current = start;
    while (current.isBefore(end) && i < 50) {
      i = i + 1;
      var dateStr = formatters.dateString(current);

      var taskView = data[dateStr];
      if (taskView == null) {
        current = current.add(const Duration(days: 1));
        continue;
      }

      var eveningTasks = taskView.eveningTasks();

      late TaskSortMetadata metadata;

      var title = formatters.compactDate(current);
      var subtitle = formatters.monthDay(current);
      if (title == subtitle) {
        subtitle = '';
      }

      // Add day section
      metadata = TaskSortMetadata(
          evening: false,
          date: current,
          title: title,
          subtitle: subtitle,
          showButton: true,
          buttonArgs: TaskSortButtonArgs(dueOn: current),
          tasks: taskView.dayTasks(),
          calendarItems: taskView.calendarItems,
          onReceive: (Task task, int newIndex, TaskSortMetadata meta) {
            Map<String, dynamic> updates = {
              'day_order': newIndex,
              'evening': false,
            };
            task.dayOrder = newIndex;
            task.evening = false;

            if (task.dueOn != meta.date) {
              task.previousDueOn = meta.date;
              task.dueOn = meta.date;
            }

            return updates;
          });
      _taskLists.add(metadata);

      // Evening sections only have a subtitle and no calendar items.
      if (eveningTasks.isNotEmpty) {
        metadata = TaskSortMetadata(
            evening: true,
            date: current,
            subtitle: 'Evening',
            tasks: eveningTasks,
            onReceive: (Task task, int newIndex, TaskSortMetadata meta) {
              Map<String, dynamic> updates = {
                'day_order': newIndex,
                'evening': true,
              };
              task.dayOrder = newIndex;
              task.evening = true;

              if (task.dueOn != meta.date) {
                task.previousDueOn = task.dueOn;
                task.dueOn = current;
                updates['due_on'] = formatters.dateString(current);
              }

              return updates;
            });
        _taskLists.add(metadata);
      }

      current = current.add(const Duration(days: 1));
    }

    _loading = _silentLoading = false;

    notifyListeners();
  }

  /// Re-order a task
  Future<void> reorderTask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    var task = _taskLists[oldListIndex].tasks[oldItemIndex];

    // Get the changes that need to be made on the server.
    var sortMeta = _taskLists[newListIndex];
    var updates = sortMeta.onReceive(task, newItemIndex, sortMeta);
    if (oldListIndex != newListIndex) {
      var dueOn = sortMeta.date;
      if (dueOn != null) {
        task.dueOn = dueOn;
        updates['due_on'] = formatters.dateString(dueOn);
      }
    }

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    await _database.updateTask(task);
  }

  Future<void> insertAt(Task task, int listIndex, int itemIndex) async {
    // Calculate position of adding to a end.
    // Generally this will be zero but it is possible to add to the
    // bottom of a populated list too.
    var targetList = _taskLists[listIndex];
    if (itemIndex == -1) {
      itemIndex = targetList.tasks.length;
    }
    // Get the changes that need to be made on the server.
    var updates = targetList.onReceive(task, itemIndex, targetList);
    targetList.tasks.insert(itemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    _database.expireTask(task);
  }
}
