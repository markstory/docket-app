import 'dart:developer' as developer;
import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/forms/project.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class ProjectEditScreen extends StatefulWidget {
  static const routeName = '/projects/{slug}/edit/';

  final String slug;

  const ProjectEditScreen(this.slug, {super.key});

  @override
  State<ProjectEditScreen> createState() => _ProjectEditScreenState();
}

class _ProjectEditScreenState extends State<ProjectEditScreen> {
  @override
  void initState() {
    super.initState();
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    // TODO this should probably refresh the project summary and not the project
    // details.
    projectsProvider.fetchBySlug(widget.slug);
  }

  @override
  Widget build(BuildContext context) {
    var projects = Provider.of<ProjectsProvider>(context, listen: false);

    void _saveProject(BuildContext context, Project project) async {
      var messenger = ScaffoldMessenger.of(context);

      void complete() {
        Navigator.pop(context);
      }

      try {
        messenger.showSnackBar(const SnackBar(content: Text('Saving')));
        await projects.updateProject(project);
        messenger.showSnackBar(const SnackBar(content: Text('Project updated')));
        complete();
      } catch (e, stacktrace) {
        developer.log("Failed to update project ${e.toString()} $stacktrace");
        messenger.showSnackBar(
          const SnackBar(content: Text('Failed to update project')),
        );
      }
    }
    var projectFuture = projects.getBySlug(widget.slug);

    return Scaffold(
      appBar: AppBar(title: const Text('Update Project')),
      body: Container(
        padding: EdgeInsets.all(space(2)),
        child: FutureBuilder<ProjectWithTasks>(
            future: projectFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text("Something terrible happened"));
              }
              var project = snapshot.data;
              if (project == null || !snapshot.hasData) {
                return const LoadingIndicator();
              }

              return ProjectForm(
                project: project.project,
                onSave: (updated) => _saveProject(context, updated),
              );
            }),
      ),
    );
  }
}
