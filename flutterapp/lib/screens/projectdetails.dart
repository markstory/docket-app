import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/taskgroup.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/providers/tasks.dart';

class ProjectDetailsScreen extends StatelessWidget {
  static const routeName = '/projects/{slug}';

  final String slug;

  const ProjectDetailsScreen(this.slug, {super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, TasksProvider>(
      builder: (context, projectsProvider, tasksProvider, child) {
        var theme = Provider.of<ThemeData>(context);
        var session = Provider.of<SessionProvider>(context);
        var projectFuture = projectsProvider.getBySlug(session.apiToken, slug); 
        var taskList = tasksProvider.projectTasks(session.apiToken, slug);

        return Scaffold(
          appBar: AppBar(title: const Text('Project Details')),
          body: FutureBuilder<Project>(
            future: projectFuture,
            builder: (context, snapshot) {
              var project = snapshot.data;
              if (project == null) {
                return const Card(
                  child: Text('404! Your project has gone missing!'),
                );
              }
              return ListView(
                children: [
                  Row(
                    children: [
                      Text(project.name, style: theme.textTheme.headlineLarge),
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
                      FutureBuilder<List<Task>>(
                        future: taskList,
                        builder: (context, snapshot) {
                          var tasks = snapshot.data;
                          if (!snapshot.hasData || tasks == null) {
                            return const LoadingIndicator(); 
                          }
                          return TaskGroup(tasks: tasks);
                        }
                      )
                    ]
                  ),
                ]
              );
            }
          ),
        );
      }
    );
  }
}

