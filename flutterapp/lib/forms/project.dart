import 'package:flutter/material.dart';

import 'package:docket/models/project.dart';
import 'package:docket/theme.dart';

class ProjectForm extends StatefulWidget {
  final Project project;
  final void Function(Project project) onSave;

  const ProjectForm({
    required this.project,
    required this.onSave, 
    super.key
  });

  @override
  State<ProjectForm> createState() => _ProjectFormState();
}

class _ProjectFormState extends State<ProjectForm> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  late final Project project;

  @override
  void initState() {
    super.initState();
    project = widget.project.copy();
  }


  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextFormField(
            decoration: const InputDecoration(
              border: OutlineInputBorder(),
              labelText: 'Name',
            ),
            validator: (String? value) {
              return (value != null && value.isNotEmpty) 
                  ? null
                  : 'Project name required';
            },
            initialValue: project.name,
            onSaved: (value) {
              if (value != null) {
                project.name = value;
              }
            }
          ),
          SizedBox(height: space(2)),
          DropdownButtonFormField(
            key: const ValueKey('color'),
            value: project.color,
            decoration: const InputDecoration(
              border: OutlineInputBorder(),
              labelText: 'Color'
            ),
            onChanged: (int? value) {
              if (value != null) {
                project.color = value;
              }
            },
            items: getProjectColors().map((item) {
              return DropdownMenuItem(
                value: item.id,
                child: Row(
                  children: [
                    Icon(Icons.circle, color: item.color, size: 12),
                    SizedBox(width: space(1)),
                    Text(
                      item.name,
                      style: const TextStyle(color: Colors.black54),
                    ),
                  ]
                )
              );
            }).toList(),
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
                    widget.onSave(project);
                  }
                }
              )
            ]
          )
        ]
      )
    );
  }
}
