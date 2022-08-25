import 'dart:developer' as developer;
import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/project.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';

class ProjectEditScreen extends StatefulWidget {
  final Project project;

  const ProjectEditScreen(this.project, {super.key});

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
    projectsProvider.fetchBySlug(widget.project.slug);
  }

  @override
  Widget build(BuildContext context) {
    var projects = Provider.of<ProjectsProvider>(context, listen: false);

    void _saveProject(BuildContext context, Project project) async {
      var messenger = ScaffoldMessenger.of(context);

      void complete(proj) {
        if (widget.project.slug != proj.slug) {
          Navigator.pushReplacementNamed(context, Routes.projectDetails,
              arguments: ProjectDetailsArguments(proj));
          return;
        }
        Navigator.pop(context);
      }

      try {
        messenger.showSnackBar(successSnackBar(context: context, text: 'Saving'));
        project = await projects.update(project);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Project updated'));
        complete(project);
      } catch (e, stacktrace) {
        developer.log("Failed to update project ${e.toString()} $stacktrace");
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Failed to update project'));
      }
    }

    var projectFuture = projects.getBySlug(widget.project.slug);

    return Scaffold(
      appBar: AppBar(title: const Text('Update Project')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(space(2)),
        child: FutureBuilder<ProjectWithTasks>(
            future: projectFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text("Something terrible happened"));
              }
              var data = snapshot.data;
              if (data == null || !snapshot.hasData) {
                return const LoadingIndicator();
              }

              return ProjectForm(
                project: data.project,
                onSave: (updated) => _saveProject(context, updated),
              );
            }),
      ),
    );
  }
}
