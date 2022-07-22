import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';
import 'package:docket/screens/taskadd.dart';

/// Button to create a new task with some fields initialized.
class TaskAddButton extends StatelessWidget {
  final DateTime? dueOn;
  final int? projectId;
  final int? sectionId;
  final bool? evening;

  const TaskAddButton({
    this.dueOn,
    this.projectId,
    this.sectionId,
    this.evening,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var task = Task.blank();

    if (dueOn != null) {
      task.dueOn = dueOn;
    }
    if (projectId != null) {
      task.projectId = projectId;
    }
    if (sectionId != null) {
      task.sectionId = sectionId;
    }
    if (evening != null) {
      task.evening = evening!;
    }
    return IconButton(
      icon: const Icon(Icons.add),
      color: theme.colorScheme.primary,
      onPressed: () {
        Navigator.pushNamed(
          context,
          TaskAddScreen.routeName,
          arguments: TaskAddScreenArguments(task),
        );
      }
    );
  }
}
