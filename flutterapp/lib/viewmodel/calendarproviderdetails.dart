import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/models/calendarsource.dart';
import 'package:docket/providers/session.dart';


class CalendarProviderDetailsViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  int? _id;
  CalendarProvider? _provider;

  CalendarProviderDetailsViewModel(LocalDatabase database, this.session) {
    _database = database;

    _database.calendarList.addListener(() async {
      refresh();
    });
  }

  bool get loading => _loading;

  CalendarProvider get provider {
    var value = _provider;
    if (value == null) {
      throw Exception("Cannot access provider it is not set.");
    }

    return value;
  }

  int get id {
    var value = _id;
    if (value == null) {
      throw Exception("Cannot access id it is not set");
    }

    return value;
  }

  setSession(SessionProvider value) {
    session = value;
  }

  setId(int value) {
    _id = value;
    fetchProvider();
  }

  /// Load data from the local database.
  /// Avoids flash of empty content, makes the app feel more snappy
  /// and provides a better offline experience.
  Future<void> fetchProvider() async {
    var provider = await _database.calendarDetails.get(id);
    _provider = provider;

    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    if (!_loading) {
      return refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchCalendarProvider(session!.apiToken, id);
    await _database.calendarDetails.set(result);
    _provider = result;
    _loading = false;

    notifyListeners();
  }

  /// Create a calendar that will be synced
  Future<void> addCalendar(CalendarSource source) async {
    var updated = await actions.createSource(session!.apiToken, source);

    provider.replaceSource(updated);
    await _database.calendarDetails.set(provider);

    notifyListeners();
  }

  /// Have the server refresh calendar events for a given synced calendar.
  Future<void> syncEvents(CalendarSource source) async {
    var updated = await actions.syncSource(session!.apiToken, source);

    provider.replaceSource(updated);
    await _database.calendarDetails.set(provider);

    notifyListeners();
  }

  /// Remove a calendar that will be synced
  Future<void> removeSource(CalendarSource source) async {
    await actions.deleteSource(session!.apiToken, source);

    provider.removeSource(source);
    await _database.calendarDetails.set(provider);

    notifyListeners();
  }

  /// Link a calendar that will be synced
  Future<void> linkSource(source) async {
    source.calendarProviderId = provider.id;
    await actions.createSource(session!.apiToken, source);

    provider.replaceSource(source);
    await _database.calendarDetails.set(provider);

    notifyListeners();
  }

  /// Update properties on a calendar source
  Future<void> updateSource(source) async {
    await actions.updateSource(session!.apiToken, source);

    provider.replaceSource(source);
    await _database.calendarDetails.set(provider);

    notifyListeners();
  }
}
