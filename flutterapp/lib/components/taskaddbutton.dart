import 'package:docket/viewmodels/taskadd.dart';
import 'package:flutter/material.dart';

import 'package:docket/routes.dart';
import 'package:provider/provider.dart';

/// Button to create a new task with some fields initialized.
class TaskAddButton extends StatelessWidget {
  final DateTime? dueOn;
  final int? projectId;
  final int? sectionId;
  final bool? evening;

  const TaskAddButton({this.dueOn, this.projectId, this.sectionId, this.evening, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var viewmodel = Provider.of<TaskAddViewModel>(context);

    if (dueOn != null) {
      viewmodel.task.dueOn = dueOn;
    }
    if (projectId != null) {
      viewmodel.task.projectId = projectId;
    }
    if (sectionId != null) {
      viewmodel.task.sectionId = sectionId;
    }
    if (evening != null) {
      viewmodel.task.evening = evening!;
    }
    return IconButton(
        icon: const Icon(Icons.add),
        color: theme.colorScheme.primary,
        onPressed: () {
          Navigator.pushNamed(
            context,
            Routes.taskAdd,
          );
        });
  }
}
