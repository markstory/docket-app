import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/theme.dart';

class TrashbinScreen extends StatefulWidget {
  const TrashbinScreen({super.key});

  @override
  State<TrashbinScreen> createState() => _TrashbinScreenState();
}

class _TrashbinScreenState extends State<TrashbinScreen> {
  @override
  void initState() {
    super.initState();

    _refresh();
  }

  Future<void> _refresh() {
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    return tasksProvider.fetchTrashbin();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(builder: (context, tasksProvider, child) {
      var tasksFuture = tasksProvider.getTrashbin();
      var colors = getCustomColors(context);

      return Scaffold(
          appBar: AppBar(
            backgroundColor: colors.disabledText,
            title: const Text('Trash Bin'),
          ),
          drawer: const AppDrawer(),
          body: FutureBuilder<TaskViewData?>(
              future: tasksFuture,
              builder: (context, snapshot) {
                if (snapshot.hasError) {
                  return const Card(child: Text("Something terrible happened"));
                }
                var data = snapshot.data;
                if (data == null) {
                  return const LoadingIndicator();
                }
                return RefreshIndicator(
                  onRefresh: _refresh,
                  child: ListView(
                    children: data.tasks
                        .map(
                          (task) => TaskItem(task: task, showProject: true, showDate: true, showRestore: true),
                        )
                        .toList(),
                  ),
                );
              }));
    });
  }
}
