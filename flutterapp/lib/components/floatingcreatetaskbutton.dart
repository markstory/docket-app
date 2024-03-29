import 'package:docket/viewmodels/taskadd.dart';
import 'package:flutter/material.dart';

import 'package:docket/routes.dart';
import 'package:provider/provider.dart';

class FloatingCreateTaskButton extends StatelessWidget {
  final DateTime? dueOn;
  final int? projectId;
  final int? sectionId;
  final bool? evening;

  const FloatingCreateTaskButton({this.dueOn, this.projectId, this.sectionId, this.evening, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    return FloatingActionButton(
      key: const ValueKey("floating-task-add"),
      onPressed: () {
        var viewmodel = Provider.of<TaskAddViewModel>(context, listen: false);

        viewmodel.task.dueOn = dueOn;
        viewmodel.task.sectionId = sectionId;
        viewmodel.task.projectId = projectId;
        viewmodel.task.evening = evening ?? false;

        Navigator.pushNamed(context, Routes.taskAdd);
      },
      backgroundColor: theme.colorScheme.primary,
      child: Icon(Icons.add, color: theme.colorScheme.onPrimary),
    );
  }
}
