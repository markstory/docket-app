import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
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
        messenger.showSnackBar(successSnackBar(text: 'Task Deleted'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(text: 'Could not delete task'));
      }
    }

    Future<void> _handleReschedule() async {
      // Show reschedule menu. Perhaps as a sheet?
    }

    void _handleCompleted() async {
      var messenger = ScaffoldMessenger.of(context);
      try {
        await tasksProvider.toggleComplete(session.apiToken, task);
        messenger.showSnackBar(successSnackBar(text: 'Task Completed'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(text: 'Could not update task'));
      }
    }

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Flexible(
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Checkbox(
                activeColor: Colors.green,
                checkColor: Colors.white,
                value: task.completed,
                onChanged: (bool? value) {
                  _handleCompleted();
                }
              ),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      task.title,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: task.completed ? Colors.grey : Colors.black,
                        decoration: task.completed
                          ? TextDecoration.lineThrough : null,
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: [
                          ProjectBadge(task: task),
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
              const PopupMenuItem<Menu>(
                value: Menu.move,
                child: TaskMenuItem(
                  icon: Icon(Icons.drive_file_move, color: Colors.amber),
                  text: Text('Move To'),
                ),
              ),
              const PopupMenuItem<Menu>(
                value: Menu.reschedule,
                child: TaskMenuItem(
                  icon: Icon(Icons.calendar_today, color: Colors.purple),
                  text: Text('Reschedule'),
                ),
              ),
              const PopupMenuItem<Menu>(
                value: Menu.delete,
                child: TaskMenuItem(
                  icon: Icon(Icons.delete, color: Colors.red),
                  text: Text('Delete'),
                ),
              ),
            ];
          }
        )
      ]
    );
  }
}

class TaskMenuItem extends StatelessWidget {
  final Widget icon;
  final Widget text;

  const TaskMenuItem({
    required this.icon, 
    required this.text, 
    super.key
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(right: 4),
          child: icon,
        ),
        text
      ],
    );
  }
}

class ProjectBadge extends StatelessWidget {
  final Task task;

  const ProjectBadge({required this.task, super.key});

  @override
  Widget build(BuildContext context) {
    var color = getProjectColor(task.projectColor);
    return Row(
      children: [
        Padding(
          padding: const EdgeInsets.all(2),
          child: Icon(Icons.circle, color: color, size: 12),
        ),
        Text(
          task.projectName,
          style: const TextStyle(color: Colors.black54),
        ),
      ]
    );
  }
}
