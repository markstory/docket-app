import 'package:flutter/foundation.dart';

class SessionModel extends ChangeNotifier {
  String? apiToken;

  void set(String token) {
    apiToken = token;
    notifyListeners();
  }

  void clear() {
    apiToken = null;
    notifyListeners();
  }
}
