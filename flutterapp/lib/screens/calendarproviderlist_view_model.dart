import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/providers/session.dart';


class CalendarProviderListViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Calendar providers list
  List<CalendarProvider> _providers = [];

  CalendarProviderListViewModel(LocalDatabase database, this.session) {
    _database = database;
    _providers = [];

    _database.calendarList.addListener(() async {
      refresh();
    });
  }

  bool get loading => _loading;
  List<CalendarProvider> get providers => _providers;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    var result = await _database.calendarList.get();
    if (result == null) {
      return;
    }
    if (result.isNotEmpty) {
      _providers = result;
    }
    if (!_loading) {
      return refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchCalendarProviders(session!.apiToken);
    await _database.calendarList.set(result);
    _providers = result;
    _loading = false;

    notifyListeners();
  }
}
