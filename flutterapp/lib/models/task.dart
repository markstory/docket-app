import 'package:clock/clock.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/calendaritem.dart';
import 'package:flutter/material.dart';

class Task {
  int? id;
  int? projectId;
  String projectSlug;
  String projectName;
  int projectColor;
  int? sectionId;
  String title;
  String body;
  DateTime? dueOn;
  int childOrder;
  int dayOrder;
  bool evening;
  bool completed;
  DateTime? deletedAt;
  List<Subtask> subtasks;
  int subtaskCount;
  int completeSubtaskCount;

  DateTime? previousDueOn;
  String? previousProjectSlug;

  Task({
    this.id,
    required this.projectId,
    required this.projectSlug,
    required this.projectName,
    required this.projectColor,
    this.sectionId,
    required this.title,
    required this.body,
    this.dueOn,
    required this.childOrder,
    required this.dayOrder,
    required this.evening,
    required this.completed,
    this.deletedAt,
    this.subtasks = const [],
    this.subtaskCount = 0,
    this.completeSubtaskCount = 0,
  });

  factory Task.blank({DateTime? dueOn, int? projectId, int? sectionId, bool evening = false}) {
    return Task(
      id: null,
      projectId: projectId,
      projectSlug: '',
      projectName: '',
      projectColor: 0,
      sectionId: sectionId,
      title: '',
      body: '',
      dueOn: dueOn,
      childOrder: 0,
      dayOrder: 0,
      evening: evening,
      completed: false,
      deletedAt: null,
      subtasks: [],
    );
  }

  factory Task.fromMap(Map<String, dynamic> json) {
    var projectId = json['project_id'];
    projectId ??= json['project']?['id'];
    var projectSlug = json['project_slug'];
    projectSlug ??= json['project']?['slug'];
    var projectColor = json['project_color'];
    projectColor ??= json['project']?['color'];
    var projectName = json['project_name'];
    projectName ??= json['project']?['name'];

    // TODO extract casting behavior into reusable functions.
    var evening = json['evening'];
    if (evening is int) {
      evening = evening == 0 ? false : true;
    }
    var completed = json['completed'];
    if (completed is int) {
      completed = completed == 0 ? false : true;
    }
    DateTime? dueOn;
    if (json['due_on'] != null) {
      dueOn = formatters.parseToLocal(json['due_on']);
    }
    DateTime? deletedAt;
    if (json['deleted_at'] != null) {
      deletedAt = formatters.parseToLocal(json['deleted_at']);
    }
    List<Subtask> subtasks = [];
    if (json['subtasks'] != null &&
        (json['subtasks'].runtimeType == List || json['subtasks'].runtimeType == List<Map<String, Object?>>)) {
      for (var item in json['subtasks']) {
        subtasks.add(Subtask.fromMap(item));
      }
    }
    var subtaskCount = json['subtask_count'] ?? 0;
    var completeSubtaskCount = json['complete_subtask_count'] ?? 0;

    return Task(
      id: json['id'],
      projectId: projectId,
      projectSlug: projectSlug,
      projectName: projectName,
      projectColor: projectColor,
      sectionId: json['section_id'],
      title: json['title'] ?? '',
      body: json['body'] ?? '',
      dueOn: dueOn,
      childOrder: json['child_order'] ?? 0,
      dayOrder: json['day_order'] ?? 0,
      evening: evening ?? false,
      completed: completed ?? false,
      deletedAt: deletedAt,
      subtasks: subtasks,
      subtaskCount: subtaskCount,
      completeSubtaskCount: completeSubtaskCount,
    );
  }

  Task copy() {
    return Task.fromMap(toMap());
  }

  Map<String, Object?> toMap() {
    String? dueOnDate;
    if (dueOn != null) {
      dueOnDate = formatters.dateString(dueOn!);
    }
    return {
      'id': id,
      'project_id': projectId,
      'project_slug': projectSlug,
      'project_name': projectName,
      'project_color': projectColor,
      'section_id': sectionId,
      'title': title,
      'body': body,
      'due_on': dueOnDate,
      'child_order': childOrder,
      'day_order': dayOrder,
      'evening': evening,
      'completed': completed,
      'deleted_at': null,
      // Filtering to exclude any pending subtasks that didn't get saved.
      'subtasks': subtasks.where((sub) => sub.id != null).map((sub) => sub.toMap()).toList(),
      'subtask_count': subtaskCount,
      'complete_subtask_count': completeSubtaskCount,
    };
  }

