import 'package:clock/clock.dart';
import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';

// Store projects + task lists by slug.
// Data is stored by slug to make fetching consistent
// with API endpoints. Some operations search by id, and these
// changes run at O(n).
class ProjectDetailsRepo extends Repository<ProjectWithTasks> {
  static const String name = 'projectdetails';

  final Map<String, DateTime?> _lastUpdate = {};

  ProjectDetailsRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a project into the lookup
  @override
  Future<void> set(ProjectWithTasks view) async {
    var current = await getMap() ?? {};
    current[view.project.slug] = view.toMap();
    _lastUpdate[view.project.slug] = clock.now();

    return setMap(current);
  }

  /// Add a task to the relevant project collecction
  /// Will expire the modified project and notify
  Future<void> append(Task task) async {
    var data = await get(task.projectSlug);
    data.tasks.add(task);

    await set(data);
    expireSlug(task.projectSlug, notify: true);
  }

  /// Replace a task in both the current and previous projects.
  ///
  /// Uses `task.projectSlug` and `previousProject` to find
  /// projects that need to be updated. Will notify.
  Future<void> updateTask(Task task) async {
    // The task was moved between projects.
    var previousProject = task.previousProjectSlug;
    if (previousProject != null && previousProject != task.projectSlug) {
      var source = await get(previousProject);
      var index = source.tasks.indexWhere((item) => item.id == task.id);
      if (index > -1) {
        source.tasks.removeAt(index);
        await set(source);
        _lastUpdate[previousProject] = null;
      }
    }

    // Replace/Insert the task into the current project.
    var projectData = await get(task.projectSlug);
    var index = projectData.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      projectData.tasks.removeAt(index);
      projectData.tasks.insert(index, task);
    } else {
      projectData.tasks.add(task);
    }

    await set(projectData);
    expireSlug(task.projectSlug, notify: true);
  }

  /// Remove a task from the project with `slug`.
  /// Does not notify.
  Future<void> removeTask(String slug, Task task) async {
    var projectData = await get(slug);
    var index = projectData.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      projectData.tasks.removeAt(index);
      await set(projectData);
      expireSlug(slug, notify: true);
    }
  }

  /// Expire a project by slug
  void expireSlug(String slug, {bool notify = false}) {
    _lastUpdate[slug] = null;
    if (notify) {
      notifyListeners();
    }
  }

  /// Check if local data for the project matching slug is fresh
  /// Freshness determined by `duration`
  bool isFreshSlug(String? slug) {
    if (duration == null) {
      return true;
    }
    if (slug == null) {
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

  /// Remove a project from the local data and update listeners.
  Future<void> remove(String slug) async {
    var data = await getMap() ?? {};
    data.remove(slug);
    await setMap(data);

    notifyListeners();
  }
}
