import 'package:flutter/foundation.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/database.dart';

class SessionProvider extends ChangeNotifier {
  ApiToken? _apiToken;
  late LocalDatabase _database;

  SessionProvider(LocalDatabase database) {
    _database = database;
    _loadApiToken();
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

  void _loadApiToken() async {
    try {
      _apiToken = await _database.fetchApiToken();
      notifyListeners();
    } catch (e) {
      _apiToken = null;
    }
  }

  Future<void> set(ApiToken token) async {
    await _database.createApiToken(token);
    _apiToken = token;
    notifyListeners();
  }

  void clear() {
    _apiToken = null;
    notifyListeners();
  }
}
