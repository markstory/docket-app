import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';

enum ViewNames {
  project,
  trashbin,
}

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

    notifyListeners();
  }

  /// Update task completed state.
  Future<void> toggleComplete(Task task) async {
    // Update the completed state
    task.completed = !task.completed;

    // Update local db and server
    await actions.toggleTask(_database.apiToken.token, task);
    if (task.completed) {
      await _database.deleteTask(task);
    } else {
      await _database.updateTask(task);
      _database.completedTasks.expire(notify: true);
    }
    notifyListeners();
  }

  /// Create or Update a task on the server and local state.
  Future<Task> updateTask(Task task) async {
    var updated = await actions.updateTask(_database.apiToken.token, task);

    updated.previousDueOn = task.previousDueOn;
    updated.previousProjectSlug = task.projectSlug;

    await _database.updateTask(updated);

    notifyListeners();
    return updated;
  }

  /// Delete a task from local database and the server.
  Future<void> deleteTask(Task task) async {
    await actions.deleteTask(_database.apiToken.token, task);
    await _database.deleteTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  Future<void> undelete(Task task) async {
    await actions.undeleteTask(_database.apiToken.token, task);
    await _database.undeleteTask(task);

    notifyListeners();
  }

  // {{{ Subtask methods
  /// Create or Update a subtask and persist to the server.
  /// @deprecated
  Future<void> saveSubtask(Task task, Subtask subtask) async {
    // Get the index before updating the server so that we can
    // get the index of new subtasks. We're assuming that there is only
    // one unsaved subtask at a time.
    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);

    if (subtask.id == null) {
      subtask = await actions.createSubtask(_database.apiToken.token, task, subtask);
    } else {
      subtask = await actions.updateSubtask(_database.apiToken.token, task, subtask);
    }

    task.subtasks[index] = subtask;
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Flip subtask.completed and persist to the server.
  /// @deprecated
  Future<void> toggleSubtask(Task task, Subtask subtask) async {
    subtask.completed = !subtask.completed;
    await actions.toggleSubtask(_database.apiToken.token, task, subtask);

    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);
    task.subtasks[index] = subtask;
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Send an API request to move a task
  /// Does not update the local database.
  /// Assumption is that the calling view will refresh from server.
  /// @deprecated
  Future<void> moveSubtask(Task task, Subtask subtask) async {
    await Future.wait([
      actions.moveSubtask(_database.apiToken.token, task, subtask),
      _database.updateTask(task),
    ]);

    notifyListeners();
  }

  /// @deprecated
  Future<void> deleteSubtask(Task task, Subtask subtask) async {
    task.subtasks.remove(subtask);

    await Future.wait([
      actions.deleteSubtask(_database.apiToken.token, task, subtask),
      _database.updateTask(task),
    ]);

    notifyListeners();
  }
  // }}}
}
