import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';

enum ViewNames {
  projectArchive,
  projectDetails,
  projectMap,
  completedTasks,
}

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;

  Set<ViewNames> _pending = {};
  Map<ViewNames, int> _retryCount = {};

  List<Project> _projects = [];
  bool _loading = false;

  ProjectsProvider(LocalDatabase database) {
    _database = database;
    _pending = {};
    _retryCount = {};
    _database.projectMap.addListener(listener);
  }

  void listener() {
    loadData();
    notifyListeners();
  }

  @override
  void dispose() {
    _database.projectMap.removeListener(listener);
    super.dispose();
  }

  List<Project> get projects => _projects;
  bool get loading => _loading;

  /// Only loads for local database.
  /// Doesn't reload from the server.
  Future<void> loadData() async {
    var wasEmpty = _projects.isEmpty;
    _projects = await _database.projectMap.all();
    if (wasEmpty && _projects.isNotEmpty) {
      notifyListeners();
    }

    // We don't have a good indicator of an empty states.
    if (_projects.isEmpty && _recordRetry(ViewNames.projectMap, 5)) {
      return fetchProjects();
    }
  }

  /// Record a retry and return an indication
  bool _recordRetry(ViewNames name, int limit) {
    var currentVal = _retryCount[name] ?? 0;
    // Check threshold
    if (currentVal >= limit) {
      return false;
    }
    _retryCount[name] = currentVal + 1;
    return true;
  }

  Future<void> clear() async {
    await _database.clearProjects();
    notifyListeners();
  }

  /// Get the project list from the local database.
  /// Deprecated: Use loadData() and .projects instead.
  Future<List<Project>> getAll() async {
    await loadData();
    return _projects;
  }

  /// Create a project on the server and notify listeners.
  Future<Project> createProject(Project project) async {
    project = await actions.createProject(_database.apiToken.token, project);

    await _database.projectMap.set(project);
    await _database.projectDetails.set(ProjectWithTasks(project: project, tasks: []));

    _projects = await _database.projectMap.all();
    notifyListeners();

    return project;
  }

  /// Update the project map and clear local data for project details.
  Future<Project> update(Project project) async {
    project = await actions.updateProject(_database.apiToken.token, project);

    // Remove the old entry by id as the slug could have been changed.
    // Remove the projectDetails view cache as well.
    await _database.projectMap.removeById(project.id);
    await _database.projectMap.set(project);
    await _database.projectDetails.remove(project.slug);
    notifyListeners();

    return project;
  }

  Future<void> _withPending(ViewNames view, Future<void> Function() callback) {
    if (_pending.contains(view)) {
      return Future.value(null);
    }
    _pending.add(view);
    try {
      return callback();
    } finally {
      _pending.remove(view);
    }
  }

  /// Fetch project list from the API and notifyListeners
  Future<void> fetchProjects() async {
    _loading = true;
    await fetchProjectsSilent();
    _loading = false;

    notifyListeners();
  }

  /// Fetch projects without using loading state or notifying
  Future<void> fetchProjectsSilent() async {
    var projects = await actions.fetchProjects(_database.apiToken.token);
    _database.projectMap.replace(projects);
    _projects = projects;
  }

  /// Move a project on the server and locally
  /// and then notifyListeners
  Future<void> move(Project project, int newRank) async {
    await _withPending(ViewNames.projectMap, () async {
      await actions.moveProject(_database.apiToken.token, project, newRank);
    });
    // Refetch the list as other projects will have new rankings
    await fetchProjects();

    notifyListeners();
  }

  /// Un-archive a project
  Future<void> unarchive(Project project) async {
    await actions.unarchiveProject(_database.apiToken.token, project);
    await Future.wait([
      _database.projectMap.clear(),
      _database.projectArchive.clear(),
    ]);
    await fetchProjects();

    notifyListeners();
  }

  /// Delete a project and remove the project the project and project details.
  Future<void> delete(Project project) async {
    await actions.deleteProject(_database.apiToken.token, project);
    await Future.wait([
      _database.projectMap.remove(project.slug),
      _database.projectDetails.remove(project.slug),
      _database.projectArchive.clear(),
    ]);
    notifyListeners();
  }
}
