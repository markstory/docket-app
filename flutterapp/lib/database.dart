import 'dart:developer' as developer;
import 'package:json_cache/json_cache.dart';
import 'package:localstorage/localstorage.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';

class LocalDatabase {
  // Configuration
  static const String dbName = 'docket-localstorage';

  // Storage keys.
  static const String apiTokenKey = 'v1:apitoken';
  static const String todayTasksKey = 'v1:todaytasks';
  static const String upcomingTasksKey = 'v1:upcomingtasks';
  static const String taskMapKey = 'v1:taskmap';

  /// Key used to lazily expire data.
  /// Contains a structure of `{key: timestamp}`
  /// Where key is one of the keys above, and timestamp
  /// is the expiration time for the key.
  static const String expiredKey = 'v1:expired';

  JsonCache? _database;

  /// Lazily create the database.
  JsonCache database() {
    if (_database != null) {
      return _database!;
    }
    final LocalStorage storage = LocalStorage(dbName);
    _database = JsonCacheMem(JsonCacheLocalStorage(storage));
    return _database!;
  }

  /// See if the storage key is old, or force expired.
  /// We don't want to eagerly refresh data from the server
  /// so we flag data as expired and then refresh next time
  /// data is used.
  Future<bool> _isDataStale(String key, bool useStale) async {
    // TODO implement date checks so that local cache expires automatically
    // every few hours.
    final db = database();
    var staleData = await db.value(expiredKey);
    if (staleData == null || staleData[key] == null || useStale) {
      return false;
    }
    var time = DateTime.now().millisecondsSinceEpoch;
    if (staleData[key] < time) {
      return true;
    }
    return false;
  }

  /// Expire tasks individually
  /// When a task is updated or created we need to
  /// clear the local cache so that the new item is visible.
  ///
  /// In a SQL based storage you'd be able to remove/update the row
  /// individually. Because our local database is view-based. We need
  /// custom logic to remove cached data for the impacted views.
  /// This ensures that we don't provide stale state to the Provider
  /// layer and instead Providers fetch fresh data from the Server.
  void _expireTask(Task task) async {
    var now = DateTime.now();
    List<String> expire = [];

    // If the task has a due date expire upcoming and possibly
    // today views.
    if (task.dueOn != null) {
      var delta = task.dueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        expire.add(todayTasksKey);
      }
      expire.add(upcomingTasksKey);
    }

    // TODO implement project view expiration.

    final db = database();
    var current = await db.value(expiredKey);
    current ??= {};

    for (var key in expire) {
      current[key] = now.millisecondsSinceEpoch;
    }
    await db.refresh(expiredKey, current);
  }

  /// Directly set a key. Avoid use outside of tests.
  Future<void> set(String key, Map<String, Object?> value) async {
    final db = database();
    await db.refresh(key, value);
  }

  // ApiToken methods.
  Future<ApiToken> createApiToken(ApiToken apiToken) async {
    final db = database();
    await db.refresh(apiTokenKey, apiToken.toMap());

    return apiToken;
  }

  Future<ApiToken?> fetchApiToken() async {
    final db = database();
    var result = await db.value(apiTokenKey);
    if (result != null) {
      return ApiToken.fromMap(result);
    }
    return null;
  }

  /// Add records to the 'today' view store.
  Future<void> setTodayTasks(List<Task> tasks) async {
    await addTasks(tasks);

    final db = database();
    await db.refresh(todayTasksKey, {
      'tasks': tasks.map((task) => task.id).toList(),
    });
  }

  /// Add records to the 'today' view store.
  Future<void> setUpcomingTasks(List<Task> tasks) async {
    await addTasks(tasks);

    final db = database();
    await db.refresh(upcomingTasksKey, {
      'tasks': tasks.map((task) => task.id).toList(),
    });
  }

  /// Store a list of Tasks.
  /// Provides more direct access to the database.
  Future<void> addTasks(List<Task> tasks) async {
    final db = database();
    var indexed = await db.value(taskMapKey);
    indexed ??= {};
    for (var task in tasks) {
      var id = task.id;
      if (id == null) {
        continue;
      }
      indexed[id.toString()] = task.toMap();
    }
    await db.refresh(taskMapKey, indexed);
  }

  // Task Loader Methods.
  /// Fetch all records in the 'today' view store.
  Future<List<Task>> fetchTodayTasks({useStale = false}) async {
    final db = database();
    var isStale = await _isDataStale(todayTasksKey, useStale);
    if (isStale) {
      return [];
    }
    var results = await db.value(todayTasksKey);
    if (results == null || results['tasks'] == null) {
      return [];
    }
    List<int> taskIds = results['tasks'].cast<int>();

    return getTasksById(taskIds);
  }

  /// Fetch all records in the 'upcoming' view store.
  Future<List<Task>> fetchUpcomingTasks({useStale = false}) async {
    final db = database();
    var isStale = await _isDataStale(upcomingTasksKey, useStale);
    if (isStale) {
      return [];
    }
    var results = await db.value(upcomingTasksKey);
    if (results == null || results['tasks'] == null) {
      return [];
    }
    List<int> taskIds = results['tasks'].cast<int>();

    return getTasksById(taskIds);
  }

  Future<List<Task>> getTasksById(List<int> taskIds) async {
    final db = database();
    var indexed = await db.value(taskMapKey);
    indexed ??= {};
    List<Task> tasks = [];
    for (var id in taskIds) {
      var record = indexed[id.toString()];
      if (record == null) {
        developer.log('Skipping task with id=$id as it could not be found.');
        continue;
      }
      tasks.add(Task.fromMap(record));
    }
    return tasks;
  }

  Future<Task?> fetchTaskById(int id) async {
    var tasks = await getTasksById([id]);
    if (tasks.isNotEmpty) {
      return tasks[0];
    }
    throw Exception('Could not load task');
  }

  /// Replace a task in the local database.
  /// This will update all task views with the new data.
  Future<void> updateTask(Task task) async {
    addTasks([task]);

    _expireTask(task);
  }

  Future<void> deleteTask(Task task) async {
    final db = database();
    if (task.id == null) {
      return;
    }
    // Remove the task from the task mapping.
    var indexed = await db.value(taskMapKey);
    indexed ??= {};
    indexed.remove(task.id.toString());
    await db.refresh(taskMapKey, indexed);

    _expireTask(task);
  }

  // Data Erasing Methods
  Future<void>clearExpired() async {
    final db = database();
    return db.remove(expiredKey);
  }

  Future<List<void>>clearTasks() async {
    final db = database();
    return Future.wait([
      db.remove(taskMapKey),
      db.remove(todayTasksKey),
      db.remove(upcomingTasksKey),
    ]);
  }
}
