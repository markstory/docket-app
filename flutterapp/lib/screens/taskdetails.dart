import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';
import 'package:docket/screens/taskdetails_view_model.dart';

class TaskDetailsScreen extends StatefulWidget {
  final Task task;

  const TaskDetailsScreen(this.task, {super.key});

  @override
  State<TaskDetailsScreen> createState() => _TaskDetailsScreenState();
}

class _TaskDetailsScreenState extends State<TaskDetailsScreen> {
  late TaskDetailsViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<TaskDetailsViewModel>(context, listen: false);
    viewmodel.setId(widget.task.id!);

    _refresh(viewmodel);
  }

  Future<void> _refresh(TaskDetailsViewModel view) async {
    await view.refresh();
  }

  void _onSave(BuildContext context, Task task) async {
    var messenger = ScaffoldMessenger.of(context);
    var navigator = Navigator.of(context);

    try {
      await viewmodel.update(task);
      navigator.pop();
      messenger.showSnackBar(successSnackBar(context: context, text: 'Task Updated'));
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update task'));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TaskDetailsViewModel>(builder: (context, view, child) {
      Widget body;
      if (view.loading) {
        body = const LoadingIndicator();
      } else {
        body = SingleChildScrollView(
          padding: EdgeInsets.all(space(1)),
          child: Column(children: [
            TaskForm(
              task: viewmodel.task,
              onSave: (task) => _onSave(context, task),
              onComplete: () => Navigator.of(context).pop(),
            ),
          ]),
        );
      }
      return Portal(
        child: Scaffold(
          appBar: AppBar(title: const Text('Task Details')),
          body: body,
        ),
      );
    });
  }
}
