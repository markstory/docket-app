import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';
import 'package:docket/routes.dart';

class FloatingCreateTaskButton extends StatelessWidget {
  final Task? task;

  const FloatingCreateTaskButton({this.task, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);

    return FloatingActionButton(
      onPressed: () {
        Navigator.pushNamed(
          context,
          Routes.taskAdd,
          arguments: TaskAddArguments(task ?? Task.blank()),
        );
      },
      backgroundColor: theme.colorScheme.primary,
      child: Icon(Icons.add, color: theme.colorScheme.onPrimary),
    );
  }
}
