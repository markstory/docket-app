import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskDetailsScreen extends StatefulWidget {
  static const routeName = '/tasks/{taskId}/view';

  final int taskId;

  const TaskDetailsScreen(this.taskId, {super.key});

  @override
  State<TaskDetailsScreen> createState() => _TaskDetailsScreenState();
}

class _TaskDetailsScreenState extends State<TaskDetailsScreen> {
  @override
  void initState() {
    super.initState();
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchById(widget.taskId);
  }

  void _onSave(BuildContext context, Task task) async {
    var messenger = ScaffoldMessenger.of(context);
    var navigator = Navigator.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.updateTask(task);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Task Updated'));
      navigator.pop();
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update task'));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(builder: (context, tasksProvider, child) {
      var pendingTask = tasksProvider.getById(widget.taskId);

      return Portal(
        child: Scaffold(
          appBar: AppBar(title: const Text('Task Details')),
          body: FutureBuilder<Task?>(
              future: pendingTask,
              builder: (context, snapshot) {
                var task = snapshot.data;
                if (task == null) {
                  return const LoadingIndicator();
                }
                return SingleChildScrollView(padding: EdgeInsets.all(space(1)), 
                  child: TaskForm(
                    task: task,
                    onSave: (task) => _onSave(context, task),
                    onComplete: () => Navigator.of(context).pop(),
                  ),
                );
              }),
        ),
      );
    });
  }
}
