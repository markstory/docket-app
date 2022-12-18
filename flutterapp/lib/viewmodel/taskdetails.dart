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

  TaskDetailsViewModel(LocalDatabase database, this.session) {
    _database = database;

    _database.taskDetails.addListener(() async {
      fetchTask();
    });
  }

  bool get loading => _loading;

  int get id {
    var value = _id;
    assert(value != null, "Cannot read id it has not been set.");

    return value!;
  }
  Task get task {
    var value = _task;
    assert(value != null, "Cannot read task it has not been set.");

    return value!;
  }

  setSession(SessionProvider value) {
    session = value;
  }

  setId(int value) {
    if (value < 0) {
      throw Exception('task id should not be below 0');
    }
    _id = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchTask();

    if (!_loading && (_task == null || !_database.taskDetails.isFresh())) {
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

    var result = await actions.fetchTaskById(session!.apiToken, id);
    await _database.addTasks([result]);
    _task = result;
    _loading = false;

    notifyListeners();
  }

  /// Update a task.
  Future<void> update(Task task) async {
    assert(task.id != 0,
      'Cannot update new task. Use create() instead.');
    task = await actions.updateTask(session!.apiToken, task);
    await _database.updateTask(task);
    _task = task;
    _id = task.id;

    notifyListeners();
  }

  /// Reorder a subtask based on the protocol defined by
  /// the drag_and_drop_lists package.
  Future<void> reorderSubtask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    assert(oldListIndex == newListIndex,
      "Cannot move subtasks between lists $oldListIndex != $newListIndex, as there is only a single subtask collection on a task.");
    var item = task.subtasks[oldItemIndex];
    item.ranking = newItemIndex;

    task.subtasks.removeAt(oldItemIndex);
    task.subtasks.insert(newItemIndex, item);
    await actions.moveSubtask(session!.apiToken, task, item);
    await _database.updateTask(task);

    notifyListeners();
  }

  /// Create a task on the server and notify listeners.
  Future<Task> create(Task task) async {
    task = await actions.createTask(session!.apiToken, task);
    await _database.addTasks([task], create: true);

    notifyListeners();

    return task;
  }
}
