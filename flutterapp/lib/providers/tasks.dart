import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';

enum ViewNames {
  today,
  upcoming,
  project,
}

/// I'm trying to keep the update methods have a 1:1
/// mapping with an `actions.` function. I think this
/// will be useful in the future if I want to have
/// remote API buffering or retries.
class TasksProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;
  Set<ViewNames> _pending = {};


  TasksProvider(LocalDatabase database, this.session) {
    _database = database;
    _pending = {};
  }

  void setSession(SessionProvider session) {
    this.session = session;
  }

  Future<void> clear() async {
    await _database.clearTasks();
    await _database.clearExpired();
    notifyListeners();
  }

  /// Get a task from the local database or server if
  /// it doesn't exist locally.
  Future<Task> getById(int id) async {
    late Task? task;
    try {
      task = await _database.fetchTaskById(id);
    } catch (e) {
      rethrow;
    }
    task ??= await actions.fetchTaskById(session!.apiToken, id);
    await _database.addTasks([task]);

    return task;
  }

  /// Create a task on the server and notify listeners.
  Future<Task> createTask(Task task) async {
    task = await actions.createTask(session!.apiToken, task);

    await _database.addTasks([task]);
    notifyListeners();

    return task;
  }

  /// Fetch tasks for today view from the server.
  /// Will notifyListeners() on completion.
  Future<void> fetchToday() async {
    _pending.add(ViewNames.today);
    var taskViewData = await actions.loadTodayTasks(session!.apiToken);
    _pending.remove(ViewNames.today);

    await _database.setToday(taskViewData);
    notifyListeners();
  }

  /// Get the local database state for today view.
  Future<TaskViewData> getToday() async {
    var taskView = await _database.getToday();
    if (_pending.contains(ViewNames.today)) {
      taskView.loading = true;
    }
    return taskView;
  }

  /// Fetch tasks for upcoming view from the server.
  /// Will notifyListeners() on completion.
  Future<void> fetchUpcoming() async {
    var taskViewData = await actions.loadUpcomingTasks(session!.apiToken);

    await _database.setUpcomingTasks(taskViewData.tasks);
    await _database.setUpcomingCalendarItems(taskViewData.calendarItems);
    notifyListeners();
  }

  // Get the locally cached upcoming tasks.
  Future<TaskViewData> getUpcoming() async {
    var tasks = await _database.fetchUpcomingTasks();
    var calendarItems = await _database.fetchUpcomingCalendarItems();

    return TaskViewData(tasks: tasks, calendarItems: calendarItems);
  }

  Future<void> fetchProjectTasks(String projectSlug) async {
    var projectDetails = await actions.fetchProjectBySlug(session!.apiToken, projectSlug);
    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);
    notifyListeners();
  }

  /// Get a list of projects for a given task
  Future<List<Task>> projectTasks(String projectSlug) async {
    return await _database.fetchProjectTasks(projectSlug);
  }

  /// Flip task.completed and persist to the server.
  Future<void> toggleComplete(Task task) async {
    // Update the completed state
    task.completed = !task.completed;

    // Update local db and server
    await actions.toggleTask(session!.apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Create or Update a task on the server and local state.
  Future<Task> updateTask(Task task) async {
    task = await actions.updateTask(session!.apiToken, task);
    await _database.updateTask(task);

    notifyListeners();
    return task;
  }

  /// Delete a task from local database and the server.
  Future<void> deleteTask(Task task) async {
    await actions.deleteTask(session!.apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  Future<void> move(Task task, Map<String, dynamic> updates) async {
    await actions.moveTask(session!.apiToken, task, updates);
  }
}
