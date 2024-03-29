import 'dart:developer' as developer;
import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/forms/project.dart';
import 'package:docket/models/project.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/projectedit.dart';

class ProjectEditScreen extends StatefulWidget {
  final Project project;

  const ProjectEditScreen(this.project, {super.key});

  @override
  State<ProjectEditScreen> createState() => _ProjectEditScreenState();
}

class _ProjectEditScreenState extends State<ProjectEditScreen> {

  late ProjectEditViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<ProjectEditViewModel>(context, listen: false);
    viewmodel.setSlug(widget.project.slug);
    viewmodel.loadData();
  }

  @override
  Widget build(BuildContext context) {
    Future<void> _saveProject(BuildContext context, Project project) async {
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
        await viewmodel.update(project);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Project updated'));
        complete(viewmodel.project);
      } catch (e, stacktrace) {
        developer.log("Failed to update project ${e.toString()} $stacktrace");
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Failed to update project'));
      }
    }

    return Consumer<ProjectEditViewModel>(builder: (context, viewmodel, child) {
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = ProjectForm(
          project: viewmodel.project,
          onSave: (updated) async => await _saveProject(context, updated),
        );
      }
      return Scaffold(
        appBar: AppBar(title: const Text('Update Project')),
        body: SingleChildScrollView(
          padding: EdgeInsets.all(space(2)),
          child: body,
        ),
      );
    });
  }
}
