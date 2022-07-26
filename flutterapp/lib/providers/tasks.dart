import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;

/// I'm trying to keep the update methods have a 1:1
/// mapping with an `actions.` function. I think this
/// will be useful in the future if I want to have
/// remote API buffering or retries.
class TasksProvider extends ChangeNotifier {
  late LocalDatabase _database;

  TasksProvider(LocalDatabase database) {
    _database = database;
  }

  Future<void> clear() async {
    await _database.clearTasks();
    await _database.clearExpired();
    notifyListeners();
  }

  Future<Task> getById(String apiToken, int id) async {
    late Task? task;
    try {
      task = await _database.fetchTaskById(id);
    } catch (e) {
      rethrow;
    }
    task ??= await actions.fetchTaskById(apiToken, id);
    await _database.addTasks([task]);

    return task;
  }

  /// Create a task on the server and notify listeners.
  Future<Task> createTask(String apiToken, Task task) async {
    task = await actions.createTask(apiToken, task);

    await _database.addTasks([task]);
    notifyListeners();

    return task;
  }

  /// Fetch tasks for today view from the server.
  /// Will notifyListeners() on completion.
  Future<void> fetchToday(String apiToken) async {
    var taskViewData = await actions.loadTodayTasks(apiToken);

    await _database.setTodayTasks(taskViewData.tasks);
    await _database.setTodayCalendarItems(taskViewData.calendarItems);
    notifyListeners();
  }

  /// Get the local database state for today view.
  Future<TaskViewData> getToday() async {
    var tasks = await _database.fetchTodayTasks();
    var calendarItems = await _database.fetchTodayCalendarItems();

    return TaskViewData(tasks: tasks, calendarItems: calendarItems);
  }

  /// Fetch tasks for upcoming view from the server.
  /// Will notifyListeners() on completion.
  Future<void> fetchUpcoming(String apiToken) async {
    var taskViewData = await actions.loadUpcomingTasks(apiToken);

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

  Future<void> fetchProjectTasks(String apiToken, String projectSlug) async {
    var projectDetails = await actions.fetchProjectBySlug(apiToken, projectSlug);
    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);
    notifyListeners();
  }

  /// Get a list of projects for a given task
  Future<List<Task>> projectTasks(String projectSlug) async {
    return await _database.fetchProjectTasks(projectSlug);
  }

  /// Flip task.completed and persist to the server.
  Future<void> toggleComplete(String apiToken, Task task) async {
    // Update the completed state
    task.completed = !task.completed;

    // Update local db and server
    await actions.toggleTask(apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Create or Update a task on the server and local state.
  Future<void> updateTask(String apiToken, Task task) async {
    task = await actions.updateTask(apiToken, task);
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Delete a task from local database and the server.
  Future<void> deleteTask(String apiToken, Task task) async {
    await actions.deleteTask(apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }
}
