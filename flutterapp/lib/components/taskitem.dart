import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/taskdue.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

enum Menu {move, reschedule, delete}

class TaskItem extends StatelessWidget {
  final Task task;

  const TaskItem(this.task, {super.key});

  @override
  Widget build(BuildContext context) {
    var session = Provider.of<SessionProvider>(context);
    var tasksProvider = Provider.of<TasksProvider>(context);

    Future<void> _handleMove() async {
      // Open project picker. Perhaps as a sheet?
    }

    Future<void> _handleDelete() async {
      var messenger = ScaffoldMessenger.of(context);
      try {
        await tasksProvider.deleteTask(session.apiToken, task);
        messenger.showSnackBar(
          successSnackBar(context: context, text: 'Task Deleted')
        );
      } catch (e) {
        messenger.showSnackBar(
          errorSnackBar(context: context, text: 'Could not delete task')
        );
      }
    }

    Future<void> _handleReschedule() async {
      // Show reschedule menu. Perhaps as a sheet?
    }

    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Flexible(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              TaskCheckbox(task),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    GestureDetector(
                      child: Text(
                        task.title,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: task.completed ? Colors.grey : Colors.black,
                          decoration: task.completed
                            ? TextDecoration.lineThrough : null,
                        ),
                      ),
                      onTap: () {
                        Navigator.pushNamed(context, '/tasks/${task.id}/view');
                      }
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 4),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          ProjectBadge(task),
                          const SizedBox(width: 4),
                          TaskDue(task),
                        ]
                      ),
                    )
                  ]
                )
              ),
            ]
          )
        ),
        PopupMenuButton<Menu>(
          onSelected: (Menu item) {
            var actions = {
              Menu.move: _handleMove,
              Menu.reschedule: _handleReschedule,
              Menu.delete: _handleDelete,
            };
            actions[item]?.call();
          },
          itemBuilder: (BuildContext context) {
            return <PopupMenuEntry<Menu>>[
              PopupMenuItem<Menu>(
                value: Menu.move,
                child: ListTile(
                  leading: Icon(Icons.drive_file_move, color: customColors.actionEdit),
                  title: const Text('Move To'),
                ),
              ),
              PopupMenuItem<Menu>(
                value: Menu.reschedule,
                child: ListTile(
                  leading: Icon(Icons.calendar_today, color: customColors.dueToday),
                  title: const Text('Reschedule'),
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
          }
        )
      ]
    );
  }
}