import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/taskdue.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';

class TaskDetailsScreen extends StatelessWidget {
  static const routeName = '/tasks/{taskId}/view';

  final int taskId;

  const TaskDetailsScreen(this.taskId, {super.key});

  @override
  Widget build(BuildContext context) {
    // TODO figure out how to load tasks/today data.
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var session = Provider.of<SessionProvider>(context);
        var pendingTask = tasksProvider.getById(session.apiToken, taskId);
        var theme = Theme.of(context);

        return Scaffold(
          appBar: AppBar(),
          body: FutureBuilder<Task>(
            future: pendingTask,
            builder: (context, snapshot) {
              var task = snapshot.data!;

              return Column(
                children: [
                  Row(
                    children: [
                      TaskCheckbox(task),
                      Text(task.title, style: theme.textTheme.bodyLarge),
                    ]
                  ),
                  Row(
                    children: [
                      ProjectBadge(task),
                      const SizedBox(width: 4),
                      TaskDue(task),
                    ]
                  ),
                  Row(
                    children: [
                      Text('Notes', style: theme.textTheme.bodyLarge),
                      // TODO make this rendered markdown, and click to edit.
                      Text(task.body),
                    ]
                  ),
                  Row(
                    children: [
                      Text('Sub-tasks', style: theme.textTheme.bodyLarge),
                      // TODO make this rendered markdown, and click to edit.
                      const Text('TODO subtasks go here'),
                    ]
                  ),
                ]
              );
            }
          ),
        );
      }
    );
  }
}

