import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/task.dart';
import 'package:docket/providers/session.dart';


class ProjectCompletedViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;
  /// Are we reloading without a spinner?
  bool _silentLoading = false;

  /// Task list
  List<Task> _tasks = [];

  /// The project slug being viewed.
  String _slug = '';

  ProjectCompletedViewModel(LocalDatabase database, this.session) {
    _database = database;
    _tasks = [];

    _database.completedTasks.addListener(() async {
      loadData();
    });
  }

  bool get loading => (_loading && !_silentLoading);
  List<Task> get tasks => _tasks;
  String get slug => _slug;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Set the slug
  /// If the slug changes data will be refreshed.
  setSlug(String slug) {
    _slug = slug;
  }

  Future<void> fetchData() async {
    _loading = true;
    var result = await _database.completedTasks.get(slug);
    if (!result.isEmpty) {
      _tasks = result.tasks;
    }
    _loading = false;

    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchData();
    if (!_loading && _tasks.isEmpty) {
      return refresh();
    }
    if (!_loading && !_database.completedTasks.isFresh()) {
      return refreshSilent();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    assert(_slug.isNotEmpty, "A slug is required to load data");

    _loading = true;
    var result = await actions.fetchCompletedTasks(session!.apiToken, _slug);
    await _database.completedTasks.set(result);
    _loading = false;
    _tasks = result.tasks;

    notifyListeners();
  }

  /// Refresh from the server without a loading indicator
  Future<void> refreshSilent() async {
    assert(_slug.isNotEmpty, "A slug is required to load data");

    _loading = _silentLoading = true;

    var result = await actions.fetchCompletedTasks(session!.apiToken, _slug);
    await _database.completedTasks.set(result);

    _loading = _silentLoading = false;

    _tasks = result.tasks;
  }
}
