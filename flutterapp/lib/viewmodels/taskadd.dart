import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/viewmodels/taskform.dart';
import 'package:flutter/material.dart';

class TaskAddViewModel extends ChangeNotifier implements TaskFormViewModel {
  late LocalDatabase _database;

  bool _loading = false;

  late Task _task;

  TaskAddViewModel(LocalDatabase database) {
    _database = database;
    reset();
  }

  @override
  Task get task => _task;

  @override
  bool get loading => _loading;

  Future<void> save() async {
    var updated = await actions.createTask(_database.apiToken.token, task);
    _loading = true;

    await _database.createTask(updated);
    reset();
    _loading = false;

    notifyListeners();
  }

  void reset() {
    _task = Task.pending();
  }

  /// Reorder a subtask based on the protocol defined by
  /// the drag_and_drop_lists package.
  @override
  Future<void> reorderSubtask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    assert(oldListIndex == newListIndex,
      "Cannot move subtasks between lists $oldListIndex != $newListIndex, as there is only a single subtask collection on a task.");
    var item = _task.subtasks[oldItemIndex];
    item.ranking = newItemIndex;

    _task.subtasks.removeAt(oldItemIndex);
    _task.subtasks.insert(newItemIndex, item);

    notifyListeners();
  }

  /// Create or Update a subtask and persist to the server.
  @override
  Future<void> saveSubtask(Task task, Subtask subtask) async {
    // For new tasks subtasks must have unique text.
    var index = task.subtasks.indexWhere((item) => item.title == subtask.title);
    if (index >= 0) {
      task.subtasks[index] = subtask;
    } else {
      task.subtasks.add(subtask);
    }

    _task = task;

    notifyListeners();
  }

  /// Flip subtask.completed and persist to the server.
  @override
  Future<void> toggleSubtask(Task task, Subtask subtask) async {
    subtask.completed = !subtask.completed;

    var index = _task.subtasks.indexWhere((item) => item.id == subtask.id);
    _task.subtasks[index] = subtask;

    notifyListeners();
  }

  @override
  Future<void> deleteSubtask(Task task, Subtask subtask) async {
    _task.subtasks.removeWhere((item) => item.title == subtask.title);

    notifyListeners();
  }
}
