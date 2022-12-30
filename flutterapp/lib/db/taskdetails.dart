import 'package:clock/clock.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';

/// A map based view data provider
class TaskDetailsRepo extends Repository<Task> {
  static const String name = 'taskdetails';

  TaskDetailsRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a task into the details view.
  ///
  /// We store a per task expiration time
  /// in addition to the task data.
  @override
  Future<void> set(Task task) async {
     var current = await getMap() ?? {};
     var taskId = task.id.toString();
     current[taskId] = task.toMap();
     current[taskId]['updatedAt'] = clock.now().toIso8601String();

     await setMap(current);

     notifyListeners();
  }

  Future<Task?> get(int id) async {
    var taskId = id.toString();
    var data = await getMap();
    // Likely loading.
    if (data == null || data[taskId] == null) {
      return null;
    }
    return Task.fromMap(data[taskId]);
  }

  /// Remove a task and notifyListeners.
  Future<void> remove(int id) async {
    var data = await getMap() ?? {};
    var taskId = id.toString();

    data.remove(taskId);
    await setMap(data);

    notifyListeners();
  }

  bool isTaskFresh(int id) {
    var state = this.state;
    if (state == null) {
      return false;
    }
    if (duration == null) {
      return true;
    }
    var taskId = id.toString();
    var taskData = state['data'][taskId];
    if (taskData == null) {
      return false;
    }
    var updated = taskData["updatedAt"];
    // No updatedAt means we need to refresh.
    if (updated == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
  }

  void notify() {
    notifyListeners();
  }
}
