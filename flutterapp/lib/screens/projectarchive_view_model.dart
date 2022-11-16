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

  /// Whether or not data should be reloaded
  bool _shouldReload = false;

  /// Task list
  List<Project> _projects = [];

  ProjectArchiveViewModel(LocalDatabase database, this.session) {
    _database = database;
    _projects = [];

    _database.projectArchive.addListener(() async {
      _shouldReload = true;
    });
  }

  bool get loading => _loading;
  List<Project> get projects => _projects;

  setSession(SessionProvider value) {
    session = value;
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    if (_shouldReload || !_loading) {
      return refresh();
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
