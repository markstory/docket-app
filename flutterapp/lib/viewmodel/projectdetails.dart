import 'package:flutter/material.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/components/tasksorter.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/grouping.dart' as grouping;

class ProjectDetailsViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Task list for the day/evening
  List<TaskSortMetadata> _taskLists = [];

  Project? _project;
  String? _slug;

  ProjectDetailsViewModel(LocalDatabase database, this.session) {
    _database = database;
    _taskLists = [];

    _database.projectDetails.addListener(() async {
      refresh();
    });
  }

  Project get project {
    var p = _project;
    if (p == null) {
      throw Exception('Cannot access project it has not been set');
    }
    return p;
  }

  String get slug {
    var s = _slug;
    if (s == null) {
      throw Exception('Cannot access slug it has not been set');
    }
    return s;
  }

  bool get loading => _loading;
  List<TaskSortMetadata> get taskLists => _taskLists;

  setSession(SessionProvider value) {
    session = value;
  }

  setSlug(String slug) {
    _slug = slug;
    fetchProject();

    return this;
  }

  Future<void> fetchProject() async {
    _loading = true;
    var projectData = await _database.projectDetails.get(slug);
    if (!projectData.isEmpty) {
      _project = projectData.project;
      _buildTaskLists(projectData.tasks);
    }
    _loading = false;

    notifyListeners();
  }

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchProject();

    if (!_loading && _project == null) {
      await refresh();
    }
  }

  /// Refresh from the server.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchProjectBySlug(session!.apiToken, slug);

    _project = result.project;
    await _database.projectDetails.set(result);

    _buildTaskLists(result.tasks);
  }

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
    await actions.moveSection(session!.apiToken, project, section, newIndex);
    section.ranking = newIndex;
    await _database.projectDetails.remove(project.slug);

    notifyListeners();
  }

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
    var updates = _taskLists[listIndex].onReceive(task, itemIndex);
    _taskLists[listIndex].tasks.insert(itemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(session!.apiToken, task, updates);
    await refresh();

    notifyListeners();
  }

  /// Re-order a task
  Future<void> reorderTask(int oldItemIndex, int oldListIndex, int newItemIndex, int newListIndex) async {
    var task = _taskLists[oldListIndex].tasks[oldItemIndex];

    // Get the changes that need to be made on the server.
    var updates = _taskLists[newListIndex].onReceive(task, newItemIndex);

    // Update local state assuming server will be ok.
    _taskLists[oldListIndex].tasks.removeAt(oldItemIndex);
    _taskLists[newListIndex].tasks.insert(newItemIndex, task);

    // Update the moved task and reload from server async
    await actions.moveTask(session!.apiToken, task, updates);
    await refresh();

    notifyListeners();
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
            onReceive: (Task task, int newIndex) {
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
            onReceive: (Task task, int newIndex) {
              task.childOrder = newIndex;
              task.sectionId = group.section?.id;
              return {'child_order': newIndex, 'section_id': task.sectionId};
            });
      }
      _taskLists.add(metadata);
    }

    _loading = false;
    notifyListeners();
  }
}
