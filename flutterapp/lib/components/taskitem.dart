import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';

class TaskItem extends StatelessWidget {
  final Task task;

  const TaskItem(this.task, {super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 50,
      child: Text(task.title),
    );
  }
}
