import 'package:docket/models/task.dart';

///
/// Abstract interface that TaskForm depends on.
/// Allows viewmodels from both new and edit screens which
/// need to manage state separately.
///
abstract class TaskFormViewModel {
  Task get task;
  bool get loading;

  Future<void> reorderSubtask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex);

  Future<void> saveSubtask(Task task, Subtask subtask);

  Future<void> toggleSubtask(Task task, Subtask subtask);

  Future<void> deleteSubtask(Task task, Subtask subtask);

  void addListener(void Function() listener);

  void removeListener(void Function() listener);
}
