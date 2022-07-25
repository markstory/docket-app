import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/models/project.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/providers/projects.dart';

/// Dialog sheet for changing a project id
Future<void> showChangeProjectDialog(
  BuildContext context,
  int? projectId,
  Function(int projectId) onChange
) {
  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (BuildContext context) {
      return Consumer<ProjectsProvider>(
        builder: (context, projectsProvider, child) {
          return AlertDialog(
            title: const Text('Change Project'),
            content: SingleChildScrollView(
              child: FutureBuilder<List<Project>>(
                future: projectsProvider.getProjects(),
                builder: (context, snapshot) {
                  // TODO if there is no data and we have a stale data error
                  // load projects.
                  if (!snapshot.hasData) {
                    return const LoadingIndicator();
                  }
                  var projects = snapshot.data!;
                  return ListBody(
                    children: projects.map((project) {
                      return ListTile(
                        dense: true,
                        title: ProjectBadge(text: project.name, color: project.color, isActive: project.id == projectId),
                        onTap: () {
                          onChange(project.id);
                        }
                      );
                    }).toList(),
                  );
                }
              )
            ),
          );
        }
      );
    }
  );
}
