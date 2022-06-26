import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';

class ProjectDetailsScreen extends StatelessWidget {
  static const routeName = '/projects/{slug}';

  String slug;

  ProjectDetailsScreen(this.slug, {super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectsProvider>(
      builder: (context, projectsProvider, child) {
        var theme = Provider.of<ThemeData>(context);
        var session = Provider.of<SessionProvider>(context);
        var projectFuture = projectsProvider.getBySlug(session.apiToken, slug); 

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
                      )
                      // TODO Show Tasks. Probably need a multi consumer
                      // stacked at the top 
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

