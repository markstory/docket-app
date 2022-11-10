import 'package:flutter/material.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/taskitem.dart';
import 'package:docket/components/calendaritemlist.dart';
import 'package:docket/models/calendaritem.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TaskSorter extends StatelessWidget {
  final List<TaskSortMetadata> taskLists;

  final TaskSortMetadata? overdue;

  /// Fired when an item moves from overdue to one of the other sections.
  final void Function(DragAndDropItem newItem, int listIndex, int itemIndex) onItemAdd;

  /// Fired when items are reordered.
  final void Function(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) onItemReorder;

  /// Used to render the task list item.
  final Widget Function(Task task) buildItem;

  /// Fired when a list is reordered.
  final void Function(int oldListIndex, int newListIndex)? onListReorder;

  /// Customize the header rendering.
  final Widget Function(TaskSortMetadata metadata)? buildHeader;

  const TaskSorter(
      {required this.taskLists,
      required this.onItemAdd,
      required this.onItemReorder,
      required this.buildItem,
      this.onListReorder,
      this.overdue,
      this.buildHeader,
      super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);

    return DragAndDropLists(
      children: taskLists.map((taskListMeta) {
        var includeOverdue = (overdue?.tasks.isNotEmpty ?? false)  && taskLists.indexOf(taskListMeta) == 0;

        late Widget header;
        if (buildHeader != null) {
          header = buildHeader!(taskListMeta);
        } else {
          header = buildHeaderDefault(taskListMeta, theme, includeOverdue: includeOverdue);
        }
        return DragAndDropList(
          header: header,
          contentsWhenEmpty: buildEmpty(theme),
          canDrag: taskListMeta.canDrag,
          children: taskListMeta.tasks.map((task) {
            return DragAndDropItem(child: buildItem(task));
          }).toList(),
          lastTarget: SizedBox(height: space(3)),
        );
      }).toList(),
      itemDecorationWhileDragging: itemDragBoxDecoration(theme),
      itemDragOnLongPress: true,
      onItemReorder: onItemReorder,
      onListReorder: onListReorder ?? (int n, int o) => throw "provider onListReorder to sort lists.",
      onItemAdd: onItemAdd,
      lastItemTargetHeight: space(3),
    );
  }

  Widget buildEmpty(ThemeData theme) {
    var docketColors = theme.extension<DocketColors>()!;
    // TODO make contents dynamic based on the current metadata information
    var contents = Text('No tasks', style: theme.textTheme.titleSmall!.copyWith(color: docketColors.disabledText));

    return Padding(
      padding: EdgeInsets.symmetric(vertical: space(2)),
      child: contents,
    );
  }

  Widget buildOverdue(TaskSortMetadata taskMeta, ThemeData theme, DocketColors customColors) {
    return Column(mainAxisSize: MainAxisSize.min, children: [
      SizedBox(height: space(2)),
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
  Widget buildHeaderDefault(TaskSortMetadata taskMeta, ThemeData theme, {includeOverdue = false}) {
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
    List<Widget> text = [];

    children.add(SizedBox(width: space(2.5)));

    if (taskMeta.icon != null) {
      children
        ..add(taskMeta.icon!)
        ..add(SizedBox(width: space(0.5)));
    }
    if (taskMeta.title != null) {
      text.add(Text(taskMeta.title ?? '', style: theme.textTheme.titleLarge));
      text.add(SizedBox(width: space(1)));
    }
    if (taskMeta.subtitle != null) {
      text.add(Text(taskMeta.subtitle ?? '',
          style: theme.textTheme.titleSmall!.copyWith(color: docketColors.secondaryText)));
    }
    if (text.isNotEmpty) {
      children.add(Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: text,
      ));
    }

    if (taskMeta.button != null) {
      children.add(taskMeta.button!);
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: children
    );
  }
}

// Need to use this to set the icons instead of icon components.
enum TaskSortIcon {
  warning,
  evening,
  none,
}

/// Metadata container for building sortable task lists.
class TaskSortMetadata<T> {
  /// Enable if a task group can be dragsorted as well.
  final bool canDrag;

  /// Additional data related to the sorting group.
  /// Used for containing the Section on project details.
  final T? data;

  /// Icon to show on the left of the heading.
  /// TODO make into TaskSortIcon
  final Widget? icon;

  /// Title shown in large bold type.
  final String? title;

  /// Title shown beside the title if its present or as the only title.
  /// Rendered with secondary text.
  final String? subtitle;

  /// Header button shown after title. Can also be a Row
  /// if more than one button is required.
  final Widget? button;

  final List<Task> tasks;

  final List<CalendarItem> calendarItems;

  /// Called when a task is moved into this list.
  /// Expected to return the map of data that needs to be sent to the server.
  final Map<String, dynamic> Function(Task task, int newIndex) onReceive;

  TaskSortMetadata({
    required this.onReceive,
    this.icon,
    this.title,
    this.subtitle,
    this.button,
    this.data,
    this.tasks = const [],
    this.calendarItems = const [],
    this.canDrag = false,
  });
}
