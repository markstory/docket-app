import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskcheckbox.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/forms.dart';
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
    var projectProvider = Provider.of<ProjectsProvider>(context, listen: false);
    var projectPromise = projectProvider.getProjects();

    return FutureBuilder<List<Project>>(
      future: projectPromise,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return const LoadingIndicator();
        }
        List<Project> projects = snapshot.data!;

        return Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              FormIconRow(
                icon: TaskCheckbox(task),
                child: TextFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Title',
                  ),
                  validator: (String? value) {
                    return (value != null && value.isNotEmpty) 
                        ? null
                        : 'Task title required';
                  },
                  initialValue: task.title,
                  onSaved: (value) {
                    if (value != null) {
                      task.title = value;
                    }
                  }
                ),
              ),
              FormIconRow(
                icon: const Icon(Icons.folder, size: DocketColors.iconSize, semanticLabel: 'Project'),
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
                child:TextFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Due On',
                  ),
                  onSaved: (value) {
                    // TODO replace this input.
                  }
                ),
              ),
              FormIconRow(
                icon: const Icon(Icons.description, size: DocketColors.iconSize, semanticLabel: 'Notes on'),
                child: TextFormField(
                  keyboardType: TextInputType.multiline,
                  minLines: 1,
                  maxLines: null,
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Notes',
                  ),
                  initialValue: task.body,
                  onSaved: (value) {
                    if (value != null) {
                      task.body = value;
                    }
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
