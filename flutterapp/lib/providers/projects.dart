import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';

class ProjectsProvider extends ChangeNotifier {
  late LocalDatabase _database;

  ProjectsProvider(LocalDatabase database) {
    _database = database;
  }

  Future<void> clear() async {
    await _database.clearProjects();
    notifyListeners();
  }

  Future<Project> fetchBySlug(String apiToken, String slug) async {
    var projectDetails = await actions.fetchProjectBySlug(apiToken, slug);

    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);

    return projectDetails.project;
  }

  Future<Project> getBySlug(String slug) async {
    return await _database.fetchProjectBySlug(slug);
  }

  Future<List<Project>> fetchProjects(String apiToken) async {
    var projects = await actions.fetchProjects(apiToken);
    await _database.addProjects(projects);

    return projects;
  }

  Future<List<Project>> getProjects() async {
    return await _database.fetchProjects();
  }
}
