import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';

class ProjectEditViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  String? _slug;
  Project? _project;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  ProjectEditViewModel(LocalDatabase database) {
    _database = database;
    _database.projectDetails.addListener(listener);
  }

  @override
  void dispose() {
    _database.projectDetails.removeListener(listener);
    super.dispose();
  }

  void listener() {
    refresh();
  }

  bool get loading => _loading;

  String get slug {
    var value = _slug;
    if (value == null) {
      throw Exception("Cannot read slug it has not been set.");
    }
    return value;
  }

  Project get project {
    var value = _project;
    if (value == null) {
      throw Exception("Cannot read project as it has not been set");
    }
    return value;
  }

  setSlug(String slug) {
    _slug = slug;
  }

  Future<void> fetchProject() async {
    _loading = true;
    var result = await _database.projectDetails.get(slug);
    _project = result.project;
    _loading = false;

    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchProject();
    if (!_loading && (_project == null || !_database.projectDetails.isExpiredSlug(slug))) {
      return refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchProjectBySlug(_database.apiToken.token, slug);
    await _database.projectDetails.set(result);
    _project = result.project;
    _loading = false;

    notifyListeners();
  }

  /// Update a project.
  Future<void> update(Project project) async {
    project = await actions.updateProject(_database.apiToken.token, project);

    // Remove the old entry by id as the slug could have been changed.
    // Remove the projectDetails view cache as well.
    await _database.projectMap.removeById(project.id);
    await _database.projectMap.set(project);
    await _database.projectDetails.remove(project.slug);

    _project = project;
    _slug = project.slug;

    notifyListeners();
  }
}
