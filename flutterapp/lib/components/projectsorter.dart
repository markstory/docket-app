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
class ProjectSorter extends StatefulWidget {
  const ProjectSorter({super.key});

  @override
  State<ProjectSorter> createState() => _ProjectSorterState();
}

class _ProjectSorterState extends State<ProjectSorter> {
  late Future<List<Project>> projectsFuture;

  @override
  void initState() {
    super.initState();
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    projectsFuture = projectsProvider.getAll();
  }

  void _onItemReorder(Project project, int newIndex) async {
    var sessionProvider = Provider.of<SessionProvider>(context, listen: false);
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    await projectsProvider.move(sessionProvider.apiToken, project, newIndex);

    setState(() {
      projectsFuture = projectsProvider.getAll();
    });
  }

  @override
  Widget build(BuildContext context) {
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
            _onItemReorder(project, newItemIndex);
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
  }
}
