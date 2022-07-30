import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/calendaritemlist.dart';
import 'package:docket/components/floatingcreatetaskbutton.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/components/taskaddbutton.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TodayScreen extends StatefulWidget {
  static const routeName = '/tasks/today';

  const TodayScreen({super.key});

  @override
  State<TodayScreen> createState() => _TodayScreenState();
}

class _TodayScreenState extends State<TodayScreen> {

  @override
  void initState() {
    super.initState();
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchToday();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var theme = Theme.of(context);
        var customColors = getCustomColors(context);
        var taskViewData = tasksProvider.getToday();
        var today = DateUtils.dateOnly(DateTime.now());

        return Scaffold(
          appBar: AppBar(),
          drawer: const AppDrawer(),
          floatingActionButton: const FloatingCreateTaskButton(),
          body: ListView(
            padding: EdgeInsets.all(space(0.5)),
            children: [
              Row(children: [
                Icon(Icons.today, color: customColors.dueToday),
                const SizedBox(width: 4),
                Text('Today', style: theme.textTheme.titleLarge),
                TaskAddButton(dueOn: today),
                IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed:() {
                    tasksProvider.fetchToday();
                  }
                )
              ]),
              FutureBuilder<TaskViewData>(
                future: taskViewData,
                builder: (context, snapshot) {
                  var data = snapshot.data;
                  if (data == null) {
                    return const LoadingIndicator();
                  }
                  var day = data.tasks.where((task) => !task.evening).toList();
                  var evening = data.tasks.where((task) => task.evening).toList();

                  return Column(
                    children: [
                      CalendarItemList(calendarItems: data.calendarItems),
                      SizedBox(height: space(2)),
                      TaskGroup(tasks: day, showProject: true),
                      SizedBox(height: space(2)),
                      Row(children: [
                        Icon(Icons.bedtime_outlined, color: customColors.dueEvening),
                        SizedBox(width: space(0.5)),
                        Text('This Evening', style: theme.textTheme.titleLarge),
                        TaskAddButton(dueOn: today, evening: true),
                      ]),
                      TaskGroup(tasks: evening, showProject: true),
                    ]
                  );
                }
              ),
            ]
          )
        );
      }
    );
  }
}
