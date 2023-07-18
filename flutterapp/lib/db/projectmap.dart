import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/project.dart';

// A map based view data provider
class ProjectMapRepo extends Repository<Project> {
  static const String name = 'projectmap';

  ProjectMapRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a project into the lookup
  @override
  Future<void> set(Project project) async {
    var current = await getMap() ?? {};
    current[project.slug] = project.toMap();

    return setMap(current);
  }

  /// Replace all projects in the mapping.
  /// Useful when refreshing from the server to handle project
  /// renames or slug changes.
  Future<void> replace(List<Project> projects) async {
    Map<String, dynamic> replacement = {};
    for (var project in projects) {
      replacement[project.slug] = project.toMap();
    }
    await setMap(replacement);

    notifyListeners();
  }

  Future<void> addMany(List<Project> projects) async {
    var current = await getMap() ?? {};
    for (var project in projects) {
      current[project.slug] = project.toMap();
    }
    return setMap(current);
  }

  Future<Project?> get(String slug) async {
    var data = await getMap() ?? {};
    // Likely loading.
    if (data[slug] == null) {
      return null;
    }
    return Project.fromMap(data[slug]);
  }

  Future<List<Project>> all() async {
    var data = await getMap();
    if (data == null) {
      return [];
    }
    var projects = data.values.map((item) => Project.fromMap(item)).toList();
    projects.sort((a, b) => a.ranking.compareTo(b.ranking));
    return projects;
  }

  // Decrement the incomplete task count for a project.
  Future<void> decrement(String slug) async {
    var data = await getMap() ?? {};
    if (data[slug] == null) {
      return;
    }
    var project = Project.fromMap(data[slug]);
    project.incompleteTaskCount -= 1;

    return set(project);
  }

  // Increment the incomplete task count for a project.
  Future<void> increment(String slug) async {
    var data = await getMap() ?? {};
    if (data[slug] == null) {
      return;
    }
    var project = Project.fromMap(data[slug]);
    project.incompleteTaskCount += 1;
    return set(project);
  }

  // Remove a project by slug.
  Future<void> remove(String slug) async {
    var data = await getMap() ?? {};
    data.remove(slug);
    await setMap(data);

    notifyListeners();
  }

  // Remove a project by id.
  Future<void> removeById(int id) async {
    var data = await getMap() ?? {};
    data.removeWhere((key, value) => value['id'] == id);
    return setMap(data);
  }
}
