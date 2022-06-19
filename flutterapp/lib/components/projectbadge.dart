import 'package:flutter/material.dart';

import 'package:docket/theme.dart';
import 'package:docket/models/task.dart';

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
