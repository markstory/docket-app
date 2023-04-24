import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';

/// Module for task grouping logic.
/// This code is adapted from the javascript
/// code used in Tasks/index.tsx

class GroupedItem {
  String key;
  List<Task> items;
  List<String> ids;
  bool? hasAdd;

  GroupedItem({
    required this.key,
    required this.items,
    required this.ids,
    this.hasAdd,
  });
}

List<SectionWithTasks> groupTasksBySection(List<Section> sections, List<Task> tasks) {
  Map<int, List<Task>> sectionTable = {};
  for (var task in tasks) {
    var sectionId = task.sectionId ?? Section.root;
    if (!sectionTable.containsKey(sectionId)) {
      List<Task> group = [];
      sectionTable[sectionId] = group;
    }
    sectionTable[sectionId]?.add(task);
  }
  List<SectionWithTasks> output = [];
  if (sectionTable.containsKey(Section.root)) {
    output.add(SectionWithTasks(
      section: null,
      tasks: sectionTable[Section.root] ?? []
    ));
  }
  for (var section in sections) {
    output.add(SectionWithTasks(
        section: section,
        tasks: sectionTable[section.id] ?? [],
    ));
  }
  return output;
}
