import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class ProjectAddScreen extends StatefulWidget {
  static const routeName = '/projects/add';

  const ProjectAddScreen({super.key});

  @override
  State<ProjectAddScreen> createState() => _ProjectAddScreenState();
}

class _ProjectAddScreenState extends State<ProjectAddScreen> {
  late TextEditingController _controller;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  Project project = Project.blank();

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _saveProject(BuildContext context, SessionProvider session, ProjectsProvider projects) async {
    var messenger = ScaffoldMessenger.of(context);
    void complete() { 
      Navigator.pop(context); 
    }
    try {
      messenger.showSnackBar(
        const SnackBar(content: Text('Saving'))
      );
      await projects.createProject(session.apiToken, project);
      messenger.showSnackBar(
        const SnackBar(content: Text('Project Created'))
      );
      complete();
    } catch (e) {
      developer.log("Failed to create project ${e.toString()}");
      messenger.showSnackBar(
        const SnackBar(content: Text('Failed to create project')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, SessionProvider>(
      builder: (context, projectsProvider, sessionProvider, child) {
        // TODO extract this form so it can be used for updates too.
        return Scaffold(
          appBar: AppBar(title: const Text('New Project')),
          body: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Name'
                  ),
                  validator: (String? value) {
                    return (value != null && value.isNotEmpty) 
                        ? null
                        : 'Project name required';
                  },
                  onSaved: (value) {
                    if (value != null) {
                      project.name = value;
                    }
                  }
                ),
                DropdownButtonFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Color'
                  ),
                  onChanged: (int? value) {
                    if (value != null) {
                      project.color = value;
                    }
                  },
                  items: projectColors.map((item) {
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
                          _saveProject(context, sessionProvider, projectsProvider);
                        }
                      }
                    )
                  ]
                )
              ]
            )
          )
        );
      }
    );
  }
}
