import 'package:flutter/material.dart';

import 'package:docket/components/taskitem.dart';
import 'package:docket/models/task.dart';

class TaskGroup extends StatelessWidget {
  final List<Task> tasks;

  const TaskGroup({
    required this.tasks,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    var taskItems = tasks.map((Task task) => TaskItem(task)).toList();

    return Column(children: taskItems);
  }
}

