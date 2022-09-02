import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class SubtaskItem extends StatelessWidget {
  final Task task;
  final Subtask subtask;

  const SubtaskItem({required this.task, required this.subtask, super.key});

  void handleSubtaskComplete(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var navigator = Navigator.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.toggleSubtask(task, subtask);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Subtask Complete'));
      navigator.pop();
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  void handleUpdate(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.updateSubtask(task, subtask);
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    // TODO add inline edit with blur to save.
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
          handleSubtaskComplete(context, task, subtask);
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
