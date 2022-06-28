import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/taskdue.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

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
          appBar: AppBar(title: const Text('Task Details')),
          body: FutureBuilder<Task>(
            future: pendingTask,
            builder: (context, snapshot) {
              var task = snapshot.data;
              if (task == null) {
                return const Card(
                  child: Text("404! Could not find that task.")
                );
              }
              return ListView(
                padding: EdgeInsets.all(space(1)),
                children: [
                  ListTile(
                    leading: TaskCheckbox(task),
                    title: Text(task.title, style: theme.textTheme.titleMedium),
                    subtitle: Row(
                      children: [
                        ProjectBadge(task),
                        const SizedBox(width: 4),
                        TaskDue(task),
                      ]
                    ),
                  ),
                  // Task Notes
                  SizedBox(height: space(3)),
                  Text('Notes', style: theme.textTheme.titleLarge),
                  Text(task.body),

                  // Sub-tasks list
                  SizedBox(height: space(3)),
                  Text('Sub-tasks', style: theme.textTheme.titleLarge),
                  const Text('TODO subtasks go here'),
                ]
              );
            }
          ),
        );
      }
    );
  }
}

