import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';

import 'package:docket/models/apitoken.dart';

class LocalDatabase {
  // Configuration
  static const String dbFileName = 'docket-local.sqlite';

  // Table Constants.
  static const String apiTokensTable = 'api_tokens';

  late Database? _database;

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
    return await openDatabase(path, version: 1, onCreate: _createDb);
  }

  Future<void> _createDb(Database db, int version) async {
    const idType = 'INTEGER PRIMARY KEY AUTOINCREMENT';
    const requiredTextType = 'TEXT NOT NULL';

    await db.execute('''
CREATE TABLE $apiTokensTable (
  id $idType,
  token $requiredTextType,
  last_used DATETIME
);
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
}
