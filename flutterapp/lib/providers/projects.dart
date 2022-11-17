import 'dart:developer' as developer;
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/models/project.dart';

enum ViewNames {
  projectArchive,
  projectDetails,
  projectMap,
  completedTasks,
}

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  Set<ViewNames> _pending = {};
  Map<ViewNames, int> _retryCount = {};

  ProjectsProvider(LocalDatabase database, this.session) {
    _database = database;
    _pending = {};
    _retryCount = {};
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

  void setSession(SessionProvider session) {
    this.session = session;
  }

  Future<void> clear() async {
    await _database.clearProjects();
    notifyListeners();
  }

  /// Get the project list from the local database.
  Future<List<Project>> getAll() async {
    var projects = await _database.projectMap.all();
    // We don't have a good indicator of an empty states.
    if (projects.isEmpty && _recordRetry(ViewNames.projectMap, 5)) {
      fetchProjects();
    }
    return projects;
  }

  /// Create a project on the server and notify listeners.
  Future<Project> createProject(Project project) async {
    project = await actions.createProject(session!.apiToken, project);

    await _database.projectMap.set(project);
    notifyListeners();

    return project;
  }

  /// Update the project map and clear local data for project details.
  Future<Project> update(Project project) async {
    project = await actions.updateProject(session!.apiToken, project);

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
    await _withPending(ViewNames.projectMap, () async {
      var projects = await actions.fetchProjects(session!.apiToken);
      return _database.projectMap.replace(projects);
    });

    notifyListeners();
  }

  /// Move a project on the server and locally
  /// and then notifyListeners
  Future<void> move(Project project, int newRank) async {
    await _withPending(ViewNames.projectArchive, () async {
      project = await actions.moveProject(session!.apiToken, project, newRank);
      return _database.projectMap.set(project);
    });

    notifyListeners();
  }

  /// Archive a project and remove the project the project and project details.
  Future<Project> archive(Project project) async {
    await actions.archiveProject(session!.apiToken, project);
    await Future.wait([
      _database.projectMap.remove(project.slug),
      _database.projectDetails.remove(project.slug),
      _database.projectArchive.clear(),
    ]);
    notifyListeners();

    return project;
  }

  /// Un-archive a project
  Future<void> unarchive(Project project) async {
    await actions.unarchiveProject(session!.apiToken, project);
    await Future.wait([
      _database.projectMap.clear(),
      _database.projectArchive.clear(),
    ]);
    await fetchProjects();

    notifyListeners();
  }

  /// Delete a project and remove the project the project and project details.
  Future<void> delete(Project project) async {
    await actions.deleteProject(session!.apiToken, project);
    await Future.wait([
      _database.projectMap.remove(project.slug),
      _database.projectDetails.remove(project.slug),
      _database.projectArchive.clear(),
    ]);
    notifyListeners();
  }

  // Section Methods {{{
  // Remove a section and clear the project details view cache
  Future<void> createSection(Project project, Section section) async {
    await actions.createSection(session!.apiToken, project, section);
    await _database.projectDetails.remove(project.slug);

    notifyListeners();
  }

  // Remove a section and clear the project details view cache
  Future<void> deleteSection(Project project, Section section) async {
    await actions.deleteSection(session!.apiToken, project, section);
    await _database.projectDetails.remove(project.slug);

    notifyListeners();
  }

  /// Read a project from the local database by slug.
  Future<void> updateSection(Project project, Section section) async {
    await actions.updateSection(session!.apiToken, project, section);
    await _database.projectDetails.remove(project.slug);

    notifyListeners();
  }

  /// Read a project from the local database by slug.
  Future<void> moveSection(Project project, Section section, int newIndex) async {
    await actions.moveSection(session!.apiToken, project, section, newIndex);
    section.ranking = newIndex;
    await _database.projectDetails.remove(project.slug);

    notifyListeners();
  }
  // }}}
}
