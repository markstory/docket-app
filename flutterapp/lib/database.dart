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

  /// Expire local data
  void _expireTask(Task task) async {
    var now = DateTime.now();
    List<String> expire = [];

    if (task.dueOn != null) {
      var delta = task.dueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        expire.add(todayTasksKey);
      }
    }
    if (task.dueOn != null) {
      // TODO expire upcoming view.
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

  // Task Loader Methods.
  /// Fetch all records in the 'today' view store.
  Future<List<Task>> fetchTodayTasks({useStale = false}) async {
    final db = database();
    var isStale = await _isDataStale(todayTasksKey, useStale);
    if (isStale) {
      return [];
    }
    var results = await db.value(todayTasksKey);
    if (results == null) {
      return [];
    }
    if (results['tasks'] != null) {
      List<Task> tasks = [];
      for (var item in results['tasks']) {
        tasks.add(Task.fromMap(item));
      }
      return tasks;
    }
    return [];
  }

  /// Add records to the 'today' view store.
  Future<void> insertTodayTasks(List<Task> tasks) async {
    final db = database();
    await db.refresh(todayTasksKey, {
      'tasks': tasks.map((task) => task.toMap()).toList(),
    });
  }

  Future<void> updateTask(Task task) async {
    final db = database();
    var results = await db.value(todayTasksKey);
    if (results == null || results['tasks'] == null) {
      return;
    }
    var index = 0;
    for (var item in results['tasks']) {
      if (item['id'] == task.id) {
        break;
      }
      index++;
    }
    results['tasks'][index] = task.toMap();
    await db.refresh(todayTasksKey, results);

    _expireTask(task);
  }

  Future<void> deleteTask(Task task) async {
    final db = database();

    var results = await db.value(todayTasksKey);
    if (results == null || results['tasks'] == null) {
      return;
    }
    results['tasks'].removeWhere(
      (item) => item['id'] == task.id
    );
    await db.refresh(todayTasksKey, results);

    _expireTask(task);
  }

  /// Erase all rows in the 'today' view store.
  Future<void>clearTodayTasks() async {
    final db = database();
    await db.remove(todayTasksKey);
  }
}
