import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/forms.dart';
import 'package:docket/components/subtaskitem.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/tasktitleinput.dart';
import 'package:docket/components/subtasksorter.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/viewmodels/taskdetails.dart';
import 'package:docket/theme.dart';

// Depends on TaskDetailsViewModel.
class TaskForm extends StatefulWidget {
  final Task task;
  final GlobalKey<FormState>? formKey;

  const TaskForm({required this.task, this.formKey, super.key});

  @override
  State<TaskForm> createState() => _TaskFormState();
}

class _TaskFormState extends State<TaskForm> {
  late bool completed;
  late bool saving = false;
  late Task task;

  late TextEditingController _newtaskController;

  @override
  void initState() {
    super.initState();
    task = widget.task.copy();
    completed = widget.task.completed;
    _newtaskController = TextEditingController(text: '');
  }

  @override
  void didUpdateWidget(TaskForm oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Sync changes for deleted subtasks.
    if (task.subtasks.length != widget.task.subtasks.length) {
      task = widget.task;
    }
  }

  /// Create the subtasks section for task details. This is a bit
  /// at odds with how the rest of the page as subtasks update immediately
  /// while other changes are deferred. Perhaps task updates should apply immediately
  /// or as a time throttled async change?
  Widget _buildSubtasks(BuildContext context, Task task) {
    var theme = Theme.of(context);

    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      SizedBox(height: space(2)),
      SubtaskSorter(
        items: task.subtasks,
        buildItem: (Subtask subtask) {
          return SubtaskItem(task: task, subtask: subtask);
        },
        onItemReorder: (oldItemIndex, oldListIndex, newItemIndex, newListIndex) async {
          var viewmodel = Provider.of<TaskDetailsViewModel>(context, listen: false);
          viewmodel.reorderSubtask(oldItemIndex, oldListIndex, newItemIndex, newListIndex);
        },
      ),
      Padding(
        padding: const EdgeInsets.fromLTRB(60, 0, 10, 30),
        child: TextField(
          key: const ValueKey('new-subtask'),
          controller: _newtaskController,
          decoration: InputDecoration(
            hintText: "Add a subtask",
            hintStyle: TextStyle(color: theme.colorScheme.primary, fontSize: 15),
          ),
          textInputAction: TextInputAction.done,
          onSubmitted: (String value) async {
            var viewmodel = Provider.of<TaskDetailsViewModel>(context, listen: false);

            var subtask = Subtask.blank(title: value);
            subtask.ranking = task.subtasks.length + 1;
            task.subtasks.add(subtask);
            await viewmodel.saveSubtask(task, subtask);
            _newtaskController.clear();
          }
        ),
      ),
    ]);
  }

  @override
  Widget build(BuildContext context) {
    var task = widget.task;
    var projectProvider = Provider.of<ProjectsProvider>(context);

    var projectList = projectProvider.getAll();

    return FutureBuilder<List<Project>>(
        future: projectList,
        builder: (context, snapshot) {
          List<Project> projects = [];
          if (snapshot.hasData) {
            projects = snapshot.data!;
          }
          // Set a default projectId
          if (task.projectId == null && projects.isNotEmpty) {
            task.projectId = projects[0].id;
          }
          var theme = Theme.of(context);
          var docketColors = theme.extension<DocketColors>()!;

          return Form(
              key: widget.formKey,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Padding(
                      padding: EdgeInsets.fromLTRB(0, space(1), space(1), 0),
                      child: TaskCheckbox(
                        value: completed,
                        task: widget.task,
                        disabled: task.id == null,
                        onToggle: (value) {
                          setState(() {
                            completed = value;
                          });
                        },
                        onChange: (value) {
                          Navigator.of(context).pop();
                        }
                      )),
                  Expanded(
                      child: TaskTitleInput(
                          autoFocus: task.id == null,
                          value: task.title,
                          projects: projects,
                          onChangeTitle: (title) {
                            setState(() {
                              task.title = title;
                            });
                          },
                          onChangeDate: (date, evening) {
                            setState(() {
                              task.dueOn = date;
                              task.evening = evening;
                            });
                          },
                          onChangeProject: (projectId) {
                            var project = projects.firstWhere((element) => element.id == projectId);
                            setState(() {
                              task.projectId = project.id;
                              task.projectSlug = project.slug;
                              task.sectionId = null;
                            });
                          }))
                ]),
                FormIconRow(
                  icon: Icon(Icons.folder_outlined,
                      size: DocketColors.iconSize, color: theme.colorScheme.primary, semanticLabel: 'Project'),
                  child: DropdownButtonFormField(
                    key: const ValueKey('project'),
                    value: task.projectId,
                    items: projects.map((item) {
                      var color = getProjectColor(item.color);

                      return DropdownMenuItem(
                          value: item.id,
                          child: Row(children: [
                            Icon(Icons.circle, color: color, size: 12),
                            SizedBox(width: space(1)),
                            Text(
                              item.name,
                            ),
                          ]));
                    }).toList(),
                    onChanged: (int? value) {
                      if (value != null) {
                        var project = projects.firstWhere((element) => element.id == value);
                        setState(() {
                          task.projectId = project.id;
                          task.projectSlug = project.slug;
                          task.sectionId = null;
                        });
                      }
                    },
                    validator: (int? value) {
                      if (value == null) {
                        return 'Project is required';
                      }
                      return null;
                    },
                  ),
                ),

                // Section Input. Conditionally shown based on the project.
                Builder(
                  builder: (context) {
                    Project? selectedProject;
                    try {
                      selectedProject = projects.firstWhere((element) => element.id == task.projectId);
                    } catch (err) {
                      selectedProject = null;
                    }
                    if (selectedProject == null || selectedProject.sections.isEmpty) {
                      return const SizedBox(width: 0, height: 0);
                    }

                    return FormIconRow(
                      icon: Icon(Icons.topic_outlined,
                          size: DocketColors.iconSize, color: docketColors.dueEvening, semanticLabel: 'Section'),
                      child: DropdownButtonFormField<int?>(
                        key: const ValueKey('section'),
                        value: task.sectionId,
                        items: selectedProject.sections.map((item) {
                          return DropdownMenuItem(
                              value: item.id,
                              child: Row(children: [
                                Text(item.name),
                              ]));
                        }).toList(),
                        decoration: InputDecoration(
                          hintText: "No section",
                          suffixIcon: task.sectionId == null
                            ? null
                            : IconButton(
                              icon: const Icon(Icons.clear),
                              onPressed: () => setState(() {
                                task.sectionId = null;
                              }),
                            ),
                        ),
                        onChanged: (int? value) {
                          setState(() {
                            task.sectionId = value;
                          });
                        },
                      ),
                    );
                  }
                ),

                FormIconRow(
                  icon: Icon(Icons.calendar_today,
                      size: DocketColors.iconSize, color: docketColors.dueTomorrow, semanticLabel: 'Due on'),
                  child: DueOnInput(
                      dueOn: task.dueOn,
                      evening: task.evening,
                      alignment: Alignment.centerLeft,
                      onUpdate: (dueOn, evening) {
                        setState(() {
                          task.dueOn = dueOn;
                          task.evening = evening;
                        });
                      }),
                ),

                FormIconRow(
                    icon: Icon(Icons.description_outlined,
                        size: DocketColors.iconSize, color: docketColors.dueFortnight, semanticLabel: 'Notes'),
                    child: MarkdownInput(
                        key: const ValueKey('body'),
                        value: task.body,
                        onChange: (value) {
                          setState(() {
                            task.body = value;
                          });
                        })),

                _buildSubtasks(context, task),
              ]));
        });
  }
}
