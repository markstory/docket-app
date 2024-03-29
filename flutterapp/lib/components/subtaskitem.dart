import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/dialogs/confirmdelete.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/taskform.dart';

class SubtaskItem extends StatefulWidget {
  final Task task;
  final Subtask subtask;
  final TaskFormViewModel viewmodel;

  const SubtaskItem({required this.task, required this.subtask, required this.viewmodel, super.key});

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

    // Forces provider to initialize early making tests pass more easily.
    Provider.of<TasksProvider>(context, listen: false);
  }

  @override
  void dispose() {
    _controller.dispose();
    inputFocus.dispose();
    super.dispose();
  }

  void handleSubtaskComplete(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var viewmodel = widget.viewmodel;

    try {
      await viewmodel.toggleSubtask(task, subtask);
      if (subtask.completed) {
        messenger.showSnackBar(successSnackBar(context: context, text: 'Subtask Updated'));
      }
    } catch (e) {
      messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not update subtask'));
    }
  }

  void handleUpdate(BuildContext context, Task task, Subtask subtask) async {
    var messenger = ScaffoldMessenger.of(context);
    var viewmodel = widget.viewmodel;

    try {
      await viewmodel.saveSubtask(task, subtask);
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
      key: ValueKey('subtask-${subtask.id}'),
      contentPadding: EdgeInsets.fromLTRB(space(0.3), space(0.5), space(1), space(0.5)),
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
      title: itemContents(context, subtask, customColors),
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
          key: const ValueKey('remove-subtask'),
          icon: Icon(Icons.delete, color: customColors.actionDelete),
          onPressed: () {
            showConfirmDelete(
                context: context,
                content: "Are you sure you want to delete this subtask?",
                onConfirm: () async {
                  var navigator = Navigator.of(context);
                  var viewmodel = widget.viewmodel;

                  await viewmodel.deleteSubtask(widget.task, widget.subtask);
                  navigator.pop();
                });
          }),
    );
  }

  Widget itemContents(BuildContext context, Subtask subtask, DocketColors customColors) {
    var theme = Theme.of(context);
    var textTheme = theme.textTheme;
    var viewmodel = widget.viewmodel;

    // Show uneditable text at first so that drag/drop can work.
    if (!hasFocus) {
      var textStyle = titleStyle(subtask, textTheme, customColors);
      return GestureDetector(
          child: Text(subtask.title, style: textStyle),
          onTap: () {
            setState(() {
              hasFocus = !hasFocus;
            });
          });
    }

    return TextField(
      key: const ValueKey('subtask-title'),
      focusNode: inputFocus,
      style: completedStyle(context, subtask.completed),
      controller: _controller,
      textInputAction: TextInputAction.done,
      onSubmitted: (String value) async {
        var sub = widget.subtask;
        sub.title = value;
        await viewmodel.saveSubtask(widget.task, sub);
      },
      decoration: inputSuffix(context),
    );
  }

  TextStyle titleStyle(Subtask subtask, TextTheme textTheme, DocketColors customColors) {
    var titleStyle = textTheme.bodyMedium!.copyWith(height: 2.2);
    if (subtask.completed) {
      titleStyle = titleStyle.copyWith(
        color: customColors.disabledText,
        decoration: TextDecoration.lineThrough,
      );
    }
    return titleStyle;
  }
}
