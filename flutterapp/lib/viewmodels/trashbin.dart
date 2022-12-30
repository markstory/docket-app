import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';


class TrashbinViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Task list
  List<Task> _tasks = [];

  TrashbinViewModel(LocalDatabase database) {
    _database = database;
    _tasks = [];

    _database.trashbin.addListener(listener);
  }

  @override
  void dispose() {
    _database.trashbin.removeListener(listener);
    super.dispose();
  }

  void listener() {
    loadData();
  }

  bool get loading => _loading;
  List<Task> get tasks => _tasks;

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchData();
    if (!_loading && (_tasks.isEmpty || !_database.trashbin.isFresh())) {
      return refresh();
    }
  }

  Future<void> fetchData() async {
    _loading = true;
    var result = await _database.trashbin.get();
    if (!result.isEmpty) {
      _tasks = result.tasks;
    }
    _loading = false;

    notifyListeners();
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchTrashbin(_database.apiToken.token);
    await _database.trashbin.set(result);
    _tasks = result.tasks;
    _loading = false;

    notifyListeners();
  }
}