  String get dateKey {
    if (dueOn == null) {
      return 'No Due Date';
    }
    if (isOverdue) {
      return TaskViewData.overdueKey;
    }
    return formatters.dateString(dueOn!);
  }

  bool get isOverdue {
    var due = dueOn;
    if (due == null) {
      return false;
    }
    return due.isBefore(DateUtils.dateOnly(clock.now()));
  }

  bool get hasDueDate {
    return dueOn != null;
  }
}

class Subtask {
  int? id;
  String title = '';
  int ranking = 0;
  bool completed = false;

  Subtask({
    this.id,
    required this.title,
    this.ranking = 0,
    this.completed = false,
  });

  factory Subtask.blank({String title = ''}) {
    return Subtask(title: title);
  }

  factory Subtask.fromMap(Map<String, dynamic> json) {
    return Subtask(
      id: json['id'],
      title: json['title']?.toString() ?? '',
      ranking: json['ranking'] ?? 0,
      completed: json['completed'] ?? false,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'title': title,
      'ranking': ranking,
      'completed': completed,
    };
  }
}

/// Container type for APIs that return both tasks and calendar items
class TaskViewData {
  static const overdueKey = 'overdue';

  final List<Task> tasks;
  final List<CalendarItem> calendarItems;

  // Whether or not a data refresh is pending
  bool pending;

  // True when the cache key could not be found.
  final bool isEmpty;

  TaskViewData({
    required this.tasks,
    required this.calendarItems,
    this.pending = false,
    this.isEmpty = false,
  });

  factory TaskViewData.blank({isEmpty = false}) {
    return TaskViewData(tasks: [], calendarItems: [], isEmpty: isEmpty);
  }

  factory TaskViewData.fromMap(Map<String, dynamic> map) {
    List<Task> tasks = (map['tasks'] as List? ?? []).map((data) => Task.fromMap(data)).toList();
    List<CalendarItem> calendarItems =
        (map['calendarItems'] as List? ?? []).map((data) => CalendarItem.fromMap(data)).toList();

    return TaskViewData(
      tasks: tasks,
      calendarItems: calendarItems,
    );
  }

  String dateKey() {
    if (tasks.isEmpty) {
      return '';
    }
    var dueOn = tasks.first.dueOn;
    if (dueOn == null) {
      return '';
    }
    return formatters.dateString(dueOn);
  }

  List<Task> eveningTasks() {
    return tasks.where((item) => item.evening).toList();
  }

  List<Task> dayTasks() {
    return tasks.where((item) => item.evening == false).toList();
  }

  /// Convert a single collection into a map of TaskViewData
  /// grouped by date. Used in the upcoming view.
  DailyTasksData groupByDay({int daysToFill = 28, bool groupOverdue = false}) {
    Map<String, List<Task>> taskMap = {};
    Map<String, List<CalendarItem>> calendarMap = {};
    var start = DateUtils.dateOnly(DateTime.now());

    // Index tasks by date.
    for (var task in tasks) {
      var dueOn = task.dueOn;
      if (dueOn != null && start.isAfter(dueOn)) {
        start = dueOn;
      }
      var dateKey = task.dateKey;
      if (taskMap[dateKey] == null) {
        taskMap[dateKey] = [];
      }
      taskMap[dateKey]?.add(task);
      if (dueOn == null) {
        continue;
      }
    }
    // Index calendarItems by date.
    for (var item in calendarItems) {
      for (var dateKey in item.dateKeys()) {
        var itemList = calendarMap[dateKey];
        if (itemList == null) {
          calendarMap[dateKey] = [];
        }
        itemList?.add(item);
      }
    }

    // Use a date range to ensure all values are there.
    // This makes screens easier to build I think.
    Map<String, TaskViewData> views = {};

    if (taskMap.containsKey(TaskViewData.overdueKey)) {
      views[TaskViewData.overdueKey] = TaskViewData(
        tasks: taskMap[TaskViewData.overdueKey] ?? [],
        calendarItems: [],
      );
    }

    var current = start;
    var end = start.add(Duration(days: daysToFill));
    while (current.isBefore(end) || current == end) {
      var dateStr = formatters.dateString(current);
      views[dateStr] = TaskViewData(
        tasks: taskMap[dateStr] ?? [],
        calendarItems: calendarMap.remove(dateStr) ?? [],
      );
      current = current.add(const Duration(days: 1));
    }

    return views;
  }

  Map<String, Object?> toMap() {
    return {
      'tasks': tasks.map((task) => task.toMap()).toList(),
      'calendarItems': calendarItems.map((item) => item.toMap()).toList(),
    };
  }
}

typedef DailyTasksData = Map<String, TaskViewData>;
