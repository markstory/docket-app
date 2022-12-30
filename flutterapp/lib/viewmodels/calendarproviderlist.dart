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

  setSession(SessionProvider value) {
    session = value;
  }

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

    var result = await actions.fetchCalendarProviders(session!.apiToken);
    await _database.calendarList.set(result);
    _providers = result;
    _loading = false;

    notifyListeners();
  }

  /// Delete the provider from the server and notify.
  Future<void> delete(CalendarProvider provider) async {
    await actions.deleteCalendarProvider(session!.apiToken, provider);
    await _database.calendarList.remove(provider.id);
    await _database.calendarDetails.remove(provider.id);

    notifyListeners();
  }
}