import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskAddScreen extends StatelessWidget {
  final Task task;

  const TaskAddScreen({required this.task, super.key});

  @override
  Widget build(BuildContext context) {
    Future<void> saveTask(BuildContext context, Task task) async {
      var messenger = ScaffoldMessenger.of(context);
      var tasks = Provider.of<TasksProvider>(context, listen: false);

      void complete() {
        Navigator.pop(context);
      }

      try {
        await tasks.createTask(task);
        complete();
        messenger.showSnackBar(successSnackBar(context: context, text: 'Task Created'));
      } catch (e, stacktrace) {
        developer.log("Failed to create task ${e.toString()} $stacktrace");
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Failed to create task.'));
      }
    }

    var title = task.id != null ? const Text('Edit Task') : const Text('New Task');

    return Portal(
        child: Scaffold(
            appBar: AppBar(title: title),
            body: SingleChildScrollView(
                padding: EdgeInsets.all(space(2)),
                child: TaskForm(
                  task: task,
                  onSave: (updated) async => await saveTask(context, updated),
                ))));
  }
}
