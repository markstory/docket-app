import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;

class TasksProvider with ChangeNotifier {
  List<Task> _todayTasks = [];

  late LocalDatabase _database;

  TodayProvider(LocalDatabase database) {
    _database = database;
  }

  List<Task> todayTasks(String apiToken) async {
    try {
      _todayTasks = await _database.fetchTodayTasks();
      if (!_todayTasks) {
        // Load from API results, and store them locally.
        tasks = await actions.loadTodayTasks(apiToken);
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
