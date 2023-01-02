import 'package:docket/dialogs/confirmdelete.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/dueon.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/dialogs/changedueon.dart';
import 'package:docket/dialogs/changeproject.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';

enum Menu { move, reschedule, delete }

class TaskItem extends StatelessWidget {
  final Task task;

  /// Should the date + evening icon be shown?
  final bool showDate;

  /// Should the project badge be shown?
  final bool showProject;

  /// Should the restore button be shown?
  final bool showRestore;

  const TaskItem({
    required this.task,
    this.showDate = false,
    this.showProject = false,
    this.showRestore = false,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    List<Widget> attributes = [];
    if (showProject) {
      attributes.add(ProjectBadge(text: task.projectName, color: task.projectColor));
    }
    if (showDate && task.dueOn != null) {
      attributes.add(DueOn(dueOn: task.dueOn, evening: task.evening, showIcon: true));
    }
    if (task.subtaskCount > 0) {
      attributes.add(Wrap(spacing: space(0.25), children: [
        const Icon(Icons.done, color: Colors.grey, size: 14),
        Text("${task.completeSubtaskCount}/${task.subtaskCount}"),
      ]));
    }

    Widget? subtitle;
    if (attributes.isNotEmpty) {
      subtitle = Wrap(
        runAlignment: WrapAlignment.start,
        spacing: space(1),
        children: attributes,
      );
    }
    var theme = Theme.of(context);
    var customColors = getCustomColors(context);

    var textStyle = theme.textTheme.bodyText2!;
    if (task.completed) {
      textStyle = textStyle.copyWith(
        decoration: TextDecoration.lineThrough,
        color: customColors.disabledText
      );
    }

    return ListTile(
        dense: true,
        contentPadding: EdgeInsets.fromLTRB(space(1), space(0.5), space(1), space(0.5)),
        leading: TaskCheckbox(task),
        title: AnimatedDefaultTextStyle(
          duration: const Duration(seconds: 1),
          overflow: TextOverflow.ellipsis,
          style: textStyle,
          child: Text(task.title),
        ),
        subtitle: subtitle,
        trailing: TaskActions(task, showRestore: showRestore),
        onTap: showRestore ? null : () {
          Navigator.pushNamed(context, Routes.taskDetails, arguments: TaskDetailsArguments(task));
        });
  }
}

class TaskActions extends StatelessWidget {
  final Task task;
  final bool showRestore;

  const TaskActions(this.task, {required this.showRestore, super.key});

  @override
  Widget build(BuildContext context) {
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);
    var messenger = ScaffoldMessenger.of(context);

    Future<void> handleChangeProject() async {
      var projectId = await showChangeProjectDialog(context, task.projectId);
      task.projectId = projectId;
      tasksProvider.updateTask(task);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Task Updated'));
    }

    Future<void> handleDelete() async {
      showConfirmDelete(
        context: context, 
        content: "Are you sure you want to delete this task?",
        onConfirm: () async {
          var navigator = Navigator.of(context);
          try {
            await tasksProvider.deleteTask(task);
            messenger.showSnackBar(successSnackBar(context: context, text: 'Task Deleted'));
            navigator.pop();
          } catch (e) {
            messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not delete task'));
          }
        });
    }

    Future<void> handleReschedule() async {
      var result = await showChangeDueOnDialog(context, task.dueOn, task.evening);
      // TODO Setting previous values like this is error prone. Perhaps
      // Task should have snapshotting features? or have this be model level logic.
      // The tricky part has been that doing an API request builds a new task instead
      // of updating the existing one. Perhaps solving that would be better?
      task.previousDueOn = task.dueOn;

      task.dueOn = result.dueOn;
      task.evening = result.evening;
      tasksProvider.updateTask(task);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Task Updated'));
    }

    Future<void> handleRestore() async {
      return tasksProvider.undelete(task);
    }

    if (showRestore) {
      return TextButton(onPressed: handleRestore, child: const Text('Restore'));
    }

    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(
      key: const ValueKey('task-actions'),
      onSelected: (Menu item) {
      var actions = {
        Menu.move: handleChangeProject,
        Menu.reschedule: handleReschedule,
        Menu.delete: handleDelete,
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.move,
          child: ListTile(
            leading: Icon(Icons.drive_file_move, color: customColors.actionEdit),
            title: const Text('Change Project'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.reschedule,
          child: ListTile(
            leading: Icon(Icons.calendar_today, color: customColors.dueToday),
            title: const Text('Schedule'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.delete,
          child: ListTile(
            leading: Icon(Icons.delete, color: customColors.actionDelete),
            title: const Text('Delete'),
          ),
        ),
      ];
    });
  }
}
