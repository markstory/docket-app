import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/viewmodels/taskadd.dart';
import 'package:docket/theme.dart';

class TaskAddScreen extends StatefulWidget {
  const TaskAddScreen({super.key});

  @override
  State<TaskAddScreen> createState() => _TaskAddScreenState();
}

class _TaskAddScreenState extends State<TaskAddScreen> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  bool saving = false;

  @override
  Widget build(BuildContext context) {
    var viewmodel = Provider.of<TaskAddViewModel>(context, listen: false);

    Future<void> saveTask(BuildContext context) async {
      var messenger = ScaffoldMessenger.of(context);

      void complete() {
        Navigator.pop(context);
      }

      try {
        await viewmodel.save();
        messenger.showSnackBar(successSnackBar(context: context, text: 'Task Created'));
        complete();
      } catch (e, stacktrace) {
        developer.log("Failed to create task ${e.toString()} $stacktrace");
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Failed to create task.'));
      }
    }

    var theme = Theme.of(context);

    return Portal(
        child: Scaffold(
            appBar: AppBar(
              title: const Text('New Task'),
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
                      await saveTask(context);
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
                  task: viewmodel.task,
                ))));
  }
}
