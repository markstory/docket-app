import 'package:flutter/material.dart';

import 'package:docket/components/taskitem.dart';
import 'package:docket/models/task.dart';

class TaskGroup extends StatelessWidget {
  final List<Task> tasks;
  final bool showDate;
  final bool showProject;

  const TaskGroup({
    required this.tasks,
    this.showDate = false,
    this.showProject = false,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    var taskItems = tasks.map((Task task) {
      return TaskItem(task: task, showDate: showDate, showProject: showProject);
    }).toList();

    return Column(children: taskItems);
  }
}

