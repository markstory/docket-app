import 'package:flutter/material.dart';

class TodayScreen extends StatelessWidget {
  static const routeName = '/tasks/today';

  const TodayScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // TODO figure out how to load tasks/today data.
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
}
