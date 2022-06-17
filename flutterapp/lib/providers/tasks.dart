import 'dart:developer' as developer;
import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;

/// I'm trying to keep the update methods have a 1:1
/// mapping with an `actions.` function. I think this
/// will be useful in the future if I want to have
/// remote API buffering or retries.
class TasksProvider extends ChangeNotifier {
  List<Task> _todayTasks = [];

  late LocalDatabase _database;

  TasksProvider(LocalDatabase database) {
    _database = database;
  }

  void refreshTodayTasks(String apiToken) async {
    developer.log('Refreshing today tasks');
    await _database.clearTodayTasks();
    var tasks = await actions.loadTodayTasks(apiToken);
    await _database.insertTodayTasks(tasks);

    _todayTasks = tasks;
    notifyListeners();
  }

  Future<List<Task>> todayTasks(String apiToken) async {
    if (_todayTasks.isNotEmpty) {
      return _todayTasks;
    }
    try {
      developer.log('Fetch today tasks from db');
      _todayTasks = await _database.fetchTodayTasks();
      if (_todayTasks.isEmpty) {
        developer.log('Fetch today tasks from API');
        var tasks = await actions.loadTodayTasks(apiToken);

        await _database.insertTodayTasks(tasks);
        _todayTasks = tasks;
        developer.log('Stored tasks in local db');
      }
      notifyListeners();
    } catch (e, stacktrace) {
      developer.log('Could not fetch tasks at all ${e.toString()}, $stacktrace');
      _todayTasks = [];
    }
    return _todayTasks;
  }

  Future<void> toggleComplete(String apiToken, Task task) async {
    developer.log('Toggling complete on task');

    // Update local db, then refresh from server.
    await _database.updateTask(task);
    await actions.taskToggle(apiToken, task);
  }
}
