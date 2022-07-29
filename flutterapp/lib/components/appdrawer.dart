import 'package:docket/screens/projectadd.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/projectsorter.dart';
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

    /// Ensure that projects are loaded each time the sidebar is opened.
    projectsProvider.fetchProjects(session.apiToken);
  }

  @override
  Widget build(BuildContext context) {
     return Consumer<ProjectsProvider>(
      builder: (context, projectsProvider, child) {
        var theme = Theme.of(context);
        var customColors = theme.extension<DocketColors>()!;

        return Drawer(
          child: ListView(
            shrinkWrap: true,
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
                leading: Icon(Icons.calendar_today, color: customColors.dueTomorrow),
                title: const Text('Upcoming'),
              ),
              ListTile(
                title: Text('Projects', style: theme.textTheme.subtitle1),
              ),
              const ProjectSorter(),
              ListTile(
                title: Text('Add Project', style: TextStyle(color: theme.colorScheme.primary)),
                onTap: () {
                  Navigator.pushNamed(context, ProjectAddScreen.routeName);
                }
              ),
              ListTile(
                title: Text('Archived Projects', style: TextStyle(color: customColors.dueNone)),
                onTap: () {
                  Navigator.pushNamed(context, '/projects/add');
                }
              ),
            ]
          )
        );
      }
    );
  }
}

