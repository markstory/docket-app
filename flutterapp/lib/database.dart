import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:json_cache/json_cache.dart';
import 'package:localstorage/localstorage.dart';

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/userprofile.dart';

enum TaskCollections {
  today, upcoming, projectDetails, trashBin
}

/// Utility class that makes testing listeners easier.
class CallCounter {
  int callCount = 0;
  CallCounter(): callCount = 0;

  void call() {
    callCount += 1;
  }
}


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
  late CalendarProviderListCache calendarList;
  late CalendarProviderDetailsCache calendarDetails;

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
    calendarList = CalendarProviderListCache(db, const Duration(days: 1));
    calendarDetails = CalendarProviderDetailsCache(db, const Duration(days: 1));
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
  Set<TaskCollections> _taskViews(Task task) {
    var now = DateUtils.dateOnly(clock.now());
    Set<TaskCollections> views = {};

    // If the task has a due date expire upcoming and possibly
    // today views.
    if (task.dueOn != null) {
      var delta = task.dueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TaskCollections.today);
      }
      views.add(TaskCollections.upcoming);
    }

    if (task.previousDueOn != null) {
      var delta = task.previousDueOn?.difference(now);
      if (delta != null && delta.inDays <= 0) {
        views.add(TaskCollections.today);
      }
      views.add(TaskCollections.upcoming);
    }

    if (task.deletedAt != null) {
      views.add(TaskCollections.trashBin);
    }

    return views;
  }

  /// Directly set a key. Avoid use outside of tests.
  Future<void> set(String key, Map<String, Object?> value) async {
    await database().refresh(key, value);
  }
  // }}}

  // Task Methods. {{{

  /// Create a task in the local database.
  ///
  /// Each task will added to the relevant date/project
  /// views as well as the task lookup map
  ///
  Future<void> createTask(Task task) async {
    List<Future> futures = [];

    futures.add(taskDetails.set(task));
    futures.add(projectDetails.append(task));
    futures.add(projectMap.increment(task.projectSlug));

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          futures.add(today.append(task));
          break;
        case TaskCollections.upcoming:
          futures.add(upcoming.append(task));
          break;
        default:
          throw Exception('Unknown view to clear "$view"');
      }
    }

    await Future.wait(futures);
  }

  /// Update a task in the local database.
  ///
  /// This will update all task views with the new data.
  Future<void> updateTask(Task task) async {
    List<Future> futures = [];
    futures.add(taskDetails.set(task));
    futures.add(projectDetails.updateTask(task));

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          futures.add(today.updateTask(task, expire: true));
          break;
        case TaskCollections.upcoming:
          futures.add(upcoming.updateTask(task, expire: true));
          break;
        default:
          throw Exception('Unknown view to clear "$view"');
      }
    }
    // TODO update project counters

    await Future.wait(futures);
  }

  /// Remove a task from the local database
  /// Will expire and notify the relevant view caches.
  Future<void> deleteTask(Task task) async {
    var id = task.id;
    if (id == null) {
      return;
    }
    // TODO convert this to Future.wait(...)
    await taskDetails.remove(id);
    await projectDetails.removeTask(task.projectSlug, task);
    await projectMap.decrement(task.projectSlug);

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          await today.removeTask(task);
          break;
        case TaskCollections.upcoming:
          await upcoming.removeTask(task);
          break;
        default:
          throw Exception('Cannot expire view of $view');
      }
    }

    return expireTask(task);
  }

  Future<void> undeleteTask(Task task) async {
    task.deletedAt = null;
    trashbin.expire(notify: true);

    return updateTask(task);
  }

  /// Expire the views for the relevant task
  /// Will notify each view.
  void expireTask(Task task) {
    projectDetails.expireSlug(task.projectSlug, notify: true);

    for (var view in _taskViews(task)) {
      switch (view) {
        case TaskCollections.today:
          today.expire(notify: true);
          break;
        case TaskCollections.upcoming:
          upcoming.expire(notify: true);
          break;
        case TaskCollections.trashBin:
          trashbin.expire(notify: true);
          break;
        default:
          throw Exception('Cannot expire view of $view');
      }
    }
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
  Future<List<void>> clearSilent() async {
    return Future.wait([
      today.clearSilent(),
      upcoming.clearSilent(),
      taskDetails.clearSilent(),
      projectMap.clearSilent(),
      projectDetails.clearSilent(),
      projectArchive.clearSilent(),
      completedTasks.clearSilent(),
      trashbin.clearSilent(),
      profile.clearSilent(),
      calendarList.clearSilent(),
      calendarDetails.clearSilent(),
    ]);
  }

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

  /// In memory copy of raw data. Helps save overhead
  /// of reading from JsonCache repeatedly.
  Map<String, dynamic>? _state;

  /// The time data in this store was expired.
  DateTime? _expiredAt;

  ViewCache(JsonCache database, this.duration) {
    _database = database;
  }

  /// Check if the local data is within the cache duration.
  /// Stale data will be returned by _get(). Use this method 
  /// to see if a server refresh should be performed.
  bool isFresh() {
    var state = _state;
    if (state == null) {
      return false;
    }
    // No duration means always fresh.
    if (duration == null) {
      return true;
    }
    // Expired views are not fresh.
    if (_expiredAt != null) {
      return false;
    }
    var updated = state['updatedAt'];
    // No updatedAt means we need to refresh.
    if (updated == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
  }

  /// Mark the local data as expired/stale.
  ///
  /// This doesn't remove the data but does flag it as expired.
  /// Can notify if `notify` is set to true.
  void expire({bool notify = false}) {
    _expiredAt = clock.now();

    if (notify) {
      notifyListeners();
    }
  }

  /// Whether or not this view cache has been expired.
  bool get isExpired {
    return _expiredAt != null;
  }

  /// Update local database and in-process state as well.
  Future<void> _set(Map<String, dynamic> data) async {
    var payload = {'updatedAt': clock.now().toIso8601String(), 'data': data};
    _state = payload;
    _expiredAt = null;
    await _database.refresh(keyName(), payload);
  }

  /// Fetch data from the local store.
  Future<Map<String, dynamic>?> _get() async {
    var state = _state;
    if (state != null) {
      return state['data'];
    }
    var payload = await _database.value(keyName());
    if (payload == null) {
      return null;
    }
    _state = payload;
    return payload['data'];
  }

  /// Clear the locally cached data. Will notify listeners as well.
  Future<void> clear() async {
    _state = null;
    _expiredAt = null;
    await _database.remove(keyName());
    notifyListeners();
  }

  // Clear locally cached data. Will *not* notify
  Future<void> clearSilent() async {
    _state = null;
    _expiredAt = null;
    return _database.remove(keyName());
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

  /// Add a task to the end of the today view. Will notify
  Future<void> append(Task task) async {
    var data = await get();
    data.tasks.add(task);
    await set(data);
    expire();

    notifyListeners();
  }

  /// Update a task. Will either add/update or remove 
  /// a task from the Today view depending on the task details.
  /// Will notify on changes.
  Future<void> updateTask(Task task, {bool expire = true}) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (task.isToday) {
      // Add or replace depending
      if (index == -1) {
        data.tasks.add(task);
      } else {
        data.tasks.removeAt(index);
        data.tasks.insert(index, task);
      }
    } else if (index != -1) {
      // Task is no longer in today view remove it.
      data.tasks.removeAt(index);
    }
    await set(data);
    if (expire) {
      this.expire();
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      data.tasks.removeAt(index);
      await set(data);
      expire(notify: true);
    }
  }

  Future<TaskViewData> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
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
    // Empty local data
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }

  /// Add a task to the collection
  Future<void> append(Task task) async {
    var data = await get();
    data.tasks.add(task);
    await set(data);
    expire();

    notifyListeners();
  }

  // Update a task. Will either add/remove/update the
  // task based on its state. Will notify on changes.
  Future<void> updateTask(Task task, {expire = true}) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (task.hasDueDate) {
      if (index == -1) {
        data.tasks.add(task);
      } else {
        data.tasks.removeAt(index);
        data.tasks.insert(index, task);
      }
    } else if (index != -1){
      data.tasks.removeAt(index);
    }
    await set(data);
    if (expire) {
      this.expire();
    }

    notifyListeners();
  }

  /// Remove a task from this view if it exists.
  Future<void> removeTask(Task task) async {
    var data = await get();
    var index = data.tasks.indexWhere((item) => item.id == task.id);
    if (index > -1) {
      data.tasks.removeAt(index);
      await set(data);

      expire(notify: true);
    }
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
  Future<void> set(TaskViewData tasks) async {
    return _set(tasks.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await _get();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
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
  ///
  /// We store a per task expiration time
  /// in addition to the task data.
  @override
  Future<void> set(Task task) async {
     var current = await _get() ?? {};
     var taskId = task.id.toString();
     current[taskId] = task.toMap();
     current[taskId]['updatedAt'] = clock.now().toIso8601String();

     await _set(current);

     notifyListeners();
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

  /// Remove a task and notifyListeners.
  Future<void> remove(int id) async {
    var data = await _get() ?? {};
    var taskId = id.toString();

    data.remove(taskId);
    await _set(data);

    notifyListeners();
  }

  bool isTaskFresh(int id) {
    var state = _state;
    if (state == null) {
      return false;
    }
    if (duration == null) {
      return true;
    }
    var taskId = id.toString();
    var taskData = state['data'][taskId];
    if (taskData == null) {
      return false;
    }
    var updated = taskData["updatedAt"];
    // No updatedAt means we need to refresh.
    if (updated == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
  }

  void notify() {
    notifyListeners();
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

  // Decrement the incomplete task count for a project.
  Future<void> decrement(String slug) async {
    var data = await _get() ?? {};
    if (data[slug] == null) {
      return;
    }
    var project = Project.fromMap(data[slug]);
    project.incompleteTaskCount -= 1;

    return set(project);
  }

  // Increment the incomplete task count for a project.
  Future<void> increment(String slug) async {
    var data = await _get() ?? {};
    if (data[slug] == null) {
      return;
    }
    var project = Project.fromMap(data[slug]);
    project.incompleteTaskCount += 1;
    return set(project);
  }

  // Remove a project by slug.
  Future<void> remove(String slug) async {
    var data = await _get() ?? {};
    data.remove(slug);
    return _set(data);
  }

  // Remove a project by id.
  Future<void> removeById(int id) async {
    var data = await _get() ?? {};
    data.removeWhere((key, value) => value['id'] == id);
    return _set(data);
  }
}

// Store projects + task lists by slug.
// Data is stored by slug to make fetching consistent
// with API endpoints. Some operations search by id, and these
// changes run at O(n).
class ProjectDetailsView extends ViewCache<ProjectWithTasks> {
  static const String name = 'projectdetails';
  final Map<String, DateTime> _expired = {};

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
    _expired.remove(view.project.slug);

    return _set(current);
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
        _expired[previousProject] = clock.now();
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
    _expired[slug] = clock.now();
    if (notify) {
      notifyListeners();
    }
  }

  /// Check if the project of slug has been expired.
  bool isExpiredSlug(String? slug) {
    if (slug == null) {
      return false;
    }
    return _expired[slug] != null;
  }

  Future<ProjectWithTasks> get(String slug) async {
    var data = await _get();
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
    var data = await _get() ?? {};
    data.remove(slug);
    await _set(data);

    notifyListeners();
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
        isEmpty: true,
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

class CalendarProviderListCache extends ViewCache<List<CalendarProvider>> {
  static const String name = 'calendarproviderlist';

  CalendarProviderListCache(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(List<CalendarProvider> providers) async {
    return _set({"items": providers.map((p) => p.toMap()).toList()});
  }

  Future<List<CalendarProvider>?> get() async {
    var data = await _get();
    if (data == null) {
      return null;
    }
    var items = data['items'];
    if (items == null) {
      return null;
    }
    if (items.runtimeType != List && items.runtimeType != List<Map<String, Object?>>) {
      return null;
    }
    return (items as List).map<CalendarProvider>((item) => CalendarProvider.fromMap(item)).toList();
  }
}

class CalendarProviderDetailsCache extends ViewCache<CalendarProvider> {
  static const String name = 'calendarproviderdetails';

  CalendarProviderDetailsCache(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a provider to the lookup map
  @override
  Future<void> set(CalendarProvider provider) async {
    var data = await _get() ?? {};
    data[provider.id.toString()] = provider.toMap();

    return _set(data);
  }

  Future<CalendarProvider?> get(int id) async {
    var data = await _get();
    if (data == null) {
      return null;
    }
    var providerId = id.toString();
    if (data[providerId] == null) {
      return null;
    }
    return CalendarProvider.fromMap(data[providerId]);
  }
}
