import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/models/project.dart';

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  ProjectsProvider(LocalDatabase database, this.session) {
    _database = database;
  }

  void setSession(SessionProvider session) {
    this.session = session;
  }

  Future<void> clear() async {
    await _database.clearProjects();
    notifyListeners();
  }

  /// Create a project on the server and notify listeners.
  Future<Project> createProject(Project project) async {
    project = await actions.createProject(session!.apiToken, project);

    await _database.addProjects([project]);
    notifyListeners();

    return project;
  }

  /// Fetch a project from the API and notifyListeners.
  Future<void> fetchBySlug(String slug) async {
    // TODO Perhaps this is where cache expiration should be checked.
    // Doing it here would let network calls to be skipped which
    // would be nice.
    var projectDetails = await actions.fetchProjectBySlug(session!.apiToken, slug);

    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);
    notifyListeners();
  }

  /// Read a project from the local database by slug.
  Future<Project> getBySlug(String slug) async {
    return await _database.fetchProjectBySlug(slug);
  }

  /// Fetch projects from the API and notifyListeners
  Future<void> fetchProjects() async {
    var projects = await actions.fetchProjects(session!.apiToken);
    await _database.addProjects(projects);
    notifyListeners();
  }

  /// Get the project list from the local database.
  Future<List<Project>> getAll() async {
    return await _database.fetchProjects();
  }

  /// Move a project on the server and locally
  /// and then notifyListeners
  Future<void> move(Project project, int newRank) async {
    project = await actions.moveProject(session!.apiToken, project, newRank);
    await _database.updateProject(project);
    notifyListeners();
  }
}
