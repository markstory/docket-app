import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:drag_and_drop_lists/drag_and_drop_lists.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';
import 'package:docket/screens/today_view_model.dart';

class TodayScreen extends StatefulWidget {
  const TodayScreen({super.key});

  @override
  State<TodayScreen> createState() => _TodayScreenState();
}

class _TodayScreenState extends State<TodayScreen> {
  Task? _newTask;

  late TodayViewModel viewmodel;

  @override
  void initState() {
    super.initState();

    var session = Provider.of<SessionProvider>(context, listen: false);
    viewmodel = TodayViewModel(session.database, session);
    _refresh();
  }

  Future<void> _refresh() async {
    var today = DateUtils.dateOnly(DateTime.now());
    _newTask = Task.blank(dueOn: today);

    return viewmodel.refresh();
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
          throw "Invalid theme mode encountered";
      }
      SystemChrome.setSystemUIOverlayStyle(theme.appBarTheme.systemOverlayStyle!);
    };

    return ChangeNotifierProvider<TodayViewModel>.value(
        value: viewmodel,
        child: Consumer<TodayViewModel>(builder: (context, vm, child) {
          return buildScreen(context);
        }));
  }

  Widget buildScreen(BuildContext context) {
    Widget body;
    if (viewmodel.loading) {
      body = const LoadingIndicator();
    } else {
      body = RefreshIndicator(
          onRefresh: _refresh,
          child: TaskSorter(
              taskLists: viewmodel.taskLists,
              overdue: viewmodel.overdue,
              buildItem: (Task task) {
                return TaskItem(task: task, showProject: true);
              },
              onItemReorder: (int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
                await viewmodel.reorderTask(oldItemIndex, oldListIndex, newItemIndex, newListIndex);
              },
              onItemAdd: (DragAndDropItem newItem, int listIndex, int itemIndex) async {
                var itemChild = newItem.child as TaskItem;
                var task = itemChild.task;

                await viewmodel.moveOverdue(task, listIndex, itemIndex);
              }));
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Today'),
      ),
      drawer: const AppDrawer(),
      floatingActionButton: FloatingCreateTaskButton(task: _newTask),
      body: body,
    );
  }
}
