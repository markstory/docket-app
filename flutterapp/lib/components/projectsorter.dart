import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/models/project.dart';
import 'package:docket/components/projectitem.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';

/// Sortable project list used in the application drawer.
/// When sorting is complete, the moved project will be updated
/// and projects will be updated.
class ProjectSorter extends StatelessWidget {
  const ProjectSorter({super.key});

  void _onItemReorder(ProjectsProvider projectsProvider, String apiToken, Project project, int newIndex) {
    projectsProvider.moveProject(apiToken, project, newIndex);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<SessionProvider, ProjectsProvider>(
      builder: (context, sessionProvider, projectsProvider, child) {
        // TODO this might get annoying as each update could replace
        // the list with a spinner. A stateful widget might be better
        // but I don't yet know how to manage the futures for that.
        var projectsFuture = projectsProvider.getProjects();

        return FutureBuilder<List<Project>>(
          future: projectsFuture,
          builder: (context, snapshot) {
            var projects = snapshot.data;
            if (snapshot.hasData == false || projects == null) {
              return const LoadingIndicator();
            }

            return DragAndDropLists(
              disableScrolling: true,
              children: [
                DragAndDropList(
                  canDrag: false,
                  children: projects.map((project) {
                    return DragAndDropItem(
                      child: ProjectItem(project: project),
                    );
                  }).toList(),
                )
              ],
              itemDragOnLongPress: true,
              onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) {
                var project = projects[oldItemIndex];
                _onItemReorder(projectsProvider, sessionProvider.apiToken, project, newItemIndex);
              },
              onListReorder: (int oldIndex, int newIndex) {
                throw 'List reordering not supported';
              },
              itemDecorationWhileDragging: BoxDecoration(
                boxShadow: [
                  BoxShadow(
                    color: Colors.grey.withOpacity(0.5),
                    spreadRadius: 2,
                    blurRadius: 3,
                    offset: const Offset(0, 0),
                   )
                ]
              ),
            );
          }
        );
      },
    );
  }
}
