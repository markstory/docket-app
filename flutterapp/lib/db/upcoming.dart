import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';

class UpcomingRepo extends Repository<TaskViewData> {
  static const String name = 'upcoming';

  UpcomingRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'upcoming' view.
  @override
  Future<void> set(TaskViewData data) async {
    return setMap(data.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await getMap();
    // Empty local data
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }

  /// Add a task to the collection
  Future<void> append(Task task) async {
    var data = await get();
    data.tasks.add(task);
    await set(data);
    expire();

    notifyListeners();
  }

  // Update a task. Will either add/remove/update the
  // task based on its state. Will notify on changes.
  Future<void> updateTask(Task task, {expire = true}) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (task.hasDueDate) {
      if (index == -1) {
        data.tasks.add(task);
      } else {
        data.tasks.removeAt(index);
        data.tasks.insert(index, task);
      }
    } else if (index != -1){
      data.tasks.removeAt(index);
    }
    await set(data);
    if (expire) {
      this.expire();
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      data.tasks.removeAt(index);
      await set(data);

      expire(notify: true);
    }
  }
}
