import 'package:docket/viewmodels/taskform.dart';
import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';


class TaskDetailsViewModel extends ChangeNotifier implements TaskFormViewModel {
  late LocalDatabase _database;
  int? _id;
  Task? _task;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  TaskDetailsViewModel(LocalDatabase database) {
    _database = database;
    _database.taskDetails.addListener(listener);
  }

  @override
  void dispose() {
    _database.taskDetails.removeListener(listener);
    super.dispose();
  }

  void listener() {
    fetchTask();
  }

  @override
  bool get loading => _loading;

  int get id {
    var value = _id;
    assert(value != null, "Cannot read id it has not been set.");

    return value!;
  }

  @override
  Task get task {
    var value = _task;
    assert(value != null, "Cannot read task it has not been set.");

    return value!;
  }

  setId(int value) {
    if (value < 0 && value != Task.idPending) {
      throw Exception('task id should not be below 0');
    }
    _id = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchTask();

    if (!_loading && (_task == null || !_database.taskDetails.isTaskFresh(id))) {
      return refresh();
    }
  }

  /// Load data from the local database.
  /// Avoids flash of empty content, makes the app feel more snappy
  /// and provides a better offline experience.
  Future<void> fetchTask() async {
    _loading = true;
    var task = await _database.taskDetails.get(id);
    _task = task;
    _loading = false;

    notifyListeners();
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchTaskById(_database.apiToken.token, id);
    await _database.updateTask(result);
    _task = result;
    _loading = false;

    notifyListeners();
  }

  /// Update a task.
  Future<void> update(Task task) async {
    assert(task.id != 0,
      'Cannot update new task. Use create() instead.');
    var updated = await actions.updateTask(_database.apiToken.token, task);
    updated.previousDueOn = task.dueOn;
    updated.previousProjectSlug = task.projectSlug;

    await _database.updateTask(task);
    _task = task;
    _id = task.id;

    notifyListeners();
  }

  /// Create a task on the server and notify listeners.
  Future<Task> create(Task task) async {
    task = await actions.createTask(_database.apiToken.token, task);
    _id = task.id;
    _task = task;

    await _database.createTask(task);
    notifyListeners();

    return task;
  }

  // {{{ Subtask methods

  /// Reorder a subtask based on the protocol defined by
  /// the drag_and_drop_lists package.
  @override
  Future<void> reorderSubtask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    assert(oldListIndex == newListIndex,
      "Cannot move subtasks between lists $oldListIndex != $newListIndex, as there is only a single subtask collection on a task.");
    var item = task.subtasks[oldItemIndex];
    item.ranking = newItemIndex;

    task.subtasks.removeAt(oldItemIndex);
    task.subtasks.insert(newItemIndex, item);
    if (task.hasId) {
      await actions.moveSubtask(_database.apiToken.token, task, item);
    }
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Create or Update a subtask and persist to the server.
  @override
  Future<void> saveSubtask(Task task, Subtask subtask) async {
    // Get the index before updating the server so that we can
    // get the index of new subtasks. We're assuming that there is only
    // one unsaved subtask at a time.
    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);
    task.subtasks[index] = subtask;

    if (task.hasId) {
      if (subtask.id == null) {
        subtask = await actions.createSubtask(_database.apiToken.token, task, subtask);
      } else {
        subtask = await actions.updateSubtask(_database.apiToken.token, task, subtask);
      }
    }

    await _database.updateTask(task);

    _task = task;

    notifyListeners();
  }

  /// Flip subtask.completed and persist to the server.
  @override
  Future<void> toggleSubtask(Task task, Subtask subtask) async {
    subtask.completed = !subtask.completed;

    if (task.hasId) {
      await actions.toggleSubtask(_database.apiToken.token, task, subtask);
    }

    var index = task.subtasks.indexWhere((item) => item.id == subtask.id);
    task.subtasks[index] = subtask;
    await _database.updateTask(task);

    notifyListeners();
  }

  @override
  Future<void> deleteSubtask(Task task, Subtask subtask) async {
    task.subtasks.remove(subtask);

    List<Future> futures = [];
    if (task.hasId) {
      futures.add(actions.deleteSubtask(_database.apiToken.token, task, subtask));
    }
    futures.add(_database.updateTask(task));
    await Future.wait(futures);

    notifyListeners();
  }
  // }}}
}
