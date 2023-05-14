import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/viewmodels/taskdetails.dart';
import 'package:docket/theme.dart';

class TaskAddScreen extends StatefulWidget {
  final Task task;

  const TaskAddScreen({required this.task, super.key});

  @override
  State<TaskAddScreen> createState() => _TaskAddScreenState();
}

class _TaskAddScreenState extends State<TaskAddScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  bool saving = false;

  @override
  Widget build(BuildContext context) {
    Future<void> saveTask(BuildContext context, Task task) async {
      var messenger = ScaffoldMessenger.of(context);
      var viewmodel = Provider.of<TaskDetailsViewModel>(context, listen: false);

      void complete() {
        Navigator.pop(context);
      }

      try {
        await viewmodel.create(task);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Task Created'));
        complete();
      } catch (e, stacktrace) {
        developer.log("Failed to create task ${e.toString()} $stacktrace");
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Failed to create task.'));
      }
    }

    var title = widget.task.id != null ? const Text('Edit Task') : const Text('New Task');
    var theme = Theme.of(context);

    return Portal(
        child: Scaffold(
            appBar: AppBar(
              title: title,
              actions: [
                TextButton(
                  style: TextButton.styleFrom(
                    foregroundColor: theme.colorScheme.onPrimary,
                  ),
                  child: const Text('Save'),
                  onPressed: () async {
                    if (_formKey.currentState!.validate() && saving == false) {
                      saving = true;
                      _formKey.currentState!.save();
                      await saveTask(context, widget.task);
                      saving = false;
                    }
                  }
                )
              ]
            ),
            body: SingleChildScrollView(
                padding: EdgeInsets.all(space(2)),
                child: TaskForm(
                  formKey: _formKey,
                  task: widget.task,
                ))));
  }
}
