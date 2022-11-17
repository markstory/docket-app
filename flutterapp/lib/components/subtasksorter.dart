import 'package:flutter/material.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class SubtaskSorter extends StatelessWidget {
  final List<Subtask> items;

  /// Fired when items are reordered.
  final void Function(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) onItemReorder;

  /// Used to render the task list item.
  final Widget Function(Subtask task) buildItem;

  const SubtaskSorter(
      {required this.items,
      required this.onItemReorder,
      required this.buildItem,
      super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);

    return DragAndDropLists(
      disableScrolling: true,
      children: [
        DragAndDropList(
          header: Column(
            children: [
              Row(children: [
                const SizedBox(width: 18),
                Text('Subtasks', style: theme.textTheme.titleLarge),
              ]),
              SizedBox(height: space(1)),
            ]
          ),
          contentsWhenEmpty: buildEmpty(theme),
          canDrag: false,
          children: items.map((task) {
            return DragAndDropItem(child: buildItem(task));
          }).toList(),
        ),
      ],
      itemDecorationWhileDragging: itemDragBoxDecoration(theme),
      itemDragOnLongPress: true,
      onItemReorder: onItemReorder,
      onListReorder: (oldListIndex, newListIndex) => throw Exception("List ordering not supported."),
      lastListTargetSize: 0,
    );
  }

  Widget buildEmpty(ThemeData theme) {
    var docketColors = theme.extension<DocketColors>()!;
    var contents = Text('No subtasks', style: theme.textTheme.titleSmall!.copyWith(color: docketColors.disabledText));

    return Container(
      alignment: Alignment.centerLeft,
      padding: EdgeInsets.fromLTRB(20, space(2.75), 0, 0),
      child: contents,
    );
  }
}
