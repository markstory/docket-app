import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskDetailsScreen extends StatelessWidget {
  static const routeName = '/tasks/{taskId}/view';

  final int taskId;

  const TaskDetailsScreen(this.taskId, {super.key});

  void _onSave(Task task, context) async {
    var messenger = ScaffoldMessenger.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
    var session = Provider.of<SessionProvider>(context, listen: false);

      try {
        await tasksProvider.updateTask(session.apiToken, task);
        messenger.showSnackBar(
          successSnackBar(context: context, text: 'Task Completed')
        );
      } catch (e, stack) {
        print("${e.toString()}, $stack");
        messenger.showSnackBar(
          errorSnackBar(context: context, text: 'Could not update task')
        );
      }

  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var session = Provider.of<SessionProvider>(context);
        var pendingTask = tasksProvider.getById(session.apiToken, taskId);

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
                  TaskForm(task: task, onSave: (task) => _onSave(task, context)),
                ]
              );
            }
          ),
        );
      }
    );
  }
}

