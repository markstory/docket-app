import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';

class Routes {
  static const String login = '/login';
  static const String today = '/tasks/today';
  static const String upcoming = '/tasks/upcoming';
  static const String taskAdd = '/tasks/add';
  static const String taskDetails = '/tasks/view';

  static const String projectAdd = '/projects/add';
  static const String projectEdit = '/projects/edit';
  static const String projectDetails = '/projects/view';
  static const String projectArchive = '/projects/archive';
}

// Route Parameter Classes {{{
class TaskAddArguments {
  final Task task;

  TaskAddArguments(this.task);
}

class TaskDetailsArguments {
  final Task task;

  TaskDetailsArguments(this.task);
}

class ProjectEditArguments {
  final Project project;

  ProjectEditArguments(this.project);
}

class ProjectDetailsArguments {
  final Project project;

  ProjectDetailsArguments(this.project);
}
// }}}
