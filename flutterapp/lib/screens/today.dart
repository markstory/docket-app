import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/providers/session.dart';
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
  late SessionProvider session;

  @override
  void initState() {
    super.initState();
    session = Provider.of<SessionProvider>(context, listen: false);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchToday(session.apiToken);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasksProvider, child) {
        var theme = Theme.of(context);
        var customColors = getCustomColors(context);
        var taskList = tasksProvider.getToday();

        return Scaffold(
          appBar: AppBar(),
          drawer: const AppDrawer(),
          body: ListView(
            padding: EdgeInsets.all(space(0.5)),
            children: [
              Row(children: [
                Icon(Icons.today, color: customColors.dueToday),
                const SizedBox(width: 4),
                Text('Today', style: theme.textTheme.headlineSmall),
                IconButton(
                  icon: const Icon(Icons.add),
                  onPressed: () {
                    // Should show task create sheet.
                  }
                ),
                IconButton(
                  icon: const Icon(Icons.refresh),
                  onPressed:() {
                    tasksProvider.fetchToday(session.apiToken);
                  }
                )
              ]),
              FutureBuilder<List<Task>>(
                future: taskList,
                builder: (context, snapshot) {
                  var data = snapshot.data;
                  if (data == null) {
                    return const LoadingIndicator();
                  }
                  var day = data.where((task) => !task.evening).toList();
                  var evening = data.where((task) => task.evening).toList();

                  return Column(
                    children: [
                      TaskGroup(tasks: day),
                      Row(children: [
                        Icon(Icons.bedtime_outlined, color: customColors.dueEvening),
                        SizedBox(width: space(0.5)),
                        Text('This Evening', style: theme.textTheme.headlineSmall),
                        IconButton(
                          icon: const Icon(Icons.add),
                          onPressed: () {
                            // Should show task create sheet.
                          }
                        )
                      ]),
                      TaskGroup(tasks: evening),
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