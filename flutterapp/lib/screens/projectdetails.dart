import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
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
    var session = Provider.of<SessionProvider>(context, listen: false);
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    projectsProvider.fetchBySlug(session.apiToken, widget.slug);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, TasksProvider>(
      builder: (context, projectsProvider, tasksProvider, child) {
        var theme = Theme.of(context);
        var projectFuture = projectsProvider.getBySlug(widget.slug); 

        return Scaffold(
          appBar: AppBar(title: const Text('Project Details')),
          drawer: const AppDrawer(),
          body: FutureBuilder<Project>(
            future: projectFuture,
            builder: (context, snapshot) {
              // Doing this query here should result in us hitting cache all the time
              var project = snapshot.data;
              if (project == null) {
                return const Card(
                  child: Text('404! Your project has gone missing!'),
                );
              }
              var taskList = tasksProvider.projectTasks(widget.slug);

              return ListView(
                children: [
                  Row(
                    children: [
                      SizedBox(width: space(2)),
                      Text(project.name, style: theme.textTheme.titleLarge),
                      IconButton(
                        icon: const Icon(Icons.add),
                        onPressed: () {
                          // Should show task create sheet.
                        }
                      ),
                      const Spacer(),
                      IconButton(
                        icon: const Icon(Icons.more_horiz),
                        onPressed: () {
                          // Show project menu!
                        }
                      ),
                    ]
                  ),
                  FutureBuilder<List<Task>>(
                    future: taskList,
                    builder: (context, snapshot) {
                      var tasks = snapshot.data;
                      if (tasks == null) {
                        return const LoadingIndicator(); 
                      }
                      return TaskGroup(tasks: tasks);
                    }
                  )
                ]
              );
            }
          ),
        );
      }
    );
  }
}
