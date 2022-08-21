import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/taskdatesorter.dart';
import 'package:docket/components/projectactions.dart';
import 'package:docket/dialogs/renamesection.dart';
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
  List<TaskSortMetadata<Section>> _taskLists = [];
  Task? _newTask;

  @override
  void initState() {
    super.initState();

    _refresh();
  }

  Future<void> _refresh() {
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    return projectsProvider.fetchBySlug(widget.slug);
  }

  void _buildTaskLists(ProjectWithTasks data) {
    _newTask = Task.blank(projectId: data.project.id);
    _taskLists = [];

    var grouped = grouping.groupTasksBySection(data.project.sections, data.tasks);
    for (var group in grouped) {
      late TaskSortMetadata<Section> metadata;
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
        metadata = TaskSortMetadata(
            canDrag: true,
            title: group.section?.name ?? '',
            tasks: group.tasks,
            data: group.section,
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
      var projectFuture = projectsProvider.getBySlug(widget.slug);

      return FutureBuilder<ProjectWithTasks?>(
          future: projectFuture,
          builder: (context, snapshot) {
            if (snapshot.hasError) {
              return buildWrapper(child: const Card(child: Text("Something terrible happened")));
            }
            var project = snapshot.data;
            if (project == null || project.pending || project.missingData) {
              if (project?.missingData ?? false) {
                _refresh();
              }
              return buildWrapper(child: const LoadingIndicator());
            }
            // See if this fixes sections dropping off.
            // if (_taskLists.isEmpty) {
            _buildTaskLists(project);
            // }

            return buildWrapper(
                project: project.project,
                child: TaskDateSorter(
                    taskLists: _taskLists,
                    buildItem: (Task task) {
                      return TaskItem(task: task, showDate: true, showProject: false);
                    },
                    buildHeader: (TaskSortMetadata metadata) {
                      var data = metadata.data as Section?;
                      if (data == null) {
                        return const SizedBox(width: 0, height: 0);
                      }
                      return Padding(
                          padding: EdgeInsets.only(left: space(3), right: space(1)),
                          child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                            Row(children: [
                              Text(metadata.title ?? ''),
                              TaskAddButton(projectId: project.project.id, sectionId: data.id),
                            ]),
                            SectionActions(project.project, data),
                          ]));
                    },
                    onListReorder: (int oldIndex, int newIndex) async {
                      var metadata = _taskLists[oldIndex];
                      setState(() {
                        _taskLists.removeAt(oldIndex);
                        _taskLists.insert(newIndex, metadata);
                      });
                      var section = metadata.data;
                      if (section == null) {
                        return;
                      }
                      await projectsProvider.moveSection(project.project, section, newIndex);
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
                    }));
          });
    });
  }

  Widget buildWrapper({required Widget child, Project? project}) {
    List<Widget> actions = [];
    var title = "";
    // Default to a gray color
    var colorId = 14;
    if (project != null) {
      actions = [ProjectActions(project)];
      title = project.name;
      colorId = project.color;
    }
    return Scaffold(
      appBar: AppBar(
        backgroundColor: getProjectColor(colorId),
        title: Text(title),
        actions: actions,
      ),
      drawer: const AppDrawer(),
      // TODO add scroll tracking for sections and update add button.
      floatingActionButton: FloatingCreateTaskButton(task: _newTask),
      body: child,
    );
  }
}

enum Menu { delete, edit }

class SectionActions extends StatelessWidget {
  final Section section;
  final Project project;

  const SectionActions(this.project, this.section, {super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);
    var messenger = ScaffoldMessenger.of(context);

    Future<void> _handleDelete() async {
      try {
        await projectsProvider.deleteSection(project, section);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Section Deleted'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not delete section task'));
      }
    }

    Future<void> _handleEdit() async {
      try {
        await showRenameSectionDialog(context, project, section);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Section renamed'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not rename section'));
      }
    }

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.edit: _handleEdit,
        Menu.delete: _handleDelete,
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.edit,
          child: ListTile(
            leading: Icon(Icons.edit, color: customColors.actionEdit),
            title: const Text('Rename'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.delete,
          child: ListTile(
            leading: Icon(Icons.delete, color: customColors.actionDelete),
            title: const Text('Delete'),
          ),
        ),
      ];
    });
  }
}
