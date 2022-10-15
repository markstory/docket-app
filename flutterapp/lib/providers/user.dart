import 'package:flutter/foundation.dart';

import 'package:docket/models/user.dart';
import 'package:docket/database.dart';

class UserProvider extends ChangeNotifier {
  bool loading = false;
  User? _user;
  late LocalDatabase _database;

  UserProvider(LocalDatabase database, {User? user}) {
    _database = database;
    if (user != null) {
      set(user);
    } else {
      _loadUser();
    }
  }

  String get apiToken {
    if (_apiToken == null) {
      throw Exception('Cannot get token it is not set.');
    }
    return _apiToken!.token;
  }

  bool get hasToken {
    return _apiToken != null;
  }

  void _loadUser() async {
    loading = true;
    try {
      _apiToken = await _database.fetchUser();
      notifyListeners();
    } catch (e) {
      _apiToken = null;
    } finally {
      loading = false;
    }
  }

  /// Save an API token to the local database for future use.
  Future<void> saveToken(User token) async {
    await _database.createUser(token);
    _apiToken = token;
    notifyListeners();
  }

  /// Store a token in memory but not persist it.
  /// Mostly used in tests.
  void set(String token) {
    _apiToken = User.fromMap({'id': 1, 'token': token, 'lastUsed': DateTime.now()});
  }

  void clear() {
    _apiToken = null;
    notifyListeners();
  }
}
