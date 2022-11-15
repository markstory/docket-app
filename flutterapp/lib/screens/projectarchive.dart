import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';

class ProjectArchiveScreen extends StatefulWidget {
  const ProjectArchiveScreen({super.key});

  @override
  State<ProjectArchiveScreen> createState() => _ProjectArchiveScreenState();
}

class _ProjectArchiveScreenState extends State<ProjectArchiveScreen> {
  @override
  void initState() {
    super.initState();

    _refresh();
  }

  Future<void> _refresh() {
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);

    return projectsProvider.fetchArchived();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectsProvider>(builder: (context, projectsProvider, child) {
      var projectFuture = projectsProvider.getArchived();
      var colors = getCustomColors(context);

      return Scaffold(
          appBar: AppBar(
            backgroundColor: colors.disabledText,
            title: const Text('Archived Projects'),
          ),
          drawer: const AppDrawer(),
          body: FutureBuilder<List<Project>?>(
              future: projectFuture,
              builder: (context, snapshot) {
                if (snapshot.hasError) {
                  return const Card(child: Text("Something terrible happened"));
                }
                var projects = snapshot.data;
                if (projects == null) {
                  return const LoadingIndicator();
                }
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView(
                    children: projects
                        .map((project) => ListTile(
                              title: ProjectBadge(text: project.name, color: project.color),
                              trailing: ArchivedProjectActions(project),
                            ))
                        .toList(),
                  ),
                );
              }));
    });
  }
}

enum Menu { unarchive, delete }

class ArchivedProjectActions extends StatelessWidget {
  final Project project;

  const ArchivedProjectActions(this.project, {super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;
    var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);
    var messenger = ScaffoldMessenger.of(context);

    Future<void> handleDelete() async {
      try {
        await projectsProvider.delete(project);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Project Deleted'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not delete project task'));
      }
    }

    Future<void> handleUnarchive() async {
      try {
        await projectsProvider.unarchive(project);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Project Unarchived'));
      } catch (e) {
        messenger.showSnackBar(errorSnackBar(context: context, text: 'Could not unarchive project'));
      }
    }

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.unarchive: handleUnarchive,
        Menu.delete: handleDelete,
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.unarchive,
          child: ListTile(
            leading: Icon(Icons.edit, color: customColors.actionEdit),
            title: const Text('Un-archive'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.delete,
          child: ListTile(
            leading: Icon(Icons.delete, color: customColors.actionDelete),
            title: const Text('Delete'),
          ),
        ),
      ];
    });
  }
}
