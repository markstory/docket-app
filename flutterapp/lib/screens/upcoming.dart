import 'package:docket/screens/upcoming_view_model.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/tasks.dart';

class UpcomingScreen extends StatefulWidget {
  const UpcomingScreen({super.key});

  @override
  State<UpcomingScreen> createState() => _UpcomingScreenState();
}

class _UpcomingScreenState extends State<UpcomingScreen> {
  late UpcomingViewModel viewmodel;

  @override
  void initState() {
    super.initState();

    viewmodel = Provider.of<UpcomingViewModel>(context, listen: false);
    _refresh(viewmodel);
  }

  Future<void> _refresh(UpcomingViewModel viewmodel) async {
    return viewmodel.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<UpcomingViewModel>(
      builder: buildScreen,
    );
  }

  Widget buildScreen(BuildContext context, UpcomingViewModel viewmodel, Widget? _) {
    return Consumer<TasksProvider>(builder: (context, tasksProvider, child) {
      var theme = Theme.of(context);
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = RefreshIndicator(
          onRefresh: () => _refresh(viewmodel),
          child: TaskSorter(
              taskLists: viewmodel.taskLists,
              buildItem: (Task task) {
                return TaskItem(task: task, showDate: false, showProject: true);
              },
              onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                await viewmodel.reorderTask(oldItemIndex, oldListIndex, newItemIndex, newListIndex);
              },
              onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
                var itemChild = newItem.child as TaskItem;
                var task = itemChild.task;
                await viewmodel.insertAt(task, listIndex, itemIndex);
              }),
        );
      }

      return Scaffold(
        appBar: AppBar(backgroundColor: theme.colorScheme.secondary, title: const Text('Upcoming')),
        drawer: const AppDrawer(),
        // TODO add scroll tracking for sections and update add button.
        floatingActionButton: const FloatingCreateTaskButton(),
        body: body,
      );
    });
  }
}
