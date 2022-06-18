import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;
import 'package:flutter/material.dart';

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
    await _database.clearTodayTasks();
    notifyListeners();
  }

  Future<List<Task>> refreshTodayTasks(String apiToken) async {
    await _database.clearTodayTasks();
    var tasks = await actions.loadTodayTasks(apiToken);
    await _database.insertTodayTasks(tasks);

    notifyListeners();

    return tasks;
  }

  Future<List<Task>> todayTasks(String apiToken) async {
    List<Task> tasks = [];
    try {
      tasks = await _database.fetchTodayTasks();
      if (tasks.isEmpty) {
        tasks = await actions.loadTodayTasks(apiToken);

        await _database.insertTodayTasks(tasks);
      }
      notifyListeners();
    } catch (e, stacktrace) {
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
    await actions.taskToggle(apiToken, task);

    notifyListeners();
    // TODO flag local db as potentially stale
  }

  Future<void> deleteTask(String apiToken, Task task) async {
    await _database.deleteTask(task);
    await actions.deleteTask(apiToken, task);

    notifyListeners();
    // TODO flag local db as potentially stale
  }
}
