// import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

// import 'package:docket/components/appdrawer.dart';
// import 'package:docket/components/taskgroup.dart';
// import 'package:docket/models/project.dart';
// import 'package:docket/models/task.dart';
// import 'package:docket/providers/session.dart';
import 'package:docket/providers/projects.dart';
import 'package:docket/providers/tasks.dart';
// import 'package:docket/theme.dart';

class ProjectAddScreen extends StatefulWidget {
  static const routeName = '/projects/add';

  const ProjectAddScreen({super.key});

  @override
  State<ProjectAddScreen> createState() => _ProjectAddScreenState();
}

class _ProjectAddScreenState extends State<ProjectAddScreen> {
  late TextEditingController _controller;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _saveProject() {
    // TODO save things
  }

  @override
  Widget build(BuildContext context) {
    return Consumer2<ProjectsProvider, TasksProvider>(
      builder: (context, projectsProvider, tasksProvider, child) {
        // var session = Provider.of<SessionProvider>(context);
        // var theme = Theme.of(context);
        // var projectFuture = projectsProvider.getBySlug(widget.slug); 

        return Scaffold(
          appBar: AppBar(title: const Text('New Project')),
          body: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Name'
                  ),
                  validator: (String? value) {
                    return (value != null && value.isNotEmpty) 
                        ? null
                        : 'Project name required';
                  }
                ),
                TextFormField(
                  decoration: const InputDecoration(
                    border: OutlineInputBorder(),
                    labelText: 'Color'
                  ),
                ),
                ButtonBar(
                  children: [
                    TextButton(
                      child: const Text('Cancel'),
                      onPressed: () {
                        Navigator.pop(context);
                      }
                    ),
                    ElevatedButton(
                      child: const Text('Save'),
                      onPressed: () {
                        _saveProject();
                      }
                    )
                  ]
                )
              ]
            )
          )
        );
      }
    );
  }
}
