import 'package:flutter/material.dart';

import 'package:docket/models/calendaritem.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/task.dart';
import 'package:docket/formatters.dart' as formatters;

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

///
/// Fill out the sparse input data to have all the days.
///
List<GroupedItem> zeroFillItems(DateTime firstDate, int numDays, List<GroupedItem> groups) {
  firstDate = DateUtils.dateOnly(firstDate);
  var endDate = firstDate.add(Duration(days: numDays));

  List<GroupedItem> complete = [];
  var date = firstDate;
  var index = 0;
  while (true) {
    // Gone past the end.
    if (date.isAfter(endDate) || index >= groups.length) {
      break;
    }

    var dateKey = formatters.dateString(date);
    if (groups[index].key == dateKey) {
      complete.add(groups[index]);
      index++;
    } else {
      complete.add(GroupedItem(key: dateKey, items: [], ids: []));
    }

    // Could advance past the end of the list.
    if (index >= groups.length) {
      continue;
    }

    if (index <= groups.length && groups[index].key == 'evening:$dateKey') {
      complete.add(groups[index]);
      index++;
    }

    // Increment for next loop. We are using a while/break
    // because incrementing timestamps fails when DST happens.
    date = date.add(const Duration(days: 1));
  }
  return complete;
}
typedef GrouperCallback = List<GroupedItem> Function(List<Task>);

GrouperCallback createGrouper(DateTime start, int numDays) {
  List<GroupedItem> taskGrouper(List<Task> items) {
    Map<String, List<Task>> byDate = {};
    for (var task in items) {
      var key = task.dateKey;
      if (byDate[key] == null) {
        byDate[key] = [];
      }
      byDate[key]?.add(task);
    }
    List<GroupedItem> grouped = [];
    for (var entry in byDate.entries) {
      grouped.add(GroupedItem(
        key: entry.key,
        items: entry.value,
        ids: entry.value.map((task) => task.id.toString()).toList(),
      ));
    }
    return zeroFillItems(start, numDays, grouped);
  }

  final function = taskGrouper;

  return function;
}

/// Container for calendar items grouped by date.
// TODO why is this not just Map<String, List<CalendarItem>>?
class GroupedCalendarItems {
  Map<String, List<CalendarItem>> groupings = {};

  GroupedCalendarItems();

  void addItem(CalendarItem item) {
    var dateKeys = item.dateKeys();
    for (var key in dateKeys) {
      if (groupings[key] == null) {
        List<CalendarItem> group = [];
        groupings[key] = group;
      }
      groupings[key]!.add(item);
    }
  }

  List<CalendarItem> get(String dateKey) {
    return groupings[dateKey] ?? [];
  }
}

/// Group a list of calendar items by date.
/// Used to render a list of dates that may or may not contain
/// calendar items.
GroupedCalendarItems groupCalendarItems(List<CalendarItem> items) {
  var grouped = GroupedCalendarItems();
  for (var item in items) {
    grouped.addItem(item);
  }

  return grouped;
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
