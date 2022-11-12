import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/formatters.dart' as formatters;


class TodayViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  /// Any overdue tasks
  TaskSortMetadata? _overdue;

  TodayViewModel(LocalDatabase database, this.session) {
    _database = database;
    _taskLists = [];
  }

  bool get loading => _loading;
  TaskSortMetadata? get overdue => _overdue;
  List<TaskSortMetadata> get taskLists => _taskLists;

  setLoading(bool loading) async {
    _loading = loading;

    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    var taskView = await _database.today.get();
    if (taskView.missingData == false) {
      _buildTaskLists(taskView);
    }
    if (taskView.missingData && _loading == false) {
      return refresh();
    }
  }

  /// Re-order a task
  Future<void> reorderTask(int oldListIndex, int oldItemIndex, int newListIndex, int newItemIndex) async {
    var task = _taskLists[oldListIndex].tasks[oldItemIndex];

    // Get the changes that need to be made on the server.
    var updates = _taskLists[newListIndex].onReceive(task, newItemIndex);

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);
    notifyListeners();

    // Update the moved task and reload from server async
    return actions.moveTask(session!.apiToken, task, updates);
  }

  /// Move a task out of overdue into another section
  Future<void> moveOverdue(Task task, int listIndex, int itemIndex) async {
    if (_overdue == null) {
      throw 'Should not receive items when _overdue is null';
    }

    // Calculate position of adding to a end.
    // Generally this will be zero but it is possible to add to the
    // bottom of a populated list too.
    var targetList = _taskLists[listIndex];
    if (itemIndex == -1) {
      itemIndex = targetList.tasks.length;
    }

    // Get the changes that need to be made on the server.
    var updates = _taskLists[listIndex].onReceive(task, itemIndex);
    _overdue?.tasks.remove(task);
    _taskLists[listIndex].tasks.insert(itemIndex, task);
    notifyListeners();

    // Update the moved task and reload from server async
    return actions.moveTask(session!.apiToken, task, updates);
  }

  Future<void> refresh() async {
    setLoading(true);
    var result = await Future.wait([
      actions.fetchTodayTasks(session!.apiToken),
      actions.fetchProjects(session!.apiToken),
    ]);
    var tasksView = result[0] as TaskViewData;
    var projects = result[1] as List<Project>;

    _database.projectMap.replace(projects);
    _buildTaskLists(tasksView);
    setLoading(false);
  }

  void _buildTaskLists(TaskViewData data) {
    var today = DateUtils.dateOnly(DateTime.now());

    var overdueTasks = data.tasks.where((task) => task.dueOn?.isBefore(today) ?? false).toList();
    if (overdueTasks.isNotEmpty) {
      _overdue = TaskSortMetadata(
          iconStyle: TaskSortIcon.warning,
          title: 'Overdue',
          tasks: overdueTasks,
          onReceive: (Task task, int newIndex) {
            throw 'Cannot move task to overdue';
          });
    }

    // No setState() as we don't want to re-render.
    var todayTasks = TaskSortMetadata(
        calendarItems: data.calendarItems,
        tasks: data.tasks.where((task) {
          return !task.evening && !overdueTasks.contains(task);
        }).toList(),
        onReceive: (Task task, int newIndex) {
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
        buttonArgs: {"dueOn": today, "evening": true},
        tasks: data.tasks.where((task) {
          return task.evening && !overdueTasks.contains(task);
        }).toList(),
        onReceive: (Task task, int newIndex) {
          var updates = {'evening': true, 'day_order': newIndex};
          task.evening = true;
          task.dayOrder = newIndex;

          if (task.dueOn?.isBefore(today) ?? false) {
            task.dueOn = today;
            updates['due_on'] = formatters.dateString(today);
          }
          return updates;
        });

    _taskLists
      ..add(todayTasks)
      ..add(eveningTasks);

    notifyListeners();
  }
}
