import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/forms/project.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class ProjectAddScreen extends StatelessWidget {
  final Project project = Project.blank();

  ProjectAddScreen({super.key});

  @override
  Widget build(BuildContext context) {
    void saveProject(BuildContext context, Project project) async {
      var messenger = ScaffoldMessenger.of(context);
      var projects = Provider.of<ProjectsProvider>(context, listen: false);

      void complete() {
        Navigator.pop(context);
      }

      try {
        messenger.showSnackBar(const SnackBar(content: Text('Saving')));
        await projects.createProject(project);
        messenger.showSnackBar(const SnackBar(content: Text('Project Created')));
        complete();
      } catch (e, stacktrace) {
        developer.log("Failed to create project ${e.toString()} $stacktrace");
        messenger.showSnackBar(
          const SnackBar(content: Text('Failed to create project')),
        );
      }
    }

    return Scaffold(
        appBar: AppBar(title: const Text('New Project')),
        body: SingleChildScrollView(
            padding: EdgeInsets.all(space(2)),
            child: ProjectForm(
              project: project,
              onSave: (updated) => saveProject(context, updated),
            )));
  }
}
