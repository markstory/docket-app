import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/providers/session.dart';


class ProjectArchiveViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Task list
  List<Project> _projects = [];

  ProjectArchiveViewModel(LocalDatabase database, this.session) {
    _database = database;
    _projects = [];

    _database.projectArchive.addListener(() async {
      refresh();
    });
  }

  bool get loading => _loading;
  List<Project> get projects => _projects;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchData();
    if (!_loading && (_projects.isEmpty || !_database.projectArchive.isFresh())) {
      return refresh();
    }
  }

  Future<void> fetchData() async {
    var result = await _database.projectArchive.get();
    if (result != null) {
      _projects = result;

      notifyListeners();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchProjectArchive(session!.apiToken);
    await _database.projectArchive.set(result);
    _projects = result;
    _loading = false;

    notifyListeners();
  }
}
