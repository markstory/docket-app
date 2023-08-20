import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/grouping.dart' as grouping;

class ProjectDetailsViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;
  bool _silentLoading = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  Project? _project;
  String? _slug;

  ProjectDetailsViewModel(LocalDatabase database) {
    _taskLists = [];

    _database = database;
    _database.projectDetails.addListener(listener);
  }

  @override
  void dispose() {
    _database.projectDetails.removeListener(listener);
    super.dispose();
  }

  void listener() {
    loadData();
  }

  Project get project {
    var p = _project;
    assert(p != null, 'Cannot access project it has not been set');

    return p!;
  }

  String get slug {
    var s = _slug;
    assert(s != null, 'Cannot access slug it has not been set.');

    return s!;
  }

  bool get loading => _loading && !_silentLoading;
  List<TaskSortMetadata> get taskLists => _taskLists;

  setSlug(String slug) {
    _slug = slug;

    return this;
  }

  Future<void> fetchProject() async {
    _loading = true;
    var projectData = await _database.projectDetails.get(slug);
    if (!projectData.isEmpty) {
      _project = projectData.project;
      _buildTaskLists(projectData.tasks);
      return;
    }

    _loading = false;
    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    if (_slug == null) {
      return;
    }
    await fetchProject();

    if (!_loading && (_project == null || project.slug != slug)) {
      return refresh();
    }
    if (!_loading && !_database.projectDetails.isFreshSlug(slug)) {
      await silentRefresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchProjectBySlug(_database.apiToken.token, slug);

    _project = result.project;
    await _database.projectDetails.set(result);
    await _database.projectMap.set(result.project);

    _buildTaskLists(result.tasks);
  }

  Future<void> silentRefresh() async {
    _loading = _silentLoading = true;

    var result = await actions.fetchProjectBySlug(_database.apiToken.token, slug);

    _project = result.project;
    await _database.projectDetails.set(result);

    _buildTaskLists(result.tasks);
  }

  /// Archive a project and remove the project the project and project details.
  Future<Project> archive() async {
    await actions.archiveProject(_database.apiToken.token, project);
    await Future.wait([
      _database.projectMap.remove(project.slug),
      _database.projectDetails.remove(project.slug),
      _database.projectArchive.clear(),
    ]);
    notifyListeners();

    return project;
  }


  // Section Methods {{{
  /// Move a section up or down.
  Future<void> moveSection(int oldIndex, int newIndex) async {
    // Reduce by one as the 0th section is 'root'
    // which is not a proper section on the server.
    newIndex -= 1;
    var metadata = _taskLists[oldIndex];
    _taskLists.removeAt(oldIndex);
    _taskLists.insert(newIndex, metadata);

    var section = metadata.data;
    if (section == null) {
      return;
    }
    section.ranking = newIndex;
    await actions.moveSection(_database.apiToken.token, project, section, newIndex);
    _database.projectDetails.expireSlug(project.slug);

    notifyListeners();
  }

  // Remove a section and clear the project details view cache
  Future<void> createSection(Section section) async {
    // TODO improve server API so that we don't need to refresh the project.
    await actions.createSection(_database.apiToken.token, project, section);
    await refresh();

    notifyListeners();
  }

  // Remove a section and clear the project details view cache
  Future<void> deleteSection(Section section) async {
    // TODO improve server API so that we don't need to refresh the project.
    await actions.deleteSection(_database.apiToken.token, project, section);
    await refresh();

    notifyListeners();
  }

  /// Read a project from the local database by slug.
  Future<void> updateSection(Section section) async {
    // TODO improve server API so that we don't need to refresh the project.
    await actions.updateSection(_database.apiToken.token, project, section);
    await refresh();

    notifyListeners();
  }
  // }}}

  /// Move a task out of overdue into another section
  Future<void> moveInto(Task task, int listIndex, int itemIndex) async {
    assert(_taskLists.isNotEmpty);

    // Calculate position of adding to a end.
    // Generally this will be zero but it is possible to add to the
    // bottom of a populated list too.
    var targetList = _taskLists[listIndex];
    if (itemIndex == -1) {
      itemIndex = targetList.tasks.length;
    }

    // Get the changes that need to be made on the server.
    var sortMeta = _taskLists[listIndex] as TaskSortMetadata<Section>;
    var updates = sortMeta.onReceive(task, itemIndex, sortMeta);
    _taskLists[listIndex].tasks.insert(itemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    _database.expireTask(task);

    notifyListeners();
  }

  /// Re-order a task
  Future<void> reorderTask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    var task = _taskLists[oldListIndex].tasks[oldItemIndex];

    // Get the changes that need to be made on the server.
    var sortMeta = _taskLists[newListIndex] as TaskSortMetadata<Section>;
    var updates = sortMeta.onReceive(task, newItemIndex, sortMeta);

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(_database.apiToken.token, task, updates);
    _database.expireTask(task);
  }

  void _buildTaskLists(List<Task> tasks) {
    _taskLists = [];
    var grouped = grouping.groupTasksBySection(project.sections, tasks);
    for (var group in grouped) {
      late TaskSortMetadata<Section> metadata;
      if (group.section == null) {
        metadata = TaskSortMetadata(
            title: group.section?.name ?? '',
            tasks: group.tasks,
            onReceive: (task, newIndex, meta) {
              task.childOrder = newIndex;
              task.sectionId = null;
              return {'child_order': newIndex, 'section_id': null};
            });
      } else {
        metadata = TaskSortMetadata(
            canDrag: true,
            title: group.section?.name ?? '',
            tasks: group.tasks,
            data: group.section,
            onReceive: (task, newIndex, meta) {
              task.childOrder = newIndex;
              task.sectionId = group.section?.id;
              return {'child_order': newIndex, 'section_id': task.sectionId};
            });
      }
      _taskLists.add(metadata);
    }

    _loading = _silentLoading = false;
    notifyListeners();
  }
}
