import 'dart:developer' as developer;
import 'package:json_cache/json_cache.dart';
import 'package:flutter/material.dart';
import 'package:localstorage/localstorage.dart';

import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/userprofile.dart';

class StaleDataError implements Exception {}

const isStale = '__is_stale__';

class LocalDatabase {
  static final LocalDatabase _instance = LocalDatabase();

  // Configuration
  static const String dbName = 'docket-localstorage';

  /// Key used to lazily expire data.
  /// Contains a structure of `{key: timestamp}`
  /// Where key is one of the keys above, and timestamp
  /// is the expiration time for the key.
  static const String expiredKey = 'v1:expired';

  JsonCache? _database;

  late TodayView today;
  late UpcomingView upcoming;
  late TaskDetailsView taskDetails;
  late ProjectMapView projectMap;
  late ProjectDetailsView projectDetails;
  late ProjectArchiveView projectArchive;
  late CompletedTasksView completedTasks;
  late TrashbinView trashbin;
  late ApiTokenCache apiToken;
  late ProfileCache profile;

  LocalDatabase() {
    var db = database();
    today = TodayView(db, const Duration(hours: 1));
    upcoming = UpcomingView(db, const Duration(hours: 1));
    taskDetails = TaskDetailsView(db, const Duration(hours: 1));
    projectMap = ProjectMapView(db, const Duration(hours: 1));
    projectDetails = ProjectDetailsView(db, const Duration(hours: 1));
    projectArchive = ProjectArchiveView(db, const Duration(hours: 1));
    completedTasks = CompletedTasksView(db, const Duration(hours: 1));
    trashbin = TrashbinView(db, const Duration(hours: 1));
    apiToken = ApiTokenCache(db, null);
    profile = ProfileCache(db, const Duration(hours: 1));
  }

  factory LocalDatabase.instance() {
    return LocalDatabase._instance;
  }

  /// Lazily create the database.
  JsonCache database() {
    if (_database != null) {
      return _database!;
    }
    final LocalStorage storage = LocalStorage(dbName);
    _database = JsonCacheMem(JsonCacheLocalStorage(storage));
    return _database!;
  }

  /// Locate which date based views a task would be involved in.
  ///
  /// When tasks are added/removed we need to update or expire
  /// the view entries those tasks will be displayed in.
  ///
  /// In a SQL based storage you'd be able to remove/update the row
  /// individually. Because our local database is view-based. We need
  /// custom logic to locate the views and then update those views.
  List<String> _taskViews(Task task) {
    var now = DateTime.now();
    List<String> views = [];

    // If the task has a due date expire upcoming and possibly
    // today views.
    if (task.dueOn != null) {
      var delta = task.dueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TodayView.name);
      }
      views.add(UpcomingView.name);
    }

    if (task.previousDueOn != null) {
      var delta = task.previousDueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TodayView.name);
      }
      views.add(UpcomingView.name);
    }

    if (task.deletedAt != null) {
      views.add(TrashbinView.name);
    }

    return views;
  }

  /// Expire task views for a task.
  /// When a task is updated or created we need to
  /// clear the local cache so that the new item is visible.
  ///
  /// In a SQL based storage you'd be able to remove/update the row
  /// individually. Because our local database is view-based. We need
  /// custom logic to remove cached data for the impacted views.
  /// This ensures that we don't provide stale state to the Provider
  /// layer and instead Providers fetch fresh data from the Server.
  Future<void> _expireTaskViews(Task task) async {
    List<Future> futures = [];

    // Remove the project key so we read fresh data next time.
    futures.add(projectDetails.remove(task.projectSlug));

    for (var key in _taskViews(task)) {
      switch (key) {
        case TodayView.name:
          futures.add(today.clear());
          break;
        case UpcomingView.name:
          futures.add(upcoming.clear());
          break;
        case TrashbinView.name:
          futures.add(trashbin.clear());
          break;
        default:
          throw 'Unknown view key of $key';
      }
    }
    await Future.wait(futures);
  }

  /// Directly set a key. Avoid use outside of tests.
  Future<void> set(String key, Map<String, Object?> value) async {
    await database().refresh(key, value);
  }
  // }}}

  // Task Methods. {{{

  /// Store a list of Tasks.
  ///
  /// Each task will added to the relevant date/project
  /// views as well as the task lookup map
  /// Mostly used in tests.
  Future<void> addTasks(List<Task> tasks, {bool expire = false}) async {
    List<Future> futures = [];
    for (var task in tasks) {
      // Refresh task in taskDetails lookup.
      futures.add(taskDetails.set(task));

      if (expire) {
        // Update the pending view updates.
        for (var view in _taskViews(task)) {
          switch (view) {
            case TodayView.name:
              futures.add(today.clear());
              break;
            case UpcomingView.name:
              futures.add(upcoming.clear());
              break;
            default:
              throw 'Unknown view to clear "$view"';
          }
        }
        futures.add(projectDetails.remove(task.projectSlug));
      }
    }
    // TODO this should increment the project task totals.

    await Future.wait(futures);
  }

  /// Replace a task in the local database.
  /// This will update all task views with the new data.
  Future<void> updateTask(Task task) async {
    await addTasks([task]);
    return _expireTaskViews(task);
  }

  Future<void> deleteTask(Task task) async {
    var id = task.id;
    if (id == null) {
      return;
    }
    await taskDetails.remove(id);
    await projectMap.decrement(task.projectSlug);

    return _expireTaskViews(task);
  }

  Future<void> undeleteTask(Task task) async {
    await trashbin.clear();

    return _expireTaskViews(task);
  }
  // }}}

  // Project methods {{{

  /// Add a list of projects to the local database.
  Future<void> addProjects(List<Project> projects) async {
    await Future.wait(projects.map((item) => projectMap.set(item)).toList());
  }

  /// Update a project in the project list state.
  Future<void> updateProject(Project project) async {
    await Future.wait([
      projectMap.set(project),
      projectDetails.remove(project.slug),
    ]);
  }
  // }}}

  // Clearing methods {{{
  Future<List<void>> clearTasks() async {
    return Future.wait([
      taskDetails.clear(),
      today.clear(),
      upcoming.clear(),
      projectDetails.clear(),
      completedTasks.clear(),
    ]);
  }

  Future<List<void>> clearProjects() async {
    return Future.wait([
      projectMap.clear(),
      projectDetails.clear(),
      projectArchive.clear(),
      completedTasks.clear(),
    ]);
  }
  // }}}
}

