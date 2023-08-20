import 'package:docket/viewmodels/projectdetails.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/dialogs/createsection.dart';
import 'package:docket/models/project.dart';
import 'package:docket/theme.dart';
import 'package:docket/routes.dart';

enum Menu {
  archive,
  edit,
  addSection,
  viewCompleted,
}

class ProjectActions extends StatelessWidget {
  final ProjectDetailsViewModel viewmodel;

  const ProjectActions(this.viewmodel, {super.key});

  @override
  Widget build(BuildContext context) {
    var messenger = ScaffoldMessenger.of(context);

    Future<void> handleArchive() async {
      viewmodel.archive();
      messenger.showSnackBar(successSnackBar(context: context, text: 'Project Updated'));
    }

    void handleEdit() {
      Navigator.pushNamed(context, Routes.projectEdit, arguments: ProjectDetailsArguments(viewmodel.project));
    }

    void handleViewCompleted() {
      Navigator.pushNamed(context, Routes.projectCompleted, arguments: ProjectDetailsArguments(viewmodel.project));
    }

    void handleAddSection() async {
      try {
        var snackbar = successSnackBar(context: context, text: 'Section Created');
        await showCreateSectionDialog(context, viewmodel);
        messenger.showSnackBar(snackbar);
      } catch (e) {
        messenger.showSnackBar(successSnackBar(context: context, text: 'Could not create section'));
      }
    }
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(
      key: const ValueKey('project-actions'),
      onSelected: (Menu item) {
      var actions = {
        Menu.edit: handleEdit,
        Menu.archive: handleArchive,
        Menu.addSection: handleAddSection,
        Menu.viewCompleted: handleViewCompleted,
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
          value: Menu.viewCompleted,
          child: ListTile(
            leading: Icon(Icons.done, color: customColors.actionComplete),
            title: const Text('Completed Tasks'),
          ),
        ),
        const PopupMenuDivider(),
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
