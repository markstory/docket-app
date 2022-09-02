import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class SubtaskItem extends StatefulWidget {
  final Task task;
  final Subtask subtask;

  const SubtaskItem({required this.task, required this.subtask, super.key});

  @override
  State<SubtaskItem> createState() => _SubtaskItemState();
}

class _SubtaskItemState extends State<SubtaskItem> {
  late TextEditingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.subtask.title);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void handleSubtaskComplete(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var navigator = Navigator.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.toggleSubtask(task, subtask);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Subtask Complete'));
      navigator.pop();
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  void handleUpdate(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.updateSubtask(task, subtask);
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;
    var subtask = widget.subtask;

    return ListTile(
      dense: true,
      leading: Checkbox(
        activeColor: customColors.actionComplete,
        checkColor: Colors.white,
        value: subtask.completed,
        visualDensity: VisualDensity.compact,
        onChanged: (bool? value) {
          if (value == null) {
            return;
          }
          handleSubtaskComplete(context, widget.task, subtask);
        }),
      title: TextField(
        controller: _controller,
        onSubmitted: (String value) async {
          var tasksProvider = Provider.of<TasksProvider>(context);
          var sub = widget.subtask;
          sub.title = value;
          await tasksProvider.updateSubtask(widget.task, sub);
        },
      ),
    );
  }
}
