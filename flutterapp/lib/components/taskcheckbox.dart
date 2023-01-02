import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TaskCheckbox extends StatelessWidget {
  final Task task;

  /// Fired when the task state is changed on the server.
  final void Function()? onComplete;

  const TaskCheckbox(this.task, {this.onComplete, super.key});

  @override
  Widget build(BuildContext context) {
    void _handleCompleted() async {
      var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
      var messenger = ScaffoldMessenger.of(context);
      var theme = Theme.of(context);

      try {
        await tasksProvider.toggleComplete(task, wait: const Duration(seconds: 1));
        var text = task.completed ? 'Task Complete' : 'Task Incomplete';
        messenger.showSnackBar(successSnackBar(theme: theme, text: text));
        onComplete?.call();
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(theme: theme, text: 'Could not update task'));
      }
    }

    var customColors = getCustomColors(context);
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
