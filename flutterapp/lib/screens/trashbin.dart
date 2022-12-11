import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodel/trashbin.dart';

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

    _refresh(viewmodel);
  }

  Future<void> _refresh(TrashbinViewModel viewmodel) {
    return viewmodel.refresh();
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
            child: ListView(
              children: viewmodel.tasks
                  .map(
                    (task) => TaskItem(task: task, showProject: true, showDate: true, showRestore: true),
                  )
                  .toList(),
            ));
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
