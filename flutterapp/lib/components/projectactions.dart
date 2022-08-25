import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/dialogs/createsection.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/theme.dart';
import 'package:docket/routes.dart';

enum Menu {
  archive,
  edit,
  addSection,
}

class ProjectActions extends StatelessWidget {
  final Project project;

  const ProjectActions(this.project, {super.key});

  @override
  Widget build(BuildContext context) {
    var projectProvider = Provider.of<ProjectsProvider>(context);
    var messenger = ScaffoldMessenger.of(context);

    Future<void> _handleArchive() async {
      projectProvider.archive(project);
      messenger.showSnackBar(successSnackBar(context: context, text: 'Project Updated'));
    }

    void _handleEdit() {
      Navigator.pushNamed(context, Routes.projectEdit, arguments: ProjectEditArguments(project));
    }

    void _handleAddSection() async {
      try {
        await showCreateSectionDialog(context, project);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Section Created'));
      } catch (e) {
        messenger.showSnackBar(successSnackBar(context: context, text: 'Could not create section'));
      }
    }
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.edit: _handleEdit,
        Menu.archive: _handleArchive,
        Menu.addSection: _handleAddSection,
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.edit,
          child: ListTile(
            leading: Icon(Icons.edit_outlined, color: customColors.actionEdit),
            title: const Text('Edit Project'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.addSection,
          child: ListTile(
            leading: Icon(Icons.add, color: customColors.actionComplete),
            title: const Text('Add Section'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.archive,
          child: ListTile(
            leading: Icon(Icons.archive_outlined, color: customColors.dueNone),
            title: const Text('Archive'),
          ),
        ),
      ];
    });
  }
}
