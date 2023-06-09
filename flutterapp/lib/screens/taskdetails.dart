import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_mentions/flutter_mentions.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/task.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/taskdetails.dart';

class TaskDetailsScreen extends StatefulWidget {
  final Task task;

  const TaskDetailsScreen(this.task, {super.key});

  @override
  State<TaskDetailsScreen> createState() => _TaskDetailsScreenState();
}

class _TaskDetailsScreenState extends State<TaskDetailsScreen> {
  late TaskDetailsViewModel viewmodel;
  bool saving = false;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<TaskDetailsViewModel>(context, listen: false);
    viewmodel.setId(widget.task.id!);
    viewmodel.loadData();
  }

  Future<void> _onSave(BuildContext context, Task task) async {
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
      var theme = Theme.of(context);
      Widget body;
      if (view.loading) {
        body = const LoadingIndicator();
      } else {
        body = RefreshIndicator(
          onRefresh: () => view.refresh(),
          child: SingleChildScrollView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: EdgeInsets.all(space(1)),
            child: Column(children: [
              TaskForm(
                formKey: _formKey,
                viewmodel: viewmodel,
              ),
            ]),
          ));
      }
      return Portal(
        child: Scaffold(
          appBar: AppBar(
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
                    await _onSave(context, viewmodel.task);
                    saving = false;
                  }
                }
              )
            ],
            title: const Text('Task Details')
          ),
          body: body,
        ),
      );
    });
  }
}
