import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';

class TodayScreen extends StatelessWidget {
  static const routeName = '/tasks/today';

  const TodayScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasks, child) {
        var session = Provider.of<SessionProvider>(context);
        var taskList = tasks.todayTasks(session.apiToken);
        var theme = Theme.of(context);
        var customColors = theme.extension<DocketColors>()!;

        return Scaffold(
          appBar: AppBar(),
          body: Column(
            children: [
              Row(children: [
                Icon(Icons.calendar_today, color: customColors.dueToday),
                SizedBox(width: 4),
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
                    tasks.refreshTodayTasks(session.apiToken);
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
                  var tasks = data.where((task) => !task.evening).toList();
                  return TaskGroup(tasks: tasks);
                }
              ),
              Row(children: [
                Icon(Icons.bedtime, color: customColors.dueEvening),
                SizedBox(width: 4),
                Text('This Evening', style: theme.textTheme.headlineSmall),
                IconButton(
                    icon: const Icon(Icons.add),
                    onPressed: () {
                      // Should show task create sheet.
                    })
              ]),
              // This Evening Task List
              FutureBuilder<List<Task>>(
                future: taskList,
                builder: (context, snapshot) {
                  var data = snapshot.data;
                  if (data == null) {
                    return const LoadingIndicator();
                  }
                  var tasks = data.where((task) => task.evening).toList();
                  return TaskGroup(tasks: tasks);
                }
              ),
              ElevatedButton(
                onPressed: () {
                  Navigator.pushNamed(context, '/projects/home');
                },
                child: const Text('View Home project')
              ),
            ]
          )
        );
      }
    );
  }
}
