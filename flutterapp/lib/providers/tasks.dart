import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;

class TasksProvider with ChangeNotifier {
  List<Task> _todayTasks = [];

  late LocalDatabase _database;

  TasksProvider(LocalDatabase database) {
    _database = database;
  }

  void refreshTodayTasks(String apiToken) async {
    await _database.clearTodayTasks();
    var tasks = await actions.loadTodayTasks(apiToken);
    await _database.insertTodayTasks(tasks);

    _todayTasks = tasks;
    notifyListeners();
  }

  Future<List<Task>> todayTasks(String apiToken) async {
    try {
      _todayTasks = await _database.fetchTodayTasks();
      if (_todayTasks.isEmpty) {
        // Load from API results, and store them locally.
        var tasks = await actions.loadTodayTasks(apiToken);
        await _database.insertTodayTasks(tasks);
        _todayTasks = tasks;
      }
      notifyListeners();
    } catch (e) {
      _todayTasks = [];
    }
    return _todayTasks;
  }
}
