import 'package:flutter_test/flutter_test.dart';

import 'package:docket/models/task.dart';
import 'package:docket/grouping.dart' as grouping;
import 'package:docket/formatters.dart' as formatters;

void main() {
  var notDue = Task(
    title: 'first task',
    body: '',
    evening: false,
    dayOrder: 0,
    childOrder: 0,
    completed: false,
    projectSlug: 'home',
    projectName: 'home',
    projectColor: 1,
    projectId: 1,
    id: 1,
  );

  var dueLast = Task(
    title: 'celebrate',
    body: '',
    evening: false,
    dayOrder: 0,
    childOrder: 1,
    completed: false,
    projectSlug: 'home',
    projectName: 'home',
    projectColor: 1,
    projectId: 1,
    dueOn: DateTime.now().add(const Duration(days: 30)),
    id: 2,
  );

  var dueLastAgain = Task(
    title: 'last?',
    body: '',
    evening: false,
    dayOrder: 1,
    childOrder: 2,
    completed: false,
    projectSlug: 'home',
    projectName: 'home',
    projectColor: 1,
    projectId: 1,
    dueOn: DateTime.now().add(const Duration(days: 30)),
    id: 3,
  );

  var dueFirstEvening = Task(
    title: 'first',
    body: '',
    evening: true,
    dayOrder: 0,
    childOrder: 3,
    completed: false,
    projectSlug: 'home',
    projectName: 'home',
    projectColor: 1,
    projectId: 1,
    dueOn: DateTime.now(),
    id: 3,
  );

  var dueFirst = Task(
    title: 'celebrate',
    body: '',
    evening: false,
    dayOrder: 1,
    childOrder: 2,
    completed: false,
    projectSlug: 'home',
    projectName: 'home',
    projectColor: 1,
    projectId: 1,
    dueOn: DateTime.now(),
    id: 4,
  );

  group('grouping.createGrouper()', () {
    test('It can group for a single day', () {
      var tasks = [dueLast, dueLastAgain];
      var start = DateTime.now().add(const Duration(days: 25));
      var grouper = grouping.createGrouper(start, 5);
      List<grouping.GroupedItem> grouped = grouper(tasks);

      expect(grouped.length, equals(6));
      expect(grouped[0].key, equals(formatters.dateString(start)));
      expect(grouped[0].items.length, equals(0));
      expect(
        grouped[1].key,
        equals(formatters.dateString(start.add(const Duration(days: 1))))
      );
      expect(grouped[1].items.length, equals(0));

      expect(grouped[5].key, equals(formatters.dateString(dueLast.dueOn!)));
      expect(grouped[5].items.length, equals(2));
      expect(grouped[5].items[0].id, equals(dueLast.id));
      expect(grouped[5].items[1].id, equals(dueLastAgain.id));
    });

    test('It ignores tasks with no due date', () {
      var tasks = [dueFirst, notDue];
      var start = DateTime.now().subtract(const Duration(days: 1));
      var grouper = grouping.createGrouper(start, 2);
      List<grouping.GroupedItem> grouped = grouper(tasks);

      expect(grouped.length, 3);
      expect(grouped[1].key, equals(dueFirst.dateKey));
    });

    test('It can group tasks due in the evening', () {
      var tasks = [dueFirst, dueFirstEvening];
      var start = DateTime.now().subtract(const Duration(days: 1));
      var grouper = grouping.createGrouper(start, 2);
      List<grouping.GroupedItem> grouped = grouper(tasks);

      // More because of evening.
      expect(grouped.length, equals(3));
      expect(grouped[0].key, equals(formatters.dateString(start)));
      expect(grouped[0].items.length, equals(0));

      expect(grouped[1].key, equals(formatters.dateString(dueFirst.dueOn!)));
      expect(grouped[1].items.length, equals(1));

      expect(grouped[2].key, contains('evening:'));
      expect(grouped[2].items.length, equals(1));
    });
  });
}
