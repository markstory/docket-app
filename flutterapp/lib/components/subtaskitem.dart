import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class SubtaskItem extends StatelessWidget {
  final Task task;
  final Subtask subtask;
  final void Function(Subtask subtask)? onUpdate;
  final void Function(Subtask subtask)? onToggle;

  const SubtaskItem({required this.task, required this.subtask, this.onUpdate, this.onToggle, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return ListTile(
      dense: true,
      leading: Checkbox(
        activeColor: customColors.actionComplete,
        checkColor: Colors.white,
        value: subtask.completed,
        visualDensity: VisualDensity.compact,
        onChanged: (bool? value) {
          if (value == null) {
            return;
          }
          subtask.completed = value;
          if (onToggle != null) {
            onToggle!(subtask);
          }
        }),
      title: Text(
        subtask.title,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          color: subtask.completed ? Colors.grey : Colors.black,
          decoration: subtask.completed ? TextDecoration.lineThrough : null,
        ),
      ),
    );
  }
}
