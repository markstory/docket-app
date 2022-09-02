import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/subtaskitem.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';


class TaskDetailsScreen extends StatefulWidget {
  final Task task;

  const TaskDetailsScreen(this.task, {super.key});

  @override
  State<TaskDetailsScreen> createState() => _TaskDetailsScreenState();
}

class _TaskDetailsScreenState extends State<TaskDetailsScreen> {
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
      var id = widget.task.id!;
      var pendingTask = tasksProvider.getById(id);

      return Portal(
        child: Scaffold(
          appBar: AppBar(title: const Text('Task Details')),
          body: FutureBuilder<Task?>(
              future: pendingTask,
              builder: (context, snapshot) {
                var task = snapshot.data ?? widget.task;

                return SingleChildScrollView(padding: EdgeInsets.all(space(1)), 
                  child: Column(children: [
                    TaskForm(
                      task: task,
                      onSave: (task) => _onSave(context, task),
                      onComplete: () => Navigator.of(context).pop(),
                    ),
                    _buildSubtasks(context, task),
                  ]),
                );
              }),
        ),
      );
    });
  }

  Widget _buildSubtasks(BuildContext context, Task task) {
    if (task.subtasks.isEmpty) {
      return const SizedBox(height: 0, width: 0);
    }

    var theme = Theme.of(context);
    return Column(children: [
      Text('Subtasks', style: theme.textTheme.titleSmall),
      ...task.subtasks.map<Widget>((sub) {
        return SubtaskItem(task: task, subtask: sub);
      }),
    ]);
  }
}
