import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskCheckbox extends StatelessWidget {
  final Task task;
  final bool value;
  final bool disabled;

  /// Fired when state is changed on client.
  final void Function(bool value)? onToggle;

  /// Fired when the task state is changed on the server.
  final void Function(bool value)? onChange;

  const TaskCheckbox({
    required this.value,
    required this.task,
    this.onToggle,
    this.onChange,
    this.disabled = false,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    var customColors = getCustomColors(context);

    return Checkbox(
      activeColor: customColors.actionComplete,
      checkColor: Colors.white,
      value: value,
      visualDensity: VisualDensity.compact,
      onChanged: (bool? value) async {
        if (value == null || disabled) {
          return;
        }
        onToggle?.call(value);

        var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
        var messenger = ScaffoldMessenger.of(context);
        var theme = Theme.of(context);

        // Wait 500ms for completion animations.
        await Future.delayed(const Duration(milliseconds: 500), () async {
          try {
            await tasksProvider.toggleComplete(task);
            var text = task.completed ? 'Task Complete' : 'Task Incomplete';
            messenger.showSnackBar(successSnackBar(theme: theme, text: text));
          } catch (e) {
            messenger.showSnackBar(errorSnackBar(theme: theme, text: 'Could not update task'));
          }
        });
      }
    );
  }
}
