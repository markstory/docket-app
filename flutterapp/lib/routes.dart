import 'package:docket/models/calendarprovider.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';

class Routes {
  static const String login = '/login';
  static const String profileSettings = '/settings/profile';

  static const String today = '/tasks/today';
  static const String upcoming = '/tasks/upcoming';
  static const String taskAdd = '/tasks/add';
  static const String taskDetails = '/tasks/view';

  static const String projectAdd = '/projects/add';
  static const String projectEdit = '/projects/edit';
  static const String projectDetails = '/projects/view';
  static const String projectArchive = '/projects/archive';
  static const String projectCompleted = '/projects/completed';

  static const String calendarList = '/calendars';
  static const String calendarDetails = '/calendars/view';
  static const String trashbin = '/trashbin';

  static String? activeRoute = '';
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

class ProjectDetailsArguments {
  final Project project;

  ProjectDetailsArguments(this.project);
}

class CalendarDetailsArguments {
  final CalendarProvider provider;

  CalendarDetailsArguments(this.provider);
}
// }}}
