import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/components/taskdatesorter.dart';
import 'package:docket/components/projectactions.dart';
import 'package:docket/grouping.dart' as grouping;
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class ProjectDetailsScreen extends StatefulWidget {
  static const routeName = '/projects/{slug}';

  final String slug;

  const ProjectDetailsScreen(this.slug, {super.key});

  @override
  State<ProjectDetailsScreen> createState() => _ProjectDetailsScreenState();
}

class _ProjectDetailsScreenState extends State<ProjectDetailsScreen> {
  List<TaskSortMetadata> _taskLists = [];

  @override
  void initState() {
    super.initState();
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    projectsProvider.fetchBySlug(widget.slug);
  }

  void _buildTaskLists(ProjectWithTasks data) {
    var grouped = grouping.groupTasksBySection(data.project.sections, data.tasks);
    for (var group in grouped) {
      late TaskSortMetadata metadata;
      if (group.section == null) {
        metadata = TaskSortMetadata(
          title: group.section?.name ?? '',
          tasks: group.tasks,
          onReceive: (Task task, int newIndex) {
            task.childOrder = newIndex;
            task.sectionId = null;
            return {'child_order': newIndex, 'section_id': null};
          });
      } else {
        // TODO might need to add a trailing header button here
        // TODO add 'add button' for the section.
        metadata = TaskSortMetadata(
          title: group.section?.name ?? '',
          tasks: group.tasks,
          onReceive: (Task task, int newIndex) {
            task.childOrder = newIndex;
            task.sectionId = group.section?.id;
            return {'child_order': newIndex, 'section_id': task.sectionId};
          });
      }
      _taskLists.add(metadata);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, TasksProvider>(builder: (context, projectsProvider, tasksProvider, child) {
      var theme = Theme.of(context);
      var projectFuture = projectsProvider.getBySlug(widget.slug);

      return Scaffold(
        appBar: AppBar(title: const Text('Project Details')),
        drawer: const AppDrawer(),
        body: FutureBuilder<ProjectWithTasks?>(
            future: projectFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text("Something terrible happened"));
              }
              var project = snapshot.data;
              if (project == null || project.pending || project.missingData) {
                if (project?.missingData ?? false) {
                  projectsProvider.fetchBySlug(widget.slug);
                }
                return const LoadingIndicator();
              }

              List<Widget> children = [
                Row(children: [
                  SizedBox(width: space(2)),
                  Text(project.project.name, style: theme.textTheme.titleLarge),
                  TaskAddButton(projectId: project.project.id),
                  const Spacer(),
                  ProjectActions(project.project),
                ]),
              ];

              if (_taskLists.isEmpty) {
                _buildTaskLists(project);
              }

              var sorter = TaskDateSorter(
                taskLists: _taskLists,
                buildItem: (Task task) {
                  return TaskItem(task: task, showDate: true, showProject: true);
                },
                onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                  var task = _taskLists[oldListIndex].tasks[oldItemIndex];

                  // Get the changes that need to be made on the server.
                  var updates = _taskLists[oldListIndex].onReceive(task, newItemIndex);

                  // Update local state assuming server will be ok.
                  setState(() {
                    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
                    _taskLists[newListIndex].tasks.insert(newItemIndex, task);
                  });

                  // Update the moved task and reload from server async
                  await tasksProvider.move(task, updates);
                  tasksProvider.fetchToday();
                },
                onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
                  // Calculate position of adding to a end.
                  // Generally this will be zero but it is possible to add to the
                  // bottom of a populated list too.
                  var targetList = _taskLists[listIndex];
                  if (itemIndex == -1) {
                    itemIndex = targetList.tasks.length;
                  }

                  var itemChild = newItem.child as TaskItem;
                  var task = itemChild.task;

                  // Get the changes that need to be made on the server.
                  var updates = _taskLists[listIndex].onReceive(task, itemIndex);
                  setState(() {
                    _taskLists[listIndex].tasks.insert(itemIndex, task);
                  });

                  // Update the moved task and reload from server async
                  await tasksProvider.move(task, updates);
                  tasksProvider.fetchToday();
                }
              );
              children.add(sorter);

              return ListView(
                children: children,
              );
            }),
      );
    });
  }
}
