import 'dart:developer' as developer;
import 'package:flutter/foundation.dart';

import 'package:docket/models/task.dart';
import 'package:docket/database.dart';
import 'package:docket/actions.dart' as actions;

class TasksProvider extends ChangeNotifier {
  List<Task> _todayTasks = [];

  late LocalDatabase _database;

  TasksProvider(LocalDatabase database) {
    _database = database;
  }

  void refreshTodayTasks(String apiToken) async {
    developer.log('Refreshing today tasks');
    print('refreshing today tasks');
    await _database.clearTodayTasks();
    var tasks = await actions.loadTodayTasks(apiToken);
    await _database.insertTodayTasks(tasks);

    _todayTasks = tasks;
    notifyListeners();
  }

  Future<List<Task>> todayTasks(String apiToken) async {
    try {
      developer.log('Fetch today tasks from db');
      print('Fetch today tasks from db');
      _todayTasks = await _database.fetchTodayTasks();
      if (_todayTasks.isEmpty) {
        developer.log('Fetch today tasks from API');
        print('Fetch today tasks from API');
        var tasks = await actions.loadTodayTasks(apiToken);

        developer.log('Store tasks in local db');
        print('Store tasks in local db.');
        await _database.insertTodayTasks(tasks);
        _todayTasks = tasks;
      }
      notifyListeners();
    } catch (e, stacktrace) {
      print('Could not fetch tasks at all ${e.toString()}, $stacktrace');
      _todayTasks = [];
    }
    return _todayTasks;
  }
}
