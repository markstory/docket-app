import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/forms.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:docket/components/tasktitleinput.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class TaskForm extends StatefulWidget {
  final Task task;
  final void Function(Task task) onSave;

  const TaskForm({
    required this.task,
    required this.onSave, 
    super.key
  });

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
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              FormIconRow(
                icon: TaskCheckbox(task),
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
                  }
                )
              ),
              FormIconRow(
                icon: const Icon(Icons.folder_outlined, size: DocketColors.iconSize, semanticLabel: 'Project'),
                child: DropdownButtonFormField(
                  key: const ValueKey('project'),
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Project'
                  ),
                  value: task.projectId,
                  items: projects.map((item) {
                    var color = getProjectColor(item.color);

                    return DropdownMenuItem(
                      value: item.id,
                      child: Row(
                        children: [
                          Icon(Icons.circle, color: color, size: 12),
                          SizedBox(width: space(1)),
                          Text(
                            item.name,
                            style: const TextStyle(color: Colors.black54),
                          ),
                        ]
                      )
                    );
                  }).toList(),
                  onChanged: (int? value) {
                    if (value != null) {
                      task.projectId = value;
                    }
                  }
                ),
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
                  }
                ),
              ),
              FormIconRow(
                icon: const Icon(
                  Icons.description,
                  size: DocketColors.iconSize,
                  semanticLabel: 'Notes'
                ),
                child: MarkdownInput(
                  key: const ValueKey('body'),
                  value: task.body,
                  onChange: (value) {
                    setState(() {
                      task.body = value;
                    });
                  }
                )
              ),
              ButtonBar(
                children: [
                  TextButton(
                    child: const Text('Cancel'),
                    onPressed: () {
                      Navigator.pop(context);
                    }
                  ),
                  ElevatedButton(
                    child: const Text('Save'),
                    onPressed: () async {
                      if (_formKey.currentState!.validate()) {
                        _formKey.currentState!.save();
                        widget.onSave(task);
                      }
                    }
                  )
                ]
              )
            ]
          )
        );
      }
    );
  }
}
