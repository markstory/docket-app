import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

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
              TaskGroup(taskList),
              // TODO add a task list sorter here.
              Row(children: [
                const Icon(Icons.mode_night),
                const Text('This Evening'),
                IconButton(
                    icon: const Icon(Icons.add),
                    onPressed: () {
                      // Should show task create sheet.
                    })
              ]),
              // TODO add a task list sorter here.
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

class TaskGroup extends StatelessWidget {
  const TaskGroup(this.taskList, {Key? key}) : super(key: key);

  final Future<List<Task>> taskList;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<Task>>(
      future: taskList,
      builder: (context, snapshot) {
        var tasks = snapshot.data;
        if (tasks != null && tasks.isNotEmpty) {
          return Container(
            height: 200,
            child: ListView.builder(
              itemCount: tasks.length,
              itemBuilder: (BuildContext context, int index) {
                return TaskItem(tasks[index]);
              }
            )
          );
        }
        return const TasksLoading();
      }
    );
  }
}

class TasksLoading extends StatelessWidget {
  const TasksLoading({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return const SizedBox(
      width: 60,
      height: 60,
      child: CircularProgressIndicator(),
    );
  }
}

class TaskItem extends StatelessWidget {
  final Task task;

  const TaskItem(this.task, {super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 50,
      child: Text(task.title),
    );
  }
}
