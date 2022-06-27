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

    return task;
  }

  /// Fetch tasks for today view from the server.
  Future<List<Task>> fetchToday(String apiToken) async {
    var tasks = await actions.loadTodayTasks(apiToken);

    await _database.setTodayTasks(tasks);
    notifyListeners();

    return tasks;
  }

  /// Get the local database state for today view.
  Future<List<Task>> getToday() async {
    return _database.fetchTodayTasks();
  }

  // Fetch tasks for upcoming view from the server.
  Future<List<Task>> fetchUpcoming(String apiToken) async {
    var tasks = await actions.loadUpcomingTasks(apiToken);

    await _database.setUpcomingTasks(tasks);
    notifyListeners();

    return tasks;
  }

  // Get the locally cached upcoming tasks.
  Future<List<Task>> getUpcoming() async {
    return await _database.fetchUpcomingTasks();
  }

  Future<List<Task>> fetchProjectTasks(String apiToken, String projectSlug) async {
    var projectDetails = await actions.fetchProjectBySlug(apiToken, projectSlug);
    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);
    notifyListeners();

    return projectDetails.tasks;
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
