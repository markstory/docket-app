import 'package:flutter/foundation.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/database.dart';

class SessionProvider with ChangeNotifier {
  ApiToken? apiToken;
  late LocalDatabase _database;

  SessionProvider(LocalDatabase database) {
    _database = database;
    _loadApiToken();
  }

  void _loadApiToken() async {
    try {
      apiToken = await _database.fetchApiToken();
      notifyListeners();
    } catch (e) {
      apiToken = null;
    }
  }

  void set(ApiToken token) async {
    await _database.createApiToken(token);
    apiToken = token;
    notifyListeners();
  }

  void clear() {
    apiToken = null;
    notifyListeners();
  }
}