/// Abstract class that will act as the base of the ViewCache based database implementation.
/// Listeners will be notified when this view cache is cleared.
abstract class ViewCache<T> extends ChangeNotifier {
  late JsonCache _database;
  Duration? duration;

  Map<String, dynamic>? _state;

  ViewCache(JsonCache database, this.duration) {
    _database = database;
  }

  bool isFresh(String? updated) {
    // Empty data is 'fresh'. This prevents loops
    if (updated == null || duration == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = DateTime.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
  }

  /// Refresh the data stored for the 'today' view.
  Future<void> _set(Map<String, dynamic> data) async {
    var payload = {'updatedAt': DateTime.now().toIso8601String(), 'data': data};
    _state = payload;
    await _database.refresh(keyName(), payload);
  }

  /// Refresh the data stored for the 'today' view.
  Future<Map<String, dynamic>?> _get() async {
    var state = _state;
    if (state != null && isFresh(state['updatedAt'])) {
      return state['data'];
    }
    var payload = await _database.value(keyName());
    if (payload == null || !isFresh(payload['updatedAt'])) {
      return null;
    }
    _state = payload;
    return payload['data'];
  }

  /// Clear the locally cached data. Will notify listeners as well.
  Future<void> clear() async {
    _state = null;
    await _database.remove(keyName());
    notifyListeners();
  }

  /// Get the keyname for this viewcache,
  String keyName();

  /// Set data into the view cache.
  Future<void> set(T data);
}

class TodayView extends ViewCache<TaskViewData> {
  static const String name = 'today';

