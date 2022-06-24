import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/grouping.dart' as grouping;


class UpcomingScreen extends StatelessWidget {
  static const routeName = '/tasks/upcoming';

  const UpcomingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasks, child) {
        var session = Provider.of<SessionProvider>(context);
        var taskList = tasks.upcomingTasks(session.apiToken);
        var theme = Theme.of(context);

        return Scaffold(
          appBar: AppBar(),
          body: ListView(
            children: [
              Text('Upcoming', style: theme.textTheme.headlineSmall),
              FutureBuilder<List<Task>>(
                future: taskList,
                builder: (context, snapshot) {
                  var data = snapshot.data;
                  if (data == null) {
                    return const LoadingIndicator();
                  }
                  var grouperFunc = grouping.createGrouper(DateTime.now(), 28);
                  var grouped = grouperFunc(data);

                  return Column(
                    children: grouped.map<Widget>((group) {
                      var heading = group.key;
                      if (heading.contains('evening:')) {
                        heading = 'Evening';
                      }
                      // print("$heading");
                      // print("${group.items.length}");
                      return Column(
                        children: [
                          Text(heading, style: theme.textTheme.headlineSmall),
                          TaskGroup(tasks: group.items),
                        ]
                      );
                    }).toList(),
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
