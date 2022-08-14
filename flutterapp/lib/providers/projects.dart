import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/models/project.dart';

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  Set<String> _pending = {};

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

    await _database.projectMap.set(project);
    notifyListeners();

    return project;
  }

  /// Fetch a project from the API and notifyListeners.
  Future<void> fetchBySlug(String slug) async {
    // TODO add cache checks
    var projectDetails = await actions.fetchProjectBySlug(session!.apiToken, slug);

    _pending.add(_database.projectDetails.keyName());
    await _database.projectDetails.set(projectDetails);
    _pending.remove(_database.projectDetails.keyName());

    notifyListeners();
  }

  /// Read a project from the local database by slug.
  Future<ProjectWithTasks?> getBySlug(String slug) async {
    return _database.projectDetails.get(slug);
  }

  /// Fetch projects from the API and notifyListeners
  Future<void> fetchProjects() async {
    var projects = await actions.fetchProjects(session!.apiToken);

    await _database.projectMap.addMany(projects);

    notifyListeners();
  }

  /// Get the project list from the local database.
  Future<List<Project>> getAll() async {
    return _database.projectMap.all();
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
}
