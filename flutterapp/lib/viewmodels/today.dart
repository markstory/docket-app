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
    _database.tasksDaily.addListener(listener);
  }

  @override
  void dispose() {
    _database.tasksDaily.removeListener(listener);
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
    var taskView = await _database.tasksDaily.get(today);
    if (taskView.isEmpty == false) {
      _buildTaskLists(taskView);
    }
    if (!_loading && taskView.isEmpty) {
      return refresh();
    }
    if (!_loading && !_database.tasksDaily.isDayFresh(today)) {
      return refreshTasks();
    }
  }

  /// Refresh tasks from server state. Does not use loading
  /// state.
  Future<void> refreshTasks() async {
    _loading = _silentLoading = true;

    try {
      // TODO update here too
      var taskView = await actions.fetchTasksDaily(_database.apiToken.token, today);
      _database.tasksDaily.set(taskView);
      _buildTaskLists(taskView);
    } catch (err) {
      _loadError = true;
      notifyListeners();
    }
  }

  /// Refresh from the server with loading state
  Future<void> refresh() async {
    _loading = true;
    await Future.wait([
      actions.fetchTasksDaily(_database.apiToken.token, today),
      actions.fetchProjects(_database.apiToken.token),
    ]).then((results) {
      var tasksView = results[0] as TaskViewData;
      var projects = results[1] as List<Project>;

      return Future.wait([
        _database.projectMap.replace(projects),
        // TODO use upcoming repo instead.
        _database.tasksDaily.set(tasksView),
      ]).then((results) {
        // TODO update to list view.
        _buildTaskLists(tasksView);
        // TODO this needs to be on upcoming now, and is likely
        // the source of the disappearing sections
        return _database.tasksDaily.removeOlderThan(today);
      });
    }).onError((error, stack) {
      _loadError = true;
      notifyListeners();
    });
  }

  void _buildTaskLists(TaskViewData data) {
    _overdue = null;
    var overdueTasks = data.tasks.where((task) => task.dueOn?.isBefore(today) ?? false).toList();
    if (overdueTasks.isNotEmpty) {
      _overdue = TaskSortMetadata(
          iconStyle: TaskSortIcon.warning,
          title: 'Overdue',
          tasks: overdueTasks,
          onReceive: (task, newIndex, meta) {
            throw Exception('Cannot move task to overdue');
          });
    }

    // No setState() as we don't want to re-render.
    var todayTasks = TaskSortMetadata(
        calendarItems: data.calendarItems,
        title: _overdue != null ? 'Today' : null,
        tasks: data.tasks.where((task) {
          return !task.evening && !overdueTasks.contains(task);
        }).toList(),
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

    var eveningTasks = TaskSortMetadata(
        iconStyle: TaskSortIcon.evening,
        title: 'This Evening',
        showButton: true,
        buttonArgs: TaskSortButtonArgs(dueOn: today, evening: true),
        tasks: data.tasks.where((task) {
          return task.evening && !overdueTasks.contains(task);
        }).toList(),
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

    _taskLists = [todayTasks, eveningTasks];

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
    _database.expireTask(task);
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
    await _database.tasksDaily.updateTask(task);
    _database.expireTask(task);
  }
}
