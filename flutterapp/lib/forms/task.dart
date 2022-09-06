import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/forms.dart';
import 'package:docket/components/subtaskitem.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/tasktitleinput.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class TaskForm extends StatefulWidget {
  final Task task;
  final void Function(Task task) onSave;
  final void Function()? onComplete;

  const TaskForm({required this.task, required this.onSave, this.onComplete, super.key});

  @override
  State<TaskForm> createState() => _TaskFormState();
}

class _TaskFormState extends State<TaskForm> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  late final Task task;

  @override
  void initState() {
    super.initState();
    task = widget.task.copy();
  }

  /// Create the subtasks section for task details. This is a bit
  /// at odds with how the rest of the page as subtasks update immediately
  /// while other changes are deferred. Perhaps task updates should apply immediately 
  /// or as a time throttled async change?
  Widget _buildSubtasks(BuildContext context, Task task) {
    // No subtasks for unsaved tasks.
    if (task.id == null) {
      return const SizedBox(height: 0, width: 0);
    }

    var theme = Theme.of(context);
    return Column(children: [
      Text('Subtasks', style: theme.textTheme.titleSmall),
      ...task.subtasks.map<Widget>((sub) {
        return SubtaskItem(task: task, subtask: sub);
      }),
      TextButton(
        child: const Text('Add Subtask'),
        onPressed: () {
          setState(() {
            task.subtasks.add(Subtask.blank());
          });
        }
      )
    ]);
  }

  @override
  Widget build(BuildContext context) {
    var projectProvider = Provider.of<ProjectsProvider>(context);
    var projectPromise = projectProvider.getAll();

    return FutureBuilder<List<Project>>(
        future: projectPromise,
        builder: (context, snapshot) {
          List<Project> projects = [];
          if (snapshot.hasData) {
            projects = snapshot.data!;
          }

          return Form(
              key: _formKey,
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                FormIconRow (
                    icon: TaskCheckbox(task, onComplete: widget.onComplete),
                    child: TaskTitleInput(
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
                          setState(() {
                            task.projectId = projectId;
                          });
                        })),
                FormIconRow(
                  icon: const Icon(Icons.folder_outlined, size: DocketColors.iconSize, semanticLabel: 'Project'),
                  child: DropdownButtonFormField(
                      key: const ValueKey('project'),
                      decoration: const InputDecoration(border: OutlineInputBorder(), labelText: 'Project'),
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
                                style: const TextStyle(color: Colors.black54),
                              ),
                            ]));
                      }).toList(),
                      onChanged: (int? value) {
                        if (value != null) {
                          task.projectId = value;
                        }
                      }),
                ),
                FormIconRow(
                  icon: const Icon(Icons.calendar_today, size: DocketColors.iconSize, semanticLabel: 'Due on'),
                  child: DueOnInput(
                      dueOn: task.dueOn,
                      evening: task.evening,
                      onUpdate: (dueOn, evening) {
                        setState(() {
                          task.dueOn = dueOn;
                          task.evening = evening;
                        });
                      }),
                ),
                FormIconRow(
                    icon: const Icon(Icons.description_outlined, size: DocketColors.iconSize, semanticLabel: 'Notes'),
                    child: MarkdownInput(
                        key: const ValueKey('body'),
                        value: task.body,
                        onChange: (value) {
                          setState(() {
                            task.body = value;
                          });
                        })),
                _buildSubtasks(context, widget.task),
                ButtonBar(children: [
                  TextButton(
                      child: const Text('Cancel'),
                      onPressed: () {
                        Navigator.pop(context);
                      }),
                  ElevatedButton(
                      child: const Text('Save'),
                      onPressed: () async {
                        if (_formKey.currentState!.validate()) {
                          _formKey.currentState!.save();
                          widget.onSave(task);
                        }
                      })
                ])
              ]));
        });
  }
}
