import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/taskitem.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/theme.dart';
import 'package:docket/screens/projectcompleted_view_model.dart';

class ProjectCompletedScreen extends StatefulWidget {
  final Project project;

  const ProjectCompletedScreen(this.project, {super.key});

  @override
  State<ProjectCompletedScreen> createState() => _ProjectCompletedScreenState();
}

class _ProjectCompletedScreenState extends State<ProjectCompletedScreen> {
  late ProjectCompletedViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<ProjectCompletedViewModel>(context, listen: false);
    viewmodel.setSlug(widget.project.slug);

    _refresh(viewmodel);
  }

  Future<void> _refresh(ProjectCompletedViewModel viewmodel) {
    return viewmodel.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<ProjectCompletedViewModel>(builder: (context, viewmodel, child) {
      viewmodel.setSlug(widget.project.slug);
      if (viewmodel.loading) {
        return buildWrapper(context: context, project: widget.project, child: const LoadingIndicator());
      }
      return buildWrapper(
          context: context,
          project: widget.project,
          child: ListView.builder(
              itemCount: viewmodel.tasks.length,
              prototypeItem: TaskItem(
                task: viewmodel.tasks.isNotEmpty ? viewmodel.tasks.first : Task.blank(), 
                showDate: true
              ),
              itemBuilder: (BuildContext context, int index) {
                return TaskItem(task: viewmodel.tasks[index], showDate: true);
              },
            ));
    });
  }

  Widget buildWrapper({required BuildContext context, required Widget child, required Project project}) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: getProjectColor(project.color),
        title: Text("Completed ${project.name} Tasks"),
        leading: IconButton(onPressed: () {
          Navigator.pop(context);
        }, icon: const Icon(Icons.arrow_back)),
      ),
      drawer: const AppDrawer(),
      body: RefreshIndicator(
        onRefresh: () => _refresh(viewmodel),
        child: child,
      ),
    );
  }
}
