import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/models/task.dart';

class TodayScreen extends StatelessWidget {
  static const routeName = '/tasks/today';

  const TodayScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasks, child) {
        var session = Provider.of<SessionProvider>(context);
        var taskList = tasks.todayTasks(session.apiToken);
        return Scaffold(
          appBar: AppBar(),
          body: Column(
            children: [
              Row(children: [
                const Icon(Icons.calendar_today),
                const Text('Today'),
                IconButton(
                    icon: const Icon(Icons.add),
                    onPressed: () {
                      // Should show task create sheet.
                    })
              ]),
              // Today Task List
              FutureBuilder<List<Task>>(
                future: taskList,
                builder: (context, snapshot) {
                  var data = snapshot.data;
                  if (data == null) {
                    return const LoadingIndicator();
                  }
                  var tasks = data.where((task) => !task.evening).toList();
                  return TaskGroup(tasks);
                }
              ),
              Row(children: [
                const Icon(Icons.mode_night),
                const Text('This Evening'),
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
                  return TaskGroup(tasks);
                }
              ),
              ElevatedButton(
                  onPressed: () {
                    Navigator.pushNamed(context, '/projects/home');
                  },
                  child: const Text('View Home project')),
            ]
          )
        );
      }
    );
  }
}
