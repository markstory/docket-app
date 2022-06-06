import 'package:flutter/material.dart';

class ProjectDetailsScreen extends StatelessWidget {
  static const routeName = '/projects/{slug}';

  String slug;

  ProjectDetailsScreen(this.slug, {super.key});

  @override
  Widget build(BuildContext context) {
    // TODO figure out how to load tasks/today data.
    return Scaffold(
      appBar: AppBar(),
      body: Column(
        children: [
          Row(children: [
            Text(slug),
            IconButton(
                icon: const Icon(Icons.add),
                onPressed: () {
                  // Should show task create sheet.
                })
            ]
          ),
          // TODO add a task list sorter here.
          ElevatedButton(
            onPressed: () {
              Navigator.pushNamed(context, '/tasks/upcoming');
            },
            child: const Text('View Upcoming')
          ),
        ])
    );
  }
}

