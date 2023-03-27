import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';
import 'package:docket/routes.dart';

/// A project list item, primarily used in the application drawer.
class ProjectItem extends StatelessWidget {
  final Project project;

  const ProjectItem({required this.project, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var modalRoute = ModalRoute.of(context);
    var routeName = modalRoute?.settings.name;
    var routeArgs = modalRoute?.settings.arguments as ProjectDetailsArguments?;

    return ListTile(
      onTap: () {
        Navigator.pushNamed(context,
          Routes.projectDetails,
          arguments: ProjectDetailsArguments(project)
        );
      },
      leading: Icon(Icons.circle, color: getProjectColor(project.color)),
      title: Text(project.name),
      trailing: Text(
        project.incompleteTaskCount.toString(),
        style: TextStyle(color: theme.disabledColor),
      ),
      selected: (routeName == Routes.projectDetails && routeArgs?.project.slug == project.slug)
    );
  }
}

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
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    await projectsProvider.move(project, newIndex);

    setState(() {
      projectsFuture = projectsProvider.getAll();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Project>>(
        future: projectsFuture,
        builder: (context, snapshot) {
          var theme = Theme.of(context);
          var projects = snapshot.data;
          if (snapshot.hasData == false || projects == null) {
            return const LoadingIndicator();
          }

          return DragAndDropLists(
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
            disableScrolling: true,
            lastItemTargetHeight: 15,
            lastListTargetSize: 10,
            removeTopPadding: true,
            itemDragOnLongPress: true,
            onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) {
              var project = projects[oldItemIndex];
              _onItemReorder(project, newItemIndex);
            },
            onListReorder: (int oldIndex, int newIndex) {
              throw Exception('List reordering not supported');
            },
            itemDecorationWhileDragging: itemDragBoxDecoration(theme),
          );
        });
  }
}
