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
    return IconButton(
        icon: const Icon(Icons.add),
        color: theme.colorScheme.primary,
        onPressed: () {
          var viewmodel = Provider.of<TaskAddViewModel>(context);

          viewmodel.task.dueOn = dueOn;
          viewmodel.task.sectionId = sectionId;
          viewmodel.task.evening = evening ?? false;
          viewmodel.task.projectId = projectId;

          Navigator.pushNamed(
            context,
            Routes.taskAdd,
          );
        });
  }
}
