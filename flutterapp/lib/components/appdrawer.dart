import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class AppDrawer extends StatefulWidget {
  const AppDrawer({super.key});

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {
  @override
  void initState() {
    super.initState();

    var session = Provider.of<SessionProvider>(context, listen: false);
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    projectsProvider.fetchProjects(session.apiToken);
  }

  @override
  Widget build(BuildContext context) {
     return Consumer<ProjectsProvider>(
      builder: (context, projectsProvider, child) {
        var theme = Theme.of(context);
        var customColors = theme.extension<DocketColors>()!;

        var projectsFuture = projectsProvider.getProjects();

        return Drawer(
          child: ListView(
            padding: EdgeInsets.zero,
            children: [
              const DrawerHeader(
                child: Text('Docket'),
              ),
              ListTile(
                onTap: () {
                  Navigator.pushNamed(context, '/tasks/today');
                },
                leading: Icon(Icons.today, color: customColors.dueToday),
                title: const Text('Today'),
              ),
              ListTile(
                onTap: () {
                  Navigator.pushNamed(context, '/tasks/upcoming');
                },
                leading: Icon(Icons.calendar_today, color: customColors.dueToday),
                title: const Text('Upcoming'),
              ),
              ListTile(
                title: Text('Projects', style: theme.textTheme.subtitle1),
              ),
              FutureBuilder<List<Project>>(
                future: projectsFuture,
                builder: (context, snapshot) {
                  var projects = snapshot.data;
                  if (snapshot.hasData == false || projects == null) {
                    return const LoadingIndicator();
                  }
                  return Column(
                    children: projects.map((project) {
                      return ProjectItem(project: project);
                    }).toList(),
                  );
                }
              ),
              TextButton.icon(
                icon: const Icon(Icons.add),
                label: const Text('Add Project'),
                onPressed: () {
                  // TODO Show new project sheet.
                }
              ),
              TextButton(
                child: const Text('Archived Projects'),
                onPressed: () {
                  // TODO Show archived projects list.
                }
              )
            ]
          )
        );
      }
    );
  }
}

class ProjectItem extends StatelessWidget {
  final Project project;

  const ProjectItem({required this.project, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var color = getProjectColor(project.color);
    return ListTile(
      onTap: () {
        Navigator.pushNamed(context, '/projects/${project.slug}');
      },
      leading: Icon(Icons.circle, color: color),
      title: Text(project.name),
      trailing: Text(
        project.incompleteTaskCount.toString(),
        style: TextStyle(color: theme.disabledColor),
      ),
    );
  }
}
