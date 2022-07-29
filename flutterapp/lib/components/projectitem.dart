import 'package:flutter/material.dart';

import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/project.dart';

/// A project list item, primarily used in the application drawer.
class ProjectItem extends StatelessWidget {
  final Project project;

  const ProjectItem({required this.project, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    return ListTile(
      onTap: () {
        Navigator.pushNamed(context, '/projects/${project.slug}');
      },
      title: ProjectBadge(text: project.name, color: project.color),
      trailing: Text(
        project.incompleteTaskCount.toString(),
        style: TextStyle(color: theme.disabledColor),
      ),
    );
  }
}
