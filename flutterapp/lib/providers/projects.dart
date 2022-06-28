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

  Future<Project> createProject(String apiToken, Project project) async {
    project = await actions.createProject(apiToken, project);

    await _database.addProjects([project]);
    notifyListeners();

    return project;
  }

  Future<Project> fetchBySlug(String apiToken, String slug) async {
    // TODO Perhaps this is where cache expiration should be checked.
    // Doing it here would let network calls to be skipped which
    // would be nice.
    var projectDetails = await actions.fetchProjectBySlug(apiToken, slug);

    await _database.addProjectTasks(projectDetails.project, projectDetails.tasks);
    notifyListeners();

    return projectDetails.project;
  }

  Future<Project> getBySlug(String slug) async {
    return await _database.fetchProjectBySlug(slug);
  }

  Future<List<Project>> fetchProjects(String apiToken) async {
    var projects = await actions.fetchProjects(apiToken);
    await _database.addProjects(projects);
    notifyListeners();

    return projects;
  }

  Future<List<Project>> getProjects() async {
    return await _database.fetchProjects();
  }
}
