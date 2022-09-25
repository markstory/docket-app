import 'dart:developer' as developer;
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
  FocusNode inputFocus = FocusNode();
  bool hasFocus = false;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.subtask.title);
    inputFocus.addListener(() {
      setState(() {
        hasFocus = inputFocus.hasFocus;
      });
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    inputFocus.dispose();
    super.dispose();
  }

  void handleSubtaskComplete(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.toggleSubtask(task, subtask);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Subtask Complete'));
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  void handleUpdate(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    try {
      await tasksProvider.saveSubtask(task, subtask);
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
        focusNode: inputFocus,
        style: subtask.completed ? TextStyle(color: customColors.disabledText, decoration: TextDecoration.lineThrough) : null,
        controller: _controller,
        textInputAction: TextInputAction.done,
        onSubmitted: (String value) async {
          var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
          var sub = widget.subtask;
          sub.title = value;
          await tasksProvider.saveSubtask(widget.task, sub);
        },
        decoration: inputSuffix(context),
      ),
    );
  }

  InputDecoration? inputSuffix(BuildContext context) {
    if (!hasFocus) {
      return const InputDecoration();
    }
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return InputDecoration(
      suffixIcon: IconButton(
        icon: Icon(Icons.delete, color: customColors.actionDelete),
        onPressed: () {
          _confirmDelete(context);
        }
      ),
    );
  }

  void _confirmDelete(BuildContext context) {
    showDialog(
      context: context,
      builder: (BuildContext context) {
        return AlertDialog(
          title: const Text("Are you sure?"),
          content: const Text("Are you sure you want to delete this subtask?"),
          actions: [
            TextButton(
              child: const Text("Yes"),
              onPressed: () async {
                var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
                var navigator = Navigator.of(context);

                await tasksProvider.deleteSubtask(widget.task, widget.subtask);
                navigator.pop();
              }),
            ElevatedButton(child: const Text("No thanks"), onPressed: () {
              Navigator.pop(context);
            }),
          ]
        );
      }
    );
  }
}
