import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';

class TodayRepo extends Repository<TaskViewData> {
  static const String name = 'today';

  TodayRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'today' view.
  @override
  Future<void> set(TaskViewData todayData) async {
    return setMap(todayData.toMap());
  }

  /// Add a task to the end of the today view. Will notify
  Future<void> append(Task task) async {
    var data = await get();
    data.tasks.add(task);
    await set(data);
    expire();

    notifyListeners();
  }

  /// Update a task. Will either add/update or remove 
  /// a task from the Today view depending on the task details.
  /// Will notify on changes.
  Future<void> updateTask(Task task, {bool expire = true}) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (task.isToday) {
      // Add or replace depending
      if (index == -1) {
        data.tasks.add(task);
      } else {
        data.tasks.removeAt(index);
        data.tasks.insert(index, task);
      }
    } else if (index != -1) {
      // Task is no longer in today view remove it.
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

  Future<TaskViewData> get() async {
    var data = await getMap();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }
}
