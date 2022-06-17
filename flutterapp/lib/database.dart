import 'dart:developer' as developer;
import 'package:json_cache/json_cache.dart';
import 'package:localstorage/localstorage.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';

class LocalDatabase {
  // Configuration
  static const String dbName = 'docket-localstorage';

  static const String apiTokenKey = 'v1:apitoken';
  static const String todayTasksKey = 'v1:todaytasks';

  JsonCache? _database;
  JsonCache database() {
    if (_database != null) {
      return _database!;
    }
    _database = _initDb();
    return _database!;
  }

  _initDb() {
    final LocalStorage storage = LocalStorage(dbName);
    return JsonCacheMem(JsonCacheLocalStorage(storage));
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
  Future<List<Task>> fetchTodayTasks() async {
    final db = database();
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

  /// Erase all rows in the 'today' view store.
  Future<void>clearTodayTasks() async {
    final db = database();
    await db.remove(todayTasksKey);
  }
}
