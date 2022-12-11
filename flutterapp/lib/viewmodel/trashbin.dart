import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';


class TrashbinViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Task list
  List<Task> _tasks = [];

  TrashbinViewModel(LocalDatabase database, this.session) {
    _database = database;
    _tasks = [];

    _database.trashbin.addListener(() async {
      refresh();
    });
  }

  bool get loading => _loading;
  List<Task> get tasks => _tasks;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    var result = await _database.trashbin.get();
    if (!result.isEmpty) {
      _tasks = result.tasks;
    }
    if (!_loading) {
      return refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchTrashbin(session!.apiToken);
    await _database.trashbin.set(result);
    _tasks = result.tasks;
    _loading = false;

    notifyListeners();
  }
}
