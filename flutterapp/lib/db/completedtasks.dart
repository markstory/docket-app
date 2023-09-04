import 'package:clock/clock.dart';
import 'package:docket/models/task.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/project.dart';

// A map based view data provider
class CompletedTasksRepo extends Repository<ProjectWithTasks> {
  static const String name = 'completedTasks';

  final Map<String, DateTime?> _lastUpdate = {};

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
    _lastUpdate[view.project.slug] = clock.now();

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

  /// Remove a task and notify listeners.
  Future<void> removeTask(Task task) async {
    var data = await getMap() ?? {};
    var slug = task.projectSlug;
    if (data[slug] == null || data[slug]['tasks'] == null) {
      return;
    }
    List<Map<String, dynamic>> tasks = data[slug]['tasks'];
    tasks.removeWhere((item) => item['id'] == task.id);
    await setMap(data);

    notifyListeners();
  }

  Future<void> remove(String slug) async {
    var data = await getMap() ?? {};

    data.remove(slug);
    _lastUpdate[slug] = null;

    return setMap(data);
  }

  /// Mark a slug as expired and needing to be reloaded.
  /// Will also notify listeners.
  void expireSlug(String slug) {
    _lastUpdate[slug] = null;
    notifyListeners();
  }

  /// check if local data for a given slug is fresh.
  /// Freshness is determined by `duration`.
  bool isFreshSlug(String slug) {
    if (slug.isEmpty || duration == null) {
      return false;
    }
    var lastUpdate = _lastUpdate[slug];
    if (lastUpdate == null) {
      return false;
    }
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return lastUpdate.isAfter(expires);
  }
}
