import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:flutter/material.dart';

class TaskAddViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  bool _loading = false;

  late Task _task;

  TaskAddViewModel(LocalDatabase database) {
    _database = database;
    _task = Task.pending();
  }

  Task get task => _task;

  Future<void> save() async {
    var updated = await actions.createTask(_database.apiToken.token, task);
    _loading = true;

    await _database.createTask(updated);
    _task = Task.pending();
    _loading = false;

    notifyListeners();
  }
}
