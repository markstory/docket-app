import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';

enum ViewNames {
  today,
  upcoming,
  project,
  trashbin,
}

/// I'm trying to keep the update methods have a 1:1
/// mapping with an `actions.` function. I think this
/// will be useful in the future if I want to have
/// remote API buffering or retries.
class TasksProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;
  Set<ViewNames> _pending = {};

  TasksProvider(LocalDatabase database, this.session) {
    _database = database;
    _pending = {};
  }

  Future<void> _withPending(ViewNames view, Future<void> Function() callback) {
    if (_pending.contains(view)) {
      return Future.value(null);
    }
    _pending.add(view);
    try {
      return callback();
    } finally {
      _pending.remove(view);
    }
  }

  void setSession(SessionProvider session) {
    this.session = session;
  }

  Future<void> clear() async {
    await _database.clearTasks();

    notifyListeners();
  }

  /// Create a task on the server and notify listeners.
  Future<Task> createTask(Task task) async {
    task = await actions.createTask(session!.apiToken, task);

    // Force expire related views so that we read our write.
    // Ideally long term addTasks() becomes clever enough to
    // insert items into the various view caches.
    await _database.addTasks([task], expire: true);

    notifyListeners();

    return task;
  }

  /// Flip task.completed and persist to the server.
  Future<void> toggleComplete(Task task) async {
    // Update the completed state
    task.completed = !task.completed;

    // Update local db and server
    await actions.toggleTask(session!.apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Create or Update a task on the server and local state.
  Future<Task> updateTask(Task task) async {
    task = await actions.updateTask(session!.apiToken, task);
    await _database.updateTask(task);

    notifyListeners();
    return task;
  }

  /// Delete a task from local database and the server.
  Future<void> deleteTask(Task task) async {
    await actions.deleteTask(session!.apiToken, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  Future<void> undelete(Task task) async {
    await actions.undeleteTask(session!.apiToken, task);
    await _database.undeleteTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  Future<void> move(Task task, Map<String, dynamic> updates) async {
    await actions.moveTask(session!.apiToken, task, updates);
  }


  // {{{ Subtask methods
  /// Create or Update a subtask and persist to the server.
  Future<void> saveSubtask(Task task, Subtask subtask) async {
    // Get the index before updating the server so that we can
    // get the index of new subtasks. We're assuming that there is only
    // one unsaved subtask at a time.
    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);

    developer.log('saving subtask $subtask index=$index', name: 'debug');
    if (subtask.id == null) {
      developer.log('create');
      subtask = await actions.createSubtask(session!.apiToken, task, subtask);
    } else {
      developer.log('update');
      subtask = await actions.updateSubtask(session!.apiToken, task, subtask);
      developer.log('updated subtask ${subtask.toMap()}', name: 'debug');
    }

    task.subtasks[index] = subtask;
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Flip subtask.completed and persist to the server.
  Future<void> toggleSubtask(Task task, Subtask subtask) async {
    subtask.completed = !subtask.completed;
    await actions.toggleSubtask(session!.apiToken, task, subtask);

    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);
    task.subtasks[index] = subtask;
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  Future<void> moveSubtask(Task task, Subtask subtask) async {
    await Future.wait([
      actions.moveSubtask(session!.apiToken, task, subtask),
      _database.updateTask(task),
    ]);

    notifyListeners();
  }

  Future<void> deleteSubtask(Task task, Subtask subtask) async {
    task.subtasks.remove(subtask);

    await Future.wait([
      actions.deleteSubtask(session!.apiToken, task, subtask),
      _database.updateTask(task),
    ]);

    notifyListeners();
  }
  // }}}
}
