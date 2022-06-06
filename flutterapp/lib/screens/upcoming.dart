import 'package:flutter/material.dart';

class UpcomingScreen extends StatelessWidget {
  static const routeName = '/tasks/upcoming';

  const UpcomingScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // TODO figure out how to load the tasks/upcoming data.
    return Scaffold(
      appBar: AppBar(),
      body: Column(
        children: [
          const Text('Upcoming'),
          Row(
            children: [
              const Text('Today'),
              IconButton(
                  icon: const Icon(Icons.add),
                  onPressed: () {
                    // Should show task create sheet.
                  })
            ]
          ),
          // TODO add a task list sorter here.
        ]
      )
    );
  }
}
