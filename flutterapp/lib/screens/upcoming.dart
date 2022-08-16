import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/taskdatesorter.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/grouping.dart' as grouping;

class UpcomingScreen extends StatefulWidget {
  static const routeName = '/tasks/upcoming';

  const UpcomingScreen({super.key});

  @override
  State<UpcomingScreen> createState() => _UpcomingScreenState();
}

class _UpcomingScreenState extends State<UpcomingScreen> {
  List<TaskSortMetadata> _taskLists = [];

  @override
  void initState() {
    super.initState();
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchUpcoming();
  }

  void _buildTaskLists(TaskViewData data) {
    var grouperFunc = grouping.createGrouper(DateTime.now(), 28);
    var grouped = grouperFunc(data.tasks);
    var groupedCalendarItems = grouping.groupCalendarItems(data.calendarItems);

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

        // TODO figure out why calendar items aren't displaying/grouping
        // correctly once scrolling is sorted out.
        metadata = TaskSortMetadata(
            title: title,
            button: TaskAddButton(dueOn: dateVal),
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
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(builder: (context, tasksProvider, child) {
      var taskViewData = tasksProvider.getUpcoming();

      return Scaffold(
          appBar: AppBar(),
          drawer: const AppDrawer(),
          floatingActionButton: const FloatingCreateTaskButton(),
          body: FutureBuilder<TaskViewData>(
            future: taskViewData,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text("Something terrible happened"));
              }
              var data = snapshot.data;
              // Loading state
              if (data == null || data.pending || data.missingData) {
                // Reset internal state but don't re-render
                // as we will rebuild the state when the future resolves.
                _taskLists = [];

                // Reload if we were missing data and not an error
                if (data?.missingData ?? false) {
                  tasksProvider.fetchUpcoming();
                }
                return const LoadingIndicator();
              }

              if (_taskLists.isEmpty) {
                _buildTaskLists(data);
              }

              return TaskDateSorter(
                taskLists: _taskLists,
                onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                  var task = _taskLists[oldListIndex].tasks[oldItemIndex];

                  // Get the changes that need to be made on the server.
                  var updates = _taskLists[newListIndex].onReceive(task, newItemIndex);

                  // Update local state assuming server will be ok.
                  setState(() {
                    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
                    _taskLists[newListIndex].tasks.insert(newItemIndex, task);
                  });

                  // Update the moved task and reload from server async
                  await tasksProvider.move(task, updates);
                  tasksProvider.fetchToday();
                },
                onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
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
                    _taskLists[listIndex].tasks.insert(itemIndex, task);
                  });

                  // Update the moved task and reload from server async
                  await tasksProvider.move(task, updates);
                  tasksProvider.fetchUpcoming();
                }
              );
            }),
          );
    });
  }
}
