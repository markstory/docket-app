import 'package:flutter/material.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/taskitem.dart';
import 'package:docket/components/calendaritemlist.dart';
import 'package:docket/models/calendaritem.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TaskDateSorter extends StatelessWidget {
  final List<TaskSortMetadata> taskLists;

  final TaskSortMetadata? overdue;

  /// Fired when an item moves from overdue to one of the other sections.
  final void Function(DragAndDropItem newItem, int listIndex, int itemIndex) onItemAdd;

  // Fired when items are reordered.
  final void Function(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) onItemReorder;

  const TaskDateSorter(
      {required this.taskLists, required this.onItemAdd, required this.onItemReorder, this.overdue, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);

    return DragAndDropLists(
      children: taskLists.map((taskListMeta) {
        // TODO This is janky AF
        var includeOverdue = taskListMeta.title == 'Today';

        return DragAndDropList(
          header: buildHeader(taskListMeta, theme, includeOverdue: includeOverdue),
          canDrag: false,
          children: taskListMeta.tasks.map((task) {
            return DragAndDropItem(child: TaskItem(task: task, showDate: false, showProject: true));
          }).toList(),
        );
      }).toList(),
      itemDecorationWhileDragging: itemDragBoxDecoration(theme),
      itemDragOnLongPress: true,
      onItemReorder: onItemReorder,
      onListReorder: (int oldIndex, int newIndex) {
        throw 'List reordering not supported';
      },
      onItemAdd: onItemAdd,
    );
  }

  Widget buildOverdue(TaskSortMetadata taskMeta, ThemeData theme, DocketColors customColors) {
    return Column(mainAxisSize: MainAxisSize.min, children: [
      buildTitle(taskMeta, theme, customColors),
      ...taskMeta.tasks.map((task) {
        var taskItem = TaskItem(task: task, showDate: false, showProject: true);
        return Draggable<DragAndDropItem>(
          feedback: SizedBox(width: 300, height: 60, child: Material(child: taskItem)),
          data: DragAndDropItem(child: taskItem),
          child: taskItem,
        );
      }).toList()
    ]);
  }

  /// Render a header for a TaskSortMetadata instance
  Widget buildHeader(TaskSortMetadata taskMeta, ThemeData theme, {includeOverdue = false}) {
    var docketColors = theme.extension<DocketColors>()!;
    List<Widget> children = [];

    if (overdue != null && includeOverdue) {
      children.add(buildOverdue(overdue!, theme, docketColors));
    }
    children.add(buildTitle(taskMeta, theme, docketColors));

    if (taskMeta.calendarItems.isNotEmpty) {
      children.add(CalendarItemList(calendarItems: taskMeta.calendarItems));
      children.add(SizedBox(height: space(2)));
    }

    return Column(mainAxisSize: MainAxisSize.min, children: children);
  }

  Widget buildTitle(TaskSortMetadata taskMeta, ThemeData theme, DocketColors docketColors) {
    List<Widget> children = [];

    children.add(SizedBox(width: space(3)));

    if (taskMeta.icon != null) {
      children
        ..add(taskMeta.icon!)
        ..add(SizedBox(width: space(0.5)));
    }
    if (taskMeta.title != null) {
      children.add(Text(taskMeta.title ?? '', style: theme.textTheme.titleLarge));
    }
    if (taskMeta.subtitle != null) {
      children.add(Text(taskMeta.subtitle ?? '',
          style: theme.textTheme.titleSmall!.copyWith(color: docketColors.secondaryText)));
    }
    if (taskMeta.button != null) {
      children.add(taskMeta.button!);
    }
    return Row(children: children);
  }
}

/// Metadata container for building sortable task lists.
class TaskSortMetadata {
  /// Icon to show on the left of the heading.
  Widget? icon;

  /// Title shown in large bold type.
  String? title;

  /// Title shown beside the title if its present or as the only title.
  /// Rendered with secondary text.
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
    this.calendarItems = const [],
    this.icon,
    this.title,
    this.subtitle,
    this.button,
  });
}
