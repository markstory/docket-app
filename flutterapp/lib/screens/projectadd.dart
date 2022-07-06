import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/forms/project.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class ProjectAddScreen extends StatelessWidget {
  static const routeName = '/projects/add';

  final Project project = Project.blank();

  ProjectAddScreen({super.key});

  @override
  Widget build(BuildContext context) {
    void _saveProject(BuildContext context, Project project) async {
      var messenger = ScaffoldMessenger.of(context);
      var session = Provider.of<SessionProvider>(context, listen: false);
      var projects = Provider.of<ProjectsProvider>(context, listen: false);

      void complete() { 
        Navigator.pop(context); 
      }

      try {
        messenger.showSnackBar(
          const SnackBar(content: Text('Saving'))
        );
        print("project data ${project.toMap()}");
        await projects.createProject(session.apiToken, project);
        messenger.showSnackBar(
          const SnackBar(content: Text('Project Created'))
        );
        complete();
      } catch (e, stacktrace) {
        print("Failed to create project ${e.toString()} $stacktrace");
        messenger.showSnackBar(
          const SnackBar(content: Text('Failed to create project')),
        );
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('New Project')),
      body: Container(
        padding: EdgeInsets.all(space(2)),
        child: ProjectForm(
          project: project,
          onSave: (updated) => _saveProject(context, updated),
        )
      )
    );
  }
}
