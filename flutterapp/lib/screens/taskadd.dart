import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TaskAddScreenArguments {
  final Task task;

  TaskAddScreenArguments(this.task);
}

class TaskAddScreen extends StatelessWidget {
  static const routeName = '/tasks/add';
  final Task task;

  const TaskAddScreen({required this.task, super.key});

  @override
  Widget build(BuildContext context) {
    void _saveTask(BuildContext context, Task task) async {
      var messenger = ScaffoldMessenger.of(context);
      var session = Provider.of<SessionProvider>(context, listen: false);
      var tasks = Provider.of<TasksProvider>(context, listen: false);

      void complete() { 
        Navigator.pop(context); 
      }

      try {
        messenger.showSnackBar(
          const SnackBar(content: Text('Saving'))
        );
        await tasks.createTask(session.apiToken, task);
        messenger.showSnackBar(
          const SnackBar(content: Text('Task Created'))
        );
        complete();
      } catch (e, stacktrace) {
        developer.log("Failed to create project ${e.toString()} $stacktrace");
        messenger.showSnackBar(
          const SnackBar(content: Text('Failed to create task')),
        );
      }
    }
    var title = task.id != null ? const Text('Edit Task') : const Text('New Task');

    return Portal(
      child: Scaffold(
        appBar: AppBar(title: title),
        body: Container(
          padding: EdgeInsets.all(space(2)),
          child: TaskForm(
            task: task,
            onSave: (updated) => _saveTask(context, updated),
          )
        )
      )
    );
  }
}
