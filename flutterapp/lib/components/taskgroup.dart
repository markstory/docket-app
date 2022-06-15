import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';
import 'package:docket/components/taskitem.dart';

class TaskGroup extends StatelessWidget {
  final List<Task> tasks;

  const TaskGroup(this.tasks, {super.key});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 250,
      child: ListView.builder(
        itemCount: tasks.length,
        itemBuilder: (BuildContext context, int index) {
          return TaskItem(tasks[index]);
        }
      )
    );
  }
}

