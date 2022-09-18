import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TaskCheckbox extends StatelessWidget {
  final Task task;
  final void Function()? onComplete;

  const TaskCheckbox(this.task, {this.onComplete, super.key});

  @override
  Widget build(BuildContext context) {
    void _handleCompleted() async {
      var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
      var messenger = ScaffoldMessenger.of(context);
      try {
        await tasksProvider.toggleComplete(task);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Task Completed'));
        if (onComplete != null) {
          onComplete!();
        }
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update task'));
      }
    }

    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    Function(bool?)? onChanged;
    if (task.id != null) {
      onChanged = (bool? value) => _handleCompleted();
    }

    return Checkbox(
      activeColor: customColors.actionComplete,
      checkColor: Colors.white,
      value: task.completed,
      visualDensity: VisualDensity.compact,
      onChanged: onChanged,
    );
  }
}
