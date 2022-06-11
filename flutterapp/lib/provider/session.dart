import 'package:flutter/foundation.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/database.dart';

class SessionModel with ChangeNotifier {
  ApiToken? apiToken;
  late LocalDatabase _database;

  SessionModel(LocalDatabase database) {
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
    apiToken = token;
    notifyListeners();
  }

  void clear() {
    apiToken = null;
    notifyListeners();
  }
}
