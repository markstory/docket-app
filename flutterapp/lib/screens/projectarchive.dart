import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/emptystate.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/projectbadge.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/projectarchive.dart';

class ProjectArchiveScreen extends StatefulWidget {
  const ProjectArchiveScreen({super.key});

  @override
  State<ProjectArchiveScreen> createState() => _ProjectArchiveScreenState();
}

class _ProjectArchiveScreenState extends State<ProjectArchiveScreen> {
  late ProjectArchiveViewModel viewmodel;

  @override
  void initState() {
    super.initState();

    viewmodel = Provider.of<ProjectArchiveViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  Widget itemList(BuildContext context) {
    if (viewmodel.projects.isEmpty) {
      return const EmptyState(
        icon: Icons.archive,
        title: 'No archived projects',
      );
    }
    return ListView(
          children: viewmodel.projects
              .map((project) => ListTile(
                    title: ProjectBadge(text: project.name, color: project.color),
                    trailing: ArchivedProjectActions(project),
                  ))
              .toList(),
        );
    }

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectArchiveViewModel>(builder: (context, viewmodel, child) {
      var colors = getCustomColors(context);
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = RefreshIndicator(
          onRefresh: () => viewmodel.refresh(),
          child: itemList(context),
        );
      }

      return Scaffold(
        appBar: AppBar(
          backgroundColor: colors.disabledText,
          title: const Text('Archived Projects'),
        ),
        drawer: const AppDrawer(),
        body: body,
      );
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

    return PopupMenuButton<Menu>(
        key: const ValueKey('archive-actions'),
        onSelected: (Menu item) {
          var actions = {
            Menu.unarchive: handleUnarchive,
            Menu.delete: handleDelete,
          };
          actions[item]?.call();
        },
        itemBuilder: (BuildContext context) {
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
