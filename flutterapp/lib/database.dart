import 'dart:developer' as developer;
import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';

class LocalDatabase {
  // Configuration
  static const String dbFileName = 'docket-local.sqlite';

  // Table Constants.
  static const String apiTokensTable = 'api_tokens';
  static const String todayTasksTable = 'today_tasks';

  Database? _database;

  Future<Database> database() async {
    if (_database != null) {
      return _database!;
    }
    _database = await _initDb(dbFileName);
    return _database!;
  }

  Future<Database> _initDb(String filePath) async {
    final dbPath = await getDatabasesPath();
    final path = join(dbPath, filePath);

    // For when schema gets broken.
    //await deleteDatabase(path);
    return await openDatabase(path, version: 1,
      onCreate: _createDb
    );
  }

  Future<void> _createDb(Database db, int version) async {
    developer.log('Creating Database');
    const idType = 'INTEGER PRIMARY KEY AUTOINCREMENT';
    const intType = 'INTEGER';
    const textType = 'TEXT';
    const requiredTextType = 'TEXT NOT NULL';

    await db.execute('''
CREATE TABLE $apiTokensTable (
  id $idType,
  token $requiredTextType,
  last_used DATETIME
)
  ''');
    await db.execute('''
CREATE TABLE $todayTasksTable (
  id $idType,
  project_slug $textType,
  project_name $textType,
  project_color $int,
  section_id $intType,
  title $textType,
  body $textType,
  due_on DATETIME,
  child_order $intType,
  day_order $intType,
  evening boolean,
  completed boolean
)
    ''');
  }

  Future<void> close() async {
    final db = await database();

    db.close();
  }

  // ApiToken methods.
  Future<ApiToken> createApiToken(ApiToken apiToken) async {
    final db = await database();

    // Fixate the id so we do upserts.
    apiToken = apiToken.copy(id: 1);
    await db.insert(apiTokensTable, apiToken.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);

    return apiToken;
  }

  Future<ApiToken?> fetchApiToken() async {
    final db = await database();
    var result = await db.query(apiTokensTable,
      limit: 1
    );
    if (result.isNotEmpty) {
      return ApiToken.fromMap(result.first);
    }
    return null;
  }

  // Task Loader Methods.
  /// Fetch all records in the 'today' view store.
  Future<List<Task>> fetchTodayTasks() async {
    final db = await database();
    var results = await db.query(todayTasksTable,
      orderBy: 'due_on ASC, evening ASC, day_order ASC, title ASC'
    );
    if (results.isNotEmpty) {
      List<Task> tasks = [];
      for (var item in results) {
        tasks.add(Task.fromMap(item));
      }
      return tasks;
    }
    return [];
  }

  /// Add records to the 'today' view store.
  Future<void> insertTodayTasks(List<Task> tasks) async {
    final db = await database();
    await db.transaction((txn) async {
      for (var task in tasks) {
        await txn.insert(
          todayTasksTable, 
          task.toMap(), 
          conflictAlgorithm: ConflictAlgorithm.replace
        );
      }
    });
  }

  /// Erase all rows in the 'today' view store.
  Future<void>clearTodayTasks() async {
    final db = await database();
    await db.delete(todayTasksTable);
  }
}
