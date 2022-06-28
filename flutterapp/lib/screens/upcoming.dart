import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskgroup.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/providers/tasks.dart';
import 'package:docket/grouping.dart' as grouping;
import 'package:docket/theme.dart';


class UpcomingScreen extends StatefulWidget {
  static const routeName = '/tasks/upcoming';

  const UpcomingScreen({super.key});

  @override
  State<UpcomingScreen> createState() => _UpcomingScreenState();
}

class _UpcomingScreenState extends State<UpcomingScreen> {
  late SessionProvider session;

  @override
  void initState() {
    super.initState();
    session = Provider.of<SessionProvider>(context, listen: false);
    var tasksProvider = Provider.of<TasksProvider>(context, listen: false);

    tasksProvider.fetchUpcoming(session.apiToken);
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<TasksProvider>(
      builder: (context, tasks, child) {
        var theme = Theme.of(context);
        var taskList = tasks.getUpcoming();

        return Scaffold(
          appBar: AppBar(),
          drawer: const AppDrawer(),
          body: ListView(
            padding: const EdgeInsets.all(4),
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
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          TaskGroupHeading(dateKey: group.key),
                          const SizedBox(height: 4),
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


class TaskGroupHeading extends StatelessWidget {
  // Uses the keys format generated by grouping.createGrouper() and Task.dateKey
  final String dateKey;

  const TaskGroupHeading({required this.dateKey, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var heading = dateKey;

    var isEvening = heading.contains('evening:');
    if (isEvening) {
      heading = 'Evening';
    }

    Widget subheading = const SizedBox(width: 0);
    if (!isEvening) {
      var dateVal = DateTime.parse('$dateKey 00:00:00');
      heading = formatters.compactDate(dateVal);
      subheading = Text(
        formatters.monthDay(dateVal),
        style: theme.textTheme.labelMedium
      );
    }

    Widget icon = const SizedBox(width: 0);
    if (!isEvening) {
      icon = IconButton(
        icon: const Icon(Icons.add),
        color: theme.splashColor,
        onPressed: () {
          // TODO add onPressed action to open new task with dateKey value set.
      });
    }

    return Column(
      children: [
        SizedBox(height: isEvening ? 4 : 32),
        Row(
          crossAxisAlignment: CrossAxisAlignment.baseline,
          textBaseline: TextBaseline.alphabetic,
          children: [
            Text(heading, style: theme.textTheme.labelLarge),
            SizedBox(width: space(0.5)),
            subheading,
            icon,
          ]
        ),
      ],
    );
  }
}