import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

enum Menu {move, reschedule, delete}

class TaskItem extends StatelessWidget {
  final Task task;
  final Function(Task task)? onChange;

  const TaskItem(this.task, {super.key, this.onChange});

  @override
  Widget build(BuildContext context) {
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
                  if (value == null) {
                    return;
                  }
                  task.completed = value;
                  if (onChange != null) {
                    onChange?.call(task);
                  }
                }
              ),
              Flexible(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      task.title,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.grey,
                        decoration: TextDecoration.lineThrough,
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
            print('selected $item');
            switch (item) {
              case Menu.move:
              break;

              case Menu.reschedule:
              break;

              case Menu.delete:
              break;
              default:
                throw Exception('Invalid menu type $item');
            }
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
