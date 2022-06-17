import 'package:docket/providers/session.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/taskitem.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';

class TaskGroup extends StatelessWidget {
  final List<Task> tasks;

  const TaskGroup({
    required this.tasks,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var session = Provider.of<SessionProvider>(context);

        void onTaskComplete(Task task) {
          tasksProvider.toggleComplete(session.apiToken, task);
        }

        return SizedBox(
          height: 250,
          child: ListView.builder(
            itemCount: tasks.length,
            itemBuilder: (BuildContext context, int index) {
              return TaskItem(tasks[index], onChange: onTaskComplete);
            }
          )
        );
      }
    );
  }
}

