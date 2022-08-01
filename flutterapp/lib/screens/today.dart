import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/calendaritemlist.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/calendaritem.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TodayScreen extends StatefulWidget {
  static const routeName = '/tasks/today';

  const TodayScreen({super.key});

  @override
  State<TodayScreen> createState() => _TodayScreenState();
}

class _TodayScreenState extends State<TodayScreen> {
  List<TaskSortMetadata> _taskLists = [];
  TaskSortMetadata? _overdue;

  @override
  void initState() {
    super.initState();
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchToday();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var theme = Theme.of(context);
        var customColors = getCustomColors(context);
        var today = DateUtils.dateOnly(DateTime.now());

        return Scaffold(
          appBar: AppBar(),
          drawer: const AppDrawer(),
          floatingActionButton: const FloatingCreateTaskButton(),
          body: FutureBuilder<TaskViewData>(
            future: tasksProvider.getToday(),
            builder: (context, snapshot) {
              TaskViewData? data = snapshot.data;
              if (data == null) {
                // Reset internal state but don't re-render
                // as we will rebuild the state when the future resolves.
                _taskLists = [];
                return const LoadingIndicator();
              }

              if (_taskLists.isEmpty) {
                var overdueTasks = data.tasks.where((task) => task.dueOn?.isBefore(today) ?? false).toList();
                if (overdueTasks.isNotEmpty) {
                  _overdue = TaskSortMetadata(
                    icon: Icon(Icons.warning_outlined, color: customColors.actionDelete),
                    title: 'Overdue',
                    tasks: overdueTasks,
                    onReceive: (Task task, int newIndex) {
                      throw 'Cannot move task to overdue';
                    }
                  );
                }

                // No setState() as we don't want to re-render.
                var todayTasks = TaskSortMetadata(
                  icon: Icon(Icons.today, color: customColors.dueToday),
                  title: 'Today',
                  button: TaskAddButton(dueOn: today),
                  calendarItems: data.calendarItems,
                  tasks: data.tasks.where((task) => !task.evening).toList(),
                  onReceive: (Task task, int newIndex) {
                    var updates = {'evening': false, 'day_order': newIndex};
                    task.evening = false;
                    task.dayOrder = newIndex;

                    if (task.dueOn?.isBefore(today) ?? false) {
                      task.dueOn = today;
                      updates['due_on'] = formatters.dateString(today);
                    }
                    return updates;
                  }
                );

                var eveningTasks = TaskSortMetadata(
                  icon: Icon(Icons.bedtime_outlined, color: customColors.dueEvening),
                  title: 'This Evening',
                  button: TaskAddButton(dueOn: today, evening: true),

                  tasks: data.tasks.where((task) => task.evening).toList(),
                  onReceive: (Task task, int newIndex) {
                    var updates = {'evening': true, 'day_order': newIndex};
                    task.evening = true;
                    task.dayOrder = newIndex;

                    if (task.dueOn?.isBefore(today) ?? false) {
                      task.dueOn = today;
                      updates['due_on'] = formatters.dateString(today);
                    }
                    return updates;
                  }
                );

                _taskLists..add(todayTasks)..add(eveningTasks);
              }

              var dragList = DragAndDropLists(
                children: _taskLists.map((taskListMeta) {
                  return DragAndDropList(
                    header: taskListMeta.renderHeader(theme),
                    canDrag: false,
                    children: taskListMeta.tasks.map((task) {
                       return DragAndDropItem(
                         child: TaskItem(
                           task: task, 
                           showDate: false, 
                           showProject: true
                         )
                      );
                    }).toList(),
                  );
                }).toList(),
                itemDecorationWhileDragging: itemDragBoxDecoration(theme),
                itemDragOnLongPress: true,
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
                  tasksProvider.fetchToday();
                },
                onListReorder:(int oldIndex, int newIndex) {
                  throw 'List reordering not supported';
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
                  tasksProvider.fetchToday();
                }
              );

              List<Widget> children = [];
              if (_overdue != null) {
                children.add(
                  Flexible(
                    flex: 2,
                    child: buildOverdue(_overdue!, theme, customColors),
                  )
                );
              }

              children.add(
                Flexible(
                  flex: 10,
                  child: dragList,
                )
              );

              return Column(children: children);
            },
          ),
        );
      }
    );
  }

  Widget buildOverdue(TaskSortMetadata taskMeta, ThemeData theme, DocketColors customColors) {
    return Column(
      children: [
        taskMeta.renderHeader(theme),
        ...taskMeta.tasks.map((task) {
          var taskItem = TaskItem(
           task: task,
           showDate: false,
           showProject: true
          );
          return Draggable<DragAndDropItem>(
            feedback: SizedBox(
              width: 300,
              height: 60,
              child: Material(child: taskItem)
            ),
            data: DragAndDropItem(child: taskItem),
            child: taskItem,
          );
        }).toList()
      ]
    );
  }
}

// TODO find a better home for this view data object. Perhaps it and rendering it can be extracted
// into a widget that takes a list of these?
/// Metadata container for building sortable task lists.
class TaskSortMetadata {

  /// Icon to show on the left of the heading.
  Widget? icon;

  /// Title shown in large bold type.
  String? title;

  /// Title shown smaller with an underline.
  String? subtitle;

  /// Header button shown after title. Can also be a Row
  /// if more than one button is required.
  Widget? button;

  List<Task> tasks;

  List<CalendarItem> calendarItems;

  /// Called when a task is moved into this list.
  /// Expected to return the map of data that needs to be sent to the server.
  final Map<String, dynamic> Function(Task task, int newIndex) onReceive;

  TaskSortMetadata({
    required this.onReceive, 
    this.tasks = const [],
    this.calendarItems = const[],
    this.icon,
    this.title,
    this.subtitle,
    this.button,
  });

  /// Render a header for the 
  Widget renderHeader(ThemeData theme) {
    var docketColors = theme.extension<DocketColors>()!;
    List<Widget> children = [];

    children.add(SizedBox(width: space(3)));

    if (icon != null) {
      children..add(icon!)..add(SizedBox(width: space(0.5)));
    }
    children.add(Text(title ?? '', style: theme.textTheme.titleLarge));
    if (subtitle != null) {
      children.add(
        Text(
          subtitle ?? '',
          style: theme.textTheme.titleSmall!.copyWith(color: docketColors.secondaryText)
        )
      );
    }
    if (button != null) {
      children.add(button!);
    }
    var titleRow = Row(
      children: children
    );
    if (calendarItems.isEmpty) {
      return titleRow;
    }

    return Column(
      children: [
        titleRow,
        CalendarItemList(calendarItems: calendarItems),
        SizedBox(height: space(2)),
      ]
    );
  }
}
