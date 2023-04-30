import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';

class UpcomingRepo extends Repository<DailyTasksData> {
  static const String name = 'upcoming';

  UpcomingRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  Map<String, dynamic> serialize(DailyTasksData data) {
    Map<String, dynamic> result = {};
    data.forEach((key, dayView) {
      result[key] = dayView.toMap();
    });
    return result;
  }

  /// Refresh the data stored for the 'upcoming' view.
  @override
  Future<void> set(DailyTasksData data) async {
    return setMap(serialize(data));
  }

  /// Get all stored data. If no data is available and empty list
  /// will be returned.
  Future<DailyTasksData> get() async {
    var data = await getMap();
    if (data == null || data.isEmpty || data.runtimeType == List) {
      return {};
    }
    DailyTasksData result = {};
    // Stale cached data from earlier version.
    if (data.containsKey('tasks')) {
      return {};
    }

    data.forEach((key, viewData) {
      result[key] = TaskViewData.fromMap(viewData);
    });

    return result;
  }

  /// Add a task to the collection
  Future<void> append(Task task) async {
    var data = await get();

    var taskDate = task.dateKey;
    var dateView = data[taskDate] ?? TaskViewData(tasks: [task], calendarItems: []);
    data[taskDate] = dateView;

    await set(data);
    expire();

    notifyListeners();
  }

  // Update a task. Will either add/remove/update the
  // task based on its state. Will notify on changes.
  Future<void> updateTask(Task task, {expire = true}) async {
    var data = await get();

    var taskDate = task.dateKey;
    var dateView = data[taskDate] ?? TaskViewData(tasks: [task], calendarItems: []);

    var index = dateView.tasks.indexWhere((item) => item.id == task.id);
    if (task.hasDueDate) {
      if (index == -1) {
        dateView.tasks.add(task);
      } else {
        dateView.tasks.removeAt(index);
        dateView.tasks.insert(index, task);
      }
    } else if (index != -1){
      dateView.tasks.removeAt(index);
    }
    data[taskDate] = dateView;

    await set(data);
    if (expire) {
      this.expire();
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await get();

    var taskDate = task.dateKey;
    var dateView = data[taskDate] ?? TaskViewData(tasks: [task], calendarItems: []);

    var index = dateView.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      dateView.tasks.removeAt(index);
      data[taskDate] = dateView;

      await set(data);

      expire(notify: true);
    }
  }
}