  TodayView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'today' view.
  @override
  Future<void> set(TaskViewData todayData) async {
    return _set(todayData.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(missingData: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }
}

class UpcomingView extends ViewCache<TaskViewData> {
  static const String name = 'upcoming';

  UpcomingView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'upcoming' view.
  @override
  Future<void> set(TaskViewData data) async {
    return _set(data.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(missingData: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }
}

class TrashbinView extends ViewCache<TaskViewData> {
  static const String name = 'trashbin';

  TrashbinView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'today' view.
  @override
  Future<void> set(TaskViewData todayData) async {
    return _set(todayData.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(missingData: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }
}

// A map based view data provider
class TaskDetailsView extends ViewCache<Task> {
  static const String name = 'taskdetails';

  TaskDetailsView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a task into the details view.
  @override
  Future<void> set(Task task) async {
    var current = await _get() ?? {};
    current[task.id.toString()] = task.toMap();

    return _set(current);
  }

  Future<Task?> get(int id) async {
    var taskId = id.toString();
    var data = await _get();
    // Likely loading.
    if (data == null || data[taskId] == null) {
      return null;
    }
    return Task.fromMap(data[taskId]);
  }

  Future<void> remove(int id) async {
    var data = await _get() ?? {};
    var taskId = id.toString();

    data.remove(taskId);
    return _set(data);
  }
}

// A map based view data provider
class ProjectMapView extends ViewCache<Project> {
  static const String name = 'projectmap';

  ProjectMapView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a project into the lookup
  @override
  Future<void> set(Project project) async {
    var current = await _get() ?? {};
    current[project.slug] = project.toMap();

    return _set(current);
  }

  /// Replace all projects in the mapping.
  /// Useful when refreshing from the server to handle project
  /// renames or slug changes.
  Future<void> replace(List<Project> projects) async {
    Map<String, dynamic> map = {};
    for (var project in projects) {
      map[project.slug] = project.toMap();
    }
    return _set(map);
  }

  Future<void> addMany(List<Project> projects) async {
    var current = await _get() ?? {};
    for (var project in projects) {
      current[project.slug] = project.toMap();
    }
    return _set(current);
  }

  Future<Project?> get(String slug) async {
    var data = await _get() ?? {};
    // Likely loading.
    if (data[slug] == null) {
      return null;
    }
    return Project.fromMap(data[slug]);
  }

  Future<List<Project>> all() async {
    var data = await _get();
    if (data == null) {
      return [];
    }
    var projects = data.values.map((item) => Project.fromMap(item)).toList();
    projects.sort((a, b) => a.ranking.compareTo(b.ranking));
    return projects;
  }

  Future<void> decrement(String slug) async {
    var data = await _get() ?? {};
    if (data[slug] == null) {
      return;
    }
    var project = Project.fromMap(data[slug]);
    project.incompleteTaskCount -= 1;

    return set(project);
  }

  Future<void> remove(String slug) async {
    var data = await _get() ?? {};
    data.remove(slug);
    return _set(data);
  }

  Future<void> removeById(int id) async {
    var data = await _get() ?? {};
    data.removeWhere((key, value) => value['id'] == id);
    return _set(data);
  }
}

// A map based view data provider
class ProjectDetailsView extends ViewCache<ProjectWithTasks> {
  static const String name = 'projectdetails';

  ProjectDetailsView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a project into the lookup
  @override
  Future<void> set(ProjectWithTasks view) async {
    var current = await _get() ?? {};
    current[view.project.slug] = view.toMap();

    return _set(current);
  }

  Future<ProjectWithTasks> get(String slug) async {
    var data = await _get();
    // Likely loading.
    if (data == null || data[slug] == null) {
      return ProjectWithTasks(
        project: Project.blank(),
        tasks: [],
        missingData: true,
      );
    }
    return ProjectWithTasks.fromMap(data[slug]);
  }

  Future<void> remove(String slug) async {
    var data = await _get() ?? {};
    data.remove(slug);
    return _set(data);
  }
}

class ProjectArchiveView extends ViewCache<List<Project>> {
  static const String name = 'projectarchive';

  ProjectArchiveView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'upcoming' view.
  @override
  Future<void> set(List<Project> data) async {
    return _set({'projects': data.map((project) => project.toMap()).toList()});
  }

  Future<List<Project>?> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['projects'] == null) {
      return null;
    }

    return (data['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();
  }
}

// A map based view data provider
class CompletedTasksView extends ViewCache<ProjectWithTasks> {
  static const String name = 'completedTasks';

  CompletedTasksView(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(ProjectWithTasks view) async {
    var current = await _get() ?? {};
    current[view.project.slug] = view.toMap();

    return _set(current);
  }

  Future<ProjectWithTasks> get(String slug) async {
    var data = await _get();
    // Likely loading.
    if (data == null || data[slug] == null) {
      return ProjectWithTasks(
        project: Project.blank(),
        tasks: [],
        missingData: true,
      );
    }
    return ProjectWithTasks.fromMap(data[slug]);
  }

  Future<void> remove(String slug) async {
    var data = await _get() ?? {};
    data.remove(slug);
    return _set(data);
  }
}

class ApiTokenCache extends ViewCache<ApiToken> {
  static const String name = 'apitoken';

  ApiTokenCache(JsonCache database, Duration? duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(ApiToken token) async {
    return _set(token.toMap());
  }

  Future<ApiToken?> get() async {
    var data = await _get();
    if (data == null) {
      return null;
    }
    return ApiToken.fromMap(data);
  }
}

class ProfileCache extends ViewCache<UserProfile> {
  static const String name = 'userprofile';

  ProfileCache(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(UserProfile token) async {
    return _set(token.toMap());
  }

  Future<UserProfile?> get() async {
    var data = await _get();
    if (data == null) {
      return null;
    }
    return UserProfile.fromMap(data);
  }
}
