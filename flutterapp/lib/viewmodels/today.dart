import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/formatters.dart' as formatters;


class TodayViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;
  bool _silentLoading = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  /// Whether loading data failed.
  bool _loadError = false;

  /// Any overdue tasks
  TaskSortMetadata? _overdue;

  TodayViewModel(LocalDatabase database) {
    _taskLists = [];
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

  bool get loading => _loading && !_silentLoading;
  bool get loadError => _loadError;
  DateTime get today => DateUtils.dateOnly(DateTime.now());

  TaskSortMetadata? get overdue => _overdue;
  List<TaskSortMetadata> get taskLists => _taskLists;

  clearLoadError() {
    _loadError = false;
  }

  /// Load data. Should be called during initState()
  /// or when database events are received.
  Future<void> loadData() async {
    // Update to us the upcoming repo.
    var rangeView = await _database.dailyTasks.getDate(today, overdue: true);
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

  /// Refresh tasks from server state. Does not use loading
  /// state.
  Future<void> refreshTasks() async {
    _loading = _silentLoading = true;

    try {
      var rangeView = await actions.fetchDailyTasks(_database.apiToken.token, today, overdue: true);
      _database.dailyTasks.setRange(rangeView);
      _buildTaskLists(rangeView);
    } catch (err) {
      _loadError = true;
      notifyListeners();
    }
  }

  /// Refresh from the server with loading state
  Future<void> refresh() async {
    _loading = true;
    await Future.wait([
      actions.fetchDailyTasks(_database.apiToken.token, today, overdue: true),
      actions.fetchProjects(_database.apiToken.token),
    ]).then((results) {
      var rangeView = results[0] as TaskRangeView;
      var projects = results[1] as List<Project>;

      return Future.wait([
        _database.projectMap.replace(projects),
        _database.dailyTasks.setRange(rangeView),
      ]).then((results) {
        return _buildTaskLists(rangeView);
      });
    }).onError((error, stack) {
      _loadError = true;
      notifyListeners();
    });
  }

  void _buildTaskLists(TaskRangeView rangeView) {
    _overdue = null;
    var overdueData = rangeView.overdue;
    if (overdueData != null) {
      _overdue = TaskSortMetadata(
          iconStyle: TaskSortIcon.warning,
          title: 'Overdue',
          tasks: overdueData.tasks,
          onReceive: (task, newIndex, meta) {
            throw Exception('Cannot move task to overdue');
          });
    }

    _taskLists = [];
    for (var entry in rangeView.entries) {
      var taskView = entry.value;

      var dayTasks = TaskSortMetadata(
          calendarItems: taskView.calendarItems,
          title: _overdue != null ? 'Today' : null,
          tasks: taskView.dayTasks(),
          onReceive: (task, newIndex, meta) {
            var updates = {'evening': false, 'day_order': newIndex};
            task.evening = false;
            task.dayOrder = newIndex;

            if (task.dueOn?.isBefore(today) ?? false) {
              task.dueOn = today;
              updates['due_on'] = formatters.dateString(today);
            }
            return updates;
          });
      _taskLists.add(dayTasks);

      var eveningTasks = TaskSortMetadata(
          iconStyle: TaskSortIcon.evening,
          title: 'This Evening',
          showButton: true,
          buttonArgs: TaskSortButtonArgs(dueOn: today, evening: true),
          tasks: taskView.eveningTasks(),
          onReceive: (task, newIndex, meta) {
            var updates = {'evening': true, 'day_order': newIndex};
            task.evening = true;
            task.dayOrder = newIndex;

            if (task.dueOn?.isBefore(today) ?? false) {
              task.dueOn = today;
              updates['due_on'] = formatters.dateString(today);
            }
            return updates;
          });
      _taskLists.add(eveningTasks);
    }

    _loading = _silentLoading = false;

    notifyListeners();
  }

  /// Move a task out of overdue into another section
  Future<void> moveOverdue(Task task, int listIndex, int itemIndex) async {
    // Calculate position of adding to a end.
    // Generally this will be zero but it is possible to add to the
    // bottom of a populated list too.
    var targetList = _taskLists[listIndex];
    if (itemIndex == -1) {
      itemIndex = targetList.tasks.length;
    }

    // Get the changes that need to be made on the server.
    var sortMeta = _taskLists[listIndex];
    var updates = sortMeta.onReceive(task, itemIndex, sortMeta);
    _overdue?.tasks.remove(task);
    _taskLists[listIndex].tasks.insert(itemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    await _database.updateTask(task);
  }

  /// Reorder a task based on the protocol defined by
  /// the drag_and_drop_lists package.
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
  }
}
