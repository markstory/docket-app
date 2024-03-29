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

  DateTime get start {
    return DateUtils.dateOnly(DateTime.now());
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
    var rangeView = await _database.dailyTasks.getRange(start);
    if (rangeView.isNotEmpty) {
      _buildTaskLists(rangeView);
    }
    if (!_loading && rangeView.isEmpty) {
      return refresh();
    }
    if (!_loading && rangeView.needsRefresh) {
      return refreshTasks();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var rangeView = await actions.fetchUpcomingTasks(_database.apiToken.token);
    await _database.dailyTasks.setRange(rangeView);
    _buildTaskLists(rangeView);
  }

  /// Refresh tasks from server state. Does not use loading
  /// state.
  Future<void> refreshTasks() async {
    _loading = _silentLoading = true;

    var rangeView = await actions.fetchUpcomingTasks(_database.apiToken.token);
    await _database.dailyTasks.setRange(rangeView);

    _buildTaskLists(rangeView);
  }

  void _buildTaskLists(TaskRangeView rangeView) {
    _taskLists = [];

    for (var entry in rangeView.entries) {
      late TaskSortMetadata metadata;

      var title = formatters.compactDate(entry.key);
      var subtitle = formatters.monthDay(entry.key);
      if (title == subtitle) {
        subtitle = '';
      }
      var taskView = entry.value;

      // Add day section
      metadata = TaskSortMetadata(
          evening: false,
          date: entry.key,
          title: title,
          subtitle: subtitle,
          showButton: true,
          buttonArgs: TaskSortButtonArgs(dueOn: entry.key),
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
              task.previousDueOn = task.dueOn;
              task.dueOn = meta.date;
              updates['due_on'] = meta.date != null ? formatters.dateString(meta.date!) : null;
            }

            return updates;
          });
      _taskLists.add(metadata);

      // Evening sections only have a subtitle and no calendar items.
      var eveningTasks = taskView.eveningTasks();
      if (eveningTasks.isNotEmpty) {
        metadata = TaskSortMetadata(
            evening: true,
            date: entry.key,
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
                task.dueOn = meta.date;
                updates['due_on'] = meta.date != null ? formatters.dateString(meta.date!) : null;
              }

              return updates;
            });
        _taskLists.add(metadata);
      }
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

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    await _database.updateTask(task);
    _database.expireTask(task);
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
    await _database.updateTask(task);
    _database.expireTask(task);
  }
}
