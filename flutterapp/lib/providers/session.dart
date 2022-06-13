import 'package:flutter/foundation.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/database.dart';

class SessionProvider with ChangeNotifier {
  ApiToken? _apiToken;
  late LocalDatabase _database;

  SessionProvider(LocalDatabase database) {
    _database = database;
    _loadApiToken();
  }

  ApiToken? get apiToken {
    return _apiToken;
  }

  void _loadApiToken() async {
    try {
      _apiToken = await _database.fetchApiToken();
      notifyListeners();
    } catch (e) {
      _apiToken = null;
    }
  }

  void set(ApiToken token) async {
    await _database.createApiToken(token);
    _apiToken = token;
    notifyListeners();
  }

  void clear() {
    _apiToken = null;
    notifyListeners();
  }
}
