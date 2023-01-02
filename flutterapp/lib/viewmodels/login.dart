import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';

class LoginViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  String? _loginError;

  bool get hasToken => _database.apiToken.hasToken;

  String? get loginError => _loginError;

  LoginViewModel(LocalDatabase database) {
    _database = database;
  }

  Future<void> login(String? email, String? password) async {
    if (email == null || password == null) {
      _loginError = 'E-mail and password are required';
      return;
    }

    try {
      var apiToken = await actions.doLogin(email, password);
      await _database.apiToken.set(apiToken);
    } catch (e) {
      _loginError = 'Authentication failed.';
    }

    notifyListeners();
  }
}
