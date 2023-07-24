import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/today.dart';

class TodayScreen extends StatefulWidget {
  const TodayScreen({super.key});

  @override
  State<TodayScreen> createState() => _TodayScreenState();
}

class _TodayScreenState extends State<TodayScreen> {
  late TodayViewModel viewmodel;

  @override
  void initState() {
    super.initState();

    viewmodel = Provider.of<TodayViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  @override
  Widget build(BuildContext context) {
    // Update the the theme based on device settings.
    // This is a bit janky but I couldn't figure out why AdaptiveTheme
    // wasn't taking care of this.
    var window = WidgetsBinding.instance.window;
    window.onPlatformBrightnessChanged = () async {
      WidgetsBinding.instance.handlePlatformBrightnessChanged();

      final mode = await AdaptiveTheme.getThemeMode();
      late ThemeData theme;
      switch (mode) {
        case AdaptiveThemeMode.dark:
          theme = darkTheme;
          break;
        case AdaptiveThemeMode.light:
          theme = lightTheme;
          break;
        case AdaptiveThemeMode.system:
          final brightness = window.platformBrightness;
          theme = brightness == Brightness.light ? lightTheme : darkTheme;
          break;
        default:
          throw Exception("Invalid theme mode encountered");
      }
      SystemChrome.setSystemUIOverlayStyle(theme.appBarTheme.systemOverlayStyle!);
    };

    return Consumer<TodayViewModel>(
      builder: buildScreen,
    );
  }

  Widget buildScreen(BuildContext context, TodayViewModel viewmodel, Widget? _) {
    Widget body;
    if (viewmodel.loading) {
      body = const LoadingIndicator();
    } else {
      body = RefreshIndicator(
          onRefresh: () => viewmodel.refresh(),
          child: TaskSorter(
                taskLists: viewmodel.taskLists,
                overdue: viewmodel.overdue,
                buildItem: (Task task) {
                  return TaskItem(key: ValueKey(task.id), task: task, showProject: true);
                },
                onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                  await viewmodel.reorderTask(oldItemIndex, oldListIndex, newItemIndex, newListIndex);
                },
                onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
                  var itemChild = newItem.child as TaskItem;
                  var task = itemChild.task;

                  await viewmodel.moveOverdue(task, listIndex, itemIndex);
                })
          );
    }

    if (viewmodel.loadError) {
      viewmodel.clearLoadError();
      WidgetsBinding.instance.addPostFrameCallback((_) {
        ScaffoldMessenger.of(context).showSnackBar(
          errorSnackBar(context: context, text: 'Could not load data from server.')
        );
      });
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Today'),
      ),
      drawer: const AppDrawer(),
      floatingActionButton: FloatingCreateTaskButton(dueOn: viewmodel.today),
      body: body,
    );
  }
}
