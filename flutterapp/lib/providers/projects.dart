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

  Future<Project> getBySlug(String apiToken, String slug) async {
    late Project? project;
    try {
      project = await _database.fetchProjectBySlug(slug);
    } catch (e) {
      rethrow;
    }
    if (project == null) {
      project = await actions.fetchProjectBySlug(apiToken, slug);
      await _database.addProjects([project]);
    }

    return project;
  }

  Future<List<Project>> getProjects(String apiToken) async {
    late List<Project>? projects;
    try {
      projects = await _database.fetchProjects();
    } catch (e) {
      rethrow;
    }
    if (projects.isEmpty) {
      projects = await actions.fetchProjects(apiToken);
      await _database.addProjects(projects);
    }

    return projects;
  }
}
