import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskDetailsScreen extends StatelessWidget {
  static const routeName = '/tasks/{taskId}/view';

  final int taskId;

  const TaskDetailsScreen(this.taskId, {super.key});

  void _onSave(BuildContext context, Task task) async {
    var messenger = ScaffoldMessenger.of(context);
    var navigator = Navigator.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.updateTask(task);
      messenger.showSnackBar(
        successSnackBar(context: context, text: 'Task Completed')
      );
      if (navigator.canPop()) {
        navigator.pop();
      }
    } catch (e) {
      messenger.showSnackBar(
        errorSnackBar(context: context, text: 'Could not update task')
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var pendingTask = tasksProvider.getById(taskId);

        return Portal(
          child: Scaffold(
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
                    TaskForm(task: task, onSave: (task) => _onSave(context, task)),
                  ]
                );
              }
            ),
          )
        );
      }
    );
  }
}

