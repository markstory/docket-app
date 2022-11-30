import 'dart:developer' as developer;
import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/components/projectactions.dart';
import 'package:docket/dialogs/renamesection.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/screens/projectdetails_view_model.dart';
import 'package:docket/theme.dart';

class ProjectDetailsScreen extends StatefulWidget {
  final Project project;

  const ProjectDetailsScreen(this.project, {super.key});

  @override
  State<ProjectDetailsScreen> createState() => _ProjectDetailsScreenState();
}

class _ProjectDetailsScreenState extends State<ProjectDetailsScreen> {
  Task? _newTask;
  late ProjectDetailsViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<ProjectDetailsViewModel>(context, listen: false);
    viewmodel.setSlug(widget.project.slug);
    viewmodel.loadData();

    _newTask = Task.blank(projectId: widget.project.id);
    _refresh(viewmodel);
  }

  Future<void> _refresh(ProjectDetailsViewModel view) {
    _newTask = Task.blank(projectId: widget.project.id);

    return view.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectDetailsViewModel>(builder: (context, viewmodel, child) {
      if (viewmodel.loading) {
        return buildWrapper(
          project: widget.project,
          child: const LoadingIndicator(),
        );
      }

      return buildWrapper(
          project: viewmodel.project,
          child: TaskSorter(
              taskLists: viewmodel.taskLists,
              buildItem: (Task task) {
                return TaskItem(task: task, showDate: true);
              },
              buildHeader: (TaskSortMetadata metadata) {
                var data = metadata.data as Section?;
                if (data == null) {
                  return const SizedBox(width: 0, height: 0);
                }
                return Padding(
                    padding: EdgeInsets.only(left: space(2.5), right: space(1)),
                    child: Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
                      Row(children: [
                        Text(metadata.title ?? '', style: const TextStyle(fontWeight: FontWeight.bold)),
                        TaskAddButton(projectId: viewmodel.project.id, sectionId: data.id),
                      ]),
                      SectionActions(viewmodel.project, data),
                    ]));
              },
              onListReorder: (int oldIndex, int newIndex) async {
                await viewmodel.moveSection(oldIndex, newIndex);
              },
              onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                await viewmodel.reorderTask(oldItemIndex, oldListIndex, newItemIndex, newListIndex);
              },
              onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
                var itemChild = newItem.child as TaskItem;
                var task = itemChild.task;

                await viewmodel.moveInto(task, listIndex, itemIndex);
              }));
          });
  }

  Widget buildWrapper({required Widget child, required Project project}) {
    var actions = [ProjectActions(project)];
    return Scaffold(
      appBar: AppBar(
        backgroundColor: getProjectColor(project.color),
        title: Text(project.name),
        actions: actions,
      ),
      drawer: const AppDrawer(),
      // TODO add scroll tracking for sections and update add button.
      floatingActionButton: FloatingCreateTaskButton(task: _newTask),
      body: RefreshIndicator(
        onRefresh: () => _refresh(viewmodel),
        child: child,
      ),
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

    Future<void> handleDelete() async {
      try {
        await projectsProvider.deleteSection(project, section);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Section Deleted'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not delete section task'));
      }
    }

    Future<void> handleEdit() async {
      try {
        await showRenameSectionDialog(context, project, section);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Section renamed'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not rename section'));
      }
    }

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.edit: handleEdit,
        Menu.delete: handleDelete,
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
