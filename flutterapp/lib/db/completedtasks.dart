import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/project.dart';

// A map based view data provider
class CompletedTasksRepo extends Repository<ProjectWithTasks> {
  static const String name = 'completedTasks';

  CompletedTasksRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(ProjectWithTasks view) async {
    var current = await getMap() ?? {};
    current[view.project.slug] = view.toMap();

    return setMap(current);
  }

  Future<ProjectWithTasks> get(String slug) async {
    var data = await getMap();
    // Likely loading.
    if (data == null || data[slug] == null) {
      return ProjectWithTasks(
        project: Project.blank(),
        tasks: [],
        isEmpty: true,
      );
    }
    return ProjectWithTasks.fromMap(data[slug]);
  }

  Future<void> remove(String slug) async {
    var data = await getMap() ?? {};
    data.remove(slug);
    return setMap(data);
  }
}
