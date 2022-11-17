import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/grouping.dart' as grouping;


class UpcomingViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Whether or not local data has been reset and we need to reload.
  bool _shouldReload = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  /// Any overdue tasks
  TaskSortMetadata? _overdue;

  UpcomingViewModel(LocalDatabase database, this.session) {
    _database = database;

    _database.upcoming.addListener(() async {
      _shouldReload = true;
      loadData();
    });
  }

  bool get loading => _loading;
  TaskSortMetadata? get overdue => _overdue;
  List<TaskSortMetadata> get taskLists => _taskLists;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    var taskView = await _database.upcoming.get();
    if (taskView.missingData == false) {
      _buildTaskLists(taskView);
    }
    if (_shouldReload || (taskView.missingData && !_loading)) {
      return refresh();
    }
  }

  /// Re-order a task
  Future<void> reorderTask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    var task = _taskLists[oldListIndex].tasks[oldItemIndex];

    // Get the changes that need to be made on the server.
    var updates = _taskLists[newListIndex].onReceive(task, newItemIndex);

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(session!.apiToken, task, updates);

    notifyListeners();
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
    var updates = _taskLists[listIndex].onReceive(task, itemIndex);
    _taskLists[listIndex].tasks.insert(itemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(session!.apiToken, task, updates);

    notifyListeners();
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;
    _shouldReload = false;

    var tasksView = await actions.fetchUpcomingTasks(session!.apiToken);
    await _database.upcoming.set(tasksView);
    _buildTaskLists(tasksView);
  }

  void _buildTaskLists(TaskViewData data) {
    var grouperFunc = grouping.createGrouper(DateTime.now(), 28);
    var grouped = grouperFunc(data.tasks);
    var groupedCalendarItems = grouping.groupCalendarItems(data.calendarItems);

    _taskLists = [];
    for (var group in grouped) {
      var groupDate = group.key;
      var isEvening = groupDate.contains('evening:');
      if (isEvening) {
        groupDate = groupDate.replaceFirst('evening:', '');
      }
      var dateVal = DateTime.parse('$groupDate 00:00:00');

      late TaskSortMetadata metadata;
      if (isEvening) {
        // Evening sections only have a subtitle and no calendar items.
        metadata = TaskSortMetadata(
            subtitle: 'Evening',
            tasks: group.items,
            onReceive: (Task task, int newIndex) {
              task.evening = true;
              task.dayOrder = newIndex;
              task.dueOn = dateVal;

              return {'evening': true, 'day_order': newIndex, 'due_on': formatters.dateString(dateVal)};
            });
      } else {
        var title = formatters.compactDate(dateVal);
        var subtitle = formatters.monthDay(dateVal);
        if (title == subtitle) {
          subtitle = '';
        }

        metadata = TaskSortMetadata(
            title: title,
            subtitle: subtitle,
            showButton: true,
            buttonArgs: TaskSortButtonArgs(dueOn: dateVal),
            tasks: group.items,
            calendarItems: groupedCalendarItems.get(groupDate),
            onReceive: (Task task, int newIndex) {
              task.evening = false;
              task.dayOrder = newIndex;
              task.dueOn = dateVal;

              return {'evening': false, 'day_order': newIndex, 'due_on': formatters.dateString(dateVal)};
            });
      }
      _taskLists.add(metadata);
    }

    _loading = false;

    notifyListeners();
  }
}
