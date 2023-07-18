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

    var isActive = false;
    var activeRoute = Routes.activeRoute;
    if (activeRoute != null && activeRoute.startsWith(Routes.projectDetails)) {
      var parts = activeRoute.split(':');
      var slug = parts[1];
      isActive = slug == project.slug;
    }

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
      selected: isActive
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
  late ProjectsProvider provider;

  @override
  void initState() {
    super.initState();
    provider = Provider.of<ProjectsProvider>(context, listen: false);
    provider.loadData();
  }

  void _onItemReorder(Project project, int newIndex) async {
    await provider.move(project, newIndex);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectsProvider>(
      builder: buildContents,
    );
  }

  Widget buildContents(BuildContext context, ProjectsProvider provider, Widget? _) {
    if (provider.loading) {
      return const LoadingIndicator();
    } 
    var theme = Theme.of(context);

    return DragAndDropLists(
      children: [
        DragAndDropList(
          canDrag: false,
          children: provider.projects.map((project) {
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
        var project = provider.projects[oldItemIndex];
        _onItemReorder(project, newItemIndex);
      },
      onListReorder: (int oldIndex, int newIndex) {
        throw Exception('List reordering not supported');
      },
      itemDecorationWhileDragging: itemDragBoxDecoration(theme),
    );
  }
}
