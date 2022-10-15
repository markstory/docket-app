import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/providers/tasks.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TodayScreen extends StatefulWidget {
  const TodayScreen({super.key});

  @override
  State<TodayScreen> createState() => _TodayScreenState();
}

class _TodayScreenState extends State<TodayScreen> {
  List<TaskSortMetadata> _taskLists = [];
  TaskSortMetadata? _overdue;
  Task? _newTask;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<List<void>> _refresh() async {
    var today = DateUtils.dateOnly(DateTime.now());
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    _newTask = Task.blank(dueOn: today);
    _taskLists = [];
    _overdue = null;

    return Future.wait([
      tasksProvider.fetchToday(),
      projectsProvider.fetchProjects(),
    ]);
  }

  void _buildTaskLists(TaskViewData data) {
    var today = DateUtils.dateOnly(DateTime.now());
    var customColors = getCustomColors(context);

    var overdueTasks = data.tasks.where((task) => task.dueOn?.isBefore(today) ?? false).toList();
    if (overdueTasks.isNotEmpty) {
      _overdue = TaskSortMetadata(
          icon: Icon(Icons.warning_outlined, color: customColors.actionDelete),
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
        icon: Icon(Icons.bedtime_outlined, color: customColors.dueEvening),
        title: 'This Evening',
        button: TaskAddButton(dueOn: today, evening: true),
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
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(builder: (context, tasksProvider, child) {
      return Scaffold(
        appBar: AppBar(
          title: const Text('Today'),
        ),
        drawer: const AppDrawer(),
        floatingActionButton: FloatingCreateTaskButton(task: _newTask),
        body: FutureBuilder<TaskViewData>(
          future: tasksProvider.getToday(),
          builder: (context, snapshot) {
            if (snapshot.hasError) {
              return const Card(child: Text("Something terrible happened"));
            }
            var data = snapshot.data;

            // Loading state
            if (data == null || data.pending || data.missingData) {
              _taskLists = [];
              return const LoadingIndicator();
            }

            if (_taskLists.isEmpty) {
              _buildTaskLists(data);
            }

            return RefreshIndicator(
                onRefresh: _refresh,
                child: TaskSorter(
                    taskLists: _taskLists,
                    overdue: _overdue,
                    buildItem: (Task task) {
                      return TaskItem(task: task, showProject: true);
                    },
                    onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                      var task = _taskLists[oldListIndex].tasks[oldItemIndex];

                      // Get the changes that need to be made on the server.
                      var updates = _taskLists[oldListIndex].onReceive(task, newItemIndex);

                      // Update local state assuming server will be ok.
                      setState(() {
                        _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
                        _taskLists[newListIndex].tasks.insert(newItemIndex, task);
                      });

                      // Update the moved task and reload from server async
                      await tasksProvider.move(task, updates);
                    },
                    onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
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

                      var itemChild = newItem.child as TaskItem;
                      var task = itemChild.task;

                      // Get the changes that need to be made on the server.
                      var updates = _taskLists[listIndex].onReceive(task, itemIndex);
                      setState(() {
                        _overdue?.tasks.remove(task);
                        _taskLists[listIndex].tasks.insert(itemIndex, task);
                      });

                      // Update the moved task and reload from server async
                      await tasksProvider.move(task, updates);
                    }));
          },
        ),
      );
    });
  }
}
