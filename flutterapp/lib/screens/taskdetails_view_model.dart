import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';


class TaskDetailsViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;
  int? _id;
  Task? _task;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Whether or not data should be reloaded
  bool _shouldReload = false;

  TaskDetailsViewModel(LocalDatabase database, this.session) {
    _database = database;

    _database.taskDetails.addListener(() async {
      _shouldReload = true;
      loadData();
    });
  }

  bool get loading => _loading;

  int get id {
    var value = _id;
    if (value == null) {
      throw Exception("Cannot read id it has not been set.");
    }
    return value;
  }
  Task get task {
    var value = _task;
    if (value == null) {
      throw Exception("Cannot read task as it has not been set");
    }
    return value;
  }

  setSession(SessionProvider value) {
    session = value;
  }

  setId(int id) {
    _id = id;
    _shouldReload = true;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    if (_shouldReload || !_loading) {
      return refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;
    _shouldReload = false;

    var result = await actions.fetchTaskById(session!.apiToken, id);
    await _database.addTasks([result], expire: false);
    _task = result;
    _loading = false;

    notifyListeners();
  }

  /// Update a task.
  Future<void> update(Task task) async {
    task = await actions.updateTask(session!.apiToken, task);
    await _database.updateTask(task);
    _task = task;

    notifyListeners();
  }
}