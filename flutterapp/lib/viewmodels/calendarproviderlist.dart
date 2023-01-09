import 'dart:convert';

import 'package:google_sign_in/google_sign_in.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/calendarprovider.dart';


class CalendarProviderListViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Calendar providers list
  List<CalendarProvider> _providers = [];

  CalendarProviderListViewModel(LocalDatabase database) {
    _database = database;
    _database.calendarList.addListener(listener);
    _providers = [];
  }

  @override
  void dispose() {
    _database.calendarList.removeListener(listener);
    super.dispose();
  }

  void listener() {
    refresh();
  }

  bool get loading => _loading;
  List<CalendarProvider> get providers => _providers;

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchData();

    if (!_loading && (_providers.isEmpty || !_database.calendarList.isFresh())) {
      return refresh();
    }
  }

  Future<void> fetchData() async {
    _loading = true;
    var result = await _database.calendarList.get();
    if (result != null && result.isNotEmpty) {
      _providers = result;
    }
    _loading = false;

    notifyListeners();
  }

  /// Refresh from the server and notify.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchCalendarProviders(_database.apiToken.token);
    await _database.calendarList.set(result);
    _providers = result;
    _loading = false;

    notifyListeners();
  }

  /// Delete the provider from the server and notify.
  Future<void> delete(CalendarProvider provider) async {
    await actions.deleteCalendarProvider(_database.apiToken.token, provider);
    await _database.calendarList.remove(provider.id);
    await _database.calendarDetails.remove(provider.id);

    notifyListeners();
  }

  /// Expire the list view so that on the next rebuild
  void expire() {
    _database.calendarList.expire();
  }

  Future<Map<String, String>> _getConfig() async {
    Map<String, String> config = {};
    // TODO need to get prod google-auth.json
    var file = await rootBundle.loadString('assets/google-services.json');
    var decoded = jsonDecode(file);
    config['clientId'] = decoded['client_id'];
    config['serverClientId'] = decoded['server_client_id'];

    return config;
  }

  Future<void> addGoogleAccount() async {
    var clientConfig = await _getConfig();
    var googleService = GoogleSignIn(
      clientId: clientConfig['clientId'],
      serverClientId: clientConfig['serverClientId'],
      scopes: [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/calendar.events.readonly',
        'https://www.googleapis.com/auth/calendar.readonly',
      ],
    );
    var account = await googleService.signIn();
    print(account);
  }
}
