import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/emptystate.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/trashbin.dart';

class TrashbinScreen extends StatefulWidget {
  const TrashbinScreen({super.key});

  @override
  State<TrashbinScreen> createState() => _TrashbinScreenState();
}

class _TrashbinScreenState extends State<TrashbinScreen> {
  late TrashbinViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<TrashbinViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  Future<void> _refresh(TrashbinViewModel viewmodel) {
    return viewmodel.refresh();
  }

  Widget itemList(BuildContext context) {
    if (viewmodel.tasks.isEmpty) {
      return const EmptyState(
        icon: Icons.delete,
        title: 'No items in trash',
        text: 'When you delete tasks they will go here for 14 days.'
          'After that time they will be deleted permanently.'
      );
    }

    return ListView(
        children: viewmodel.tasks
            .map(
              (task) => TaskItem(
                key: ValueKey(task),
                task: task, 
                showProject: true, 
                showDate: true, 
                showRestore: true
              )
            )
            .toList());
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TrashbinViewModel>(builder: (context, viewmodel, child) {
      var colors = getCustomColors(context);
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = RefreshIndicator(
          onRefresh: () => _refresh(viewmodel),
          child: itemList(context),
        );
      }

      return Scaffold(
        appBar: AppBar(
          backgroundColor: colors.disabledText,
          title: const Text('Trash Bin'),
        ),
        drawer: const AppDrawer(),
        body: body,
      );
    });
  }
}
