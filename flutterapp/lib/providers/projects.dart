import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/models/project.dart';

enum ViewNames {
  projectDetails,
  projectMap,
}

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  Set<ViewNames> _pending = {};

  ProjectsProvider(LocalDatabase database, this.session) {
    _database = database;
    _pending = {};
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
    return _database.projectMap.all();
  }

  /// Read a project from the local database by slug.
  Future<ProjectWithTasks> getBySlug(String slug) async {
    var projectData = await _database.projectDetails.get(slug);
    if (_pending.contains(ViewNames.projectDetails)) {
      projectData.pending = true;
    }
    return projectData;
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

    await _database.projectMap.set(project);
    await _database.projectDetails.remove(project.slug);
    notifyListeners();

    return project;
  }

  /// Fetch a project from the API and notifyListeners.
  Future<void> fetchBySlug(String slug) async {
    // TODO add cache checks
    var projectDetails = await actions.fetchProjectBySlug(session!.apiToken, slug);

    _pending.add(ViewNames.projectDetails);
    await _database.projectDetails.set(projectDetails);
    _pending.remove(ViewNames.projectDetails);

    notifyListeners();
  }

  /// Fetch project list from the API and notifyListeners
  Future<void> fetchProjects() async {
    _pending.add(ViewNames.projectMap);
    var projects = await actions.fetchProjects(session!.apiToken);
    _pending.remove(ViewNames.projectMap);

    await _database.projectMap.addMany(projects);

    notifyListeners();
  }

  /// Move a project on the server and locally
  /// and then notifyListeners
  Future<void> move(Project project, int newRank) async {
    project = await actions.moveProject(session!.apiToken, project, newRank);

    await Future.wait([
      _database.projectMap.set(project),
      _database.projectDetails.remove(project.slug),
    ]);

    notifyListeners();
  }

  /// Archive a project and remove the project the project and project details.
  Future<Project> archive(Project project) async {
    await actions.archiveProject(session!.apiToken, project);
    await _database.projectMap.remove(project.slug);
    await _database.projectDetails.remove(project.slug);
    notifyListeners();

    return project;
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
