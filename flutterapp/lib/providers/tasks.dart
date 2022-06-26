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

  Future<List<Task>> refreshTodayTasks(String apiToken) async {
    var tasks = await actions.loadTodayTasks(apiToken);
    await _database.setTodayTasks(tasks);

    notifyListeners();

    return tasks;
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

  Future<List<Task>> todayTasks(String apiToken) async {
    List<Task> tasks = [];
    try {
      tasks = await _database.fetchTodayTasks();
      if (tasks.isEmpty) {
        tasks = await actions.loadTodayTasks(apiToken);

        await _database.setTodayTasks(tasks);
      }
      notifyListeners();
    } catch (e) {
      //print('Could not fetch tasks at all ${e.toString()}, $stacktrace');
      tasks = [];
    }
    return tasks;
  }

  Future<List<Task>> upcomingTasks(String apiToken) async {
    List<Task> tasks = [];
    try {
      tasks = await _database.fetchUpcomingTasks();
      if (tasks.isEmpty) {
        tasks = await actions.loadUpcomingTasks(apiToken);

        await _database.setUpcomingTasks(tasks);
      }
      notifyListeners();
    } catch (e) {
      //print('Could not fetch tasks at all ${e.toString()}, $stacktrace');
      tasks = [];
    }
    return tasks;
  }

  Future<void> toggleComplete(String apiToken, Task task) async {
    // Update the completed state
    task.completed = !task.completed;

    // Update local db and server
    await _database.updateTask(task);
    await actions.toggleTask(apiToken, task);

    notifyListeners();
  }

  Future<void> deleteTask(String apiToken, Task task) async {
    await _database.deleteTask(task);
    await actions.deleteTask(apiToken, task);

    notifyListeners();
  }
}
