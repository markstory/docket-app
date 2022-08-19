import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/components/projectactions.dart';
import 'package:docket/grouping.dart' as grouping;
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class ProjectDetailsScreen extends StatefulWidget {
  static const routeName = '/projects/{slug}';

  final String slug;

  const ProjectDetailsScreen(this.slug, {super.key});

  @override
  State<ProjectDetailsScreen> createState() => _ProjectDetailsScreenState();
}

class _ProjectDetailsScreenState extends State<ProjectDetailsScreen> {
  @override
  void initState() {
    super.initState();
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    projectsProvider.fetchBySlug(widget.slug);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, TasksProvider>(builder: (context, projectsProvider, tasksProvider, child) {
      var theme = Theme.of(context);
      var projectFuture = projectsProvider.getBySlug(widget.slug);

      return Scaffold(
        appBar: AppBar(title: const Text('Project Details')),
        drawer: const AppDrawer(),
        body: FutureBuilder<ProjectWithTasks?>(
            future: projectFuture,
            builder: (context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text("Something terrible happened"));
              }
              var project = snapshot.data;
              if (project == null || project.pending || project.missingData) {
                if (project?.missingData ?? false) {
                  projectsProvider.fetchBySlug(widget.slug);
                }
                return const LoadingIndicator();
              }

              List<Widget> children = [
                Row(children: [
                  SizedBox(width: space(2)),
                  Text(project.project.name, style: theme.textTheme.titleLarge),
                  TaskAddButton(projectId: project.project.id),
                  const Spacer(),
                  ProjectActions(project.project),
                ]),
              ];
              var sectionGroups = grouping.groupTasksBySection(project.project.sections, project.tasks);
              for (var sectionData in sectionGroups) {
                var section = sectionData.section;
                if (section != null) {
                  // TODO make a better section header.
                  children.add(Text(section.name));
                }
                children.add(TaskGroup(tasks: sectionData.tasks, showDate: true));
              }
              return ListView(
                padding: EdgeInsets.all(space(1)),
                children: children,
              );
            }),
      );
    });
  }
}
