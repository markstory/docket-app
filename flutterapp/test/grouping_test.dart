import 'package:flutter_test/flutter_test.dart';

import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
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
    sectionId: null,
    id: 1,
    subtasks: [],
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
    sectionId: null,
    dueOn: DateTime.now().add(const Duration(days: 30)),
    id: 2,
    subtasks: [],
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
    sectionId: 1,
    dueOn: DateTime.now().add(const Duration(days: 30)),
    id: 3,
    subtasks: [],
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
    sectionId: 2,
    dueOn: DateTime.now(),
    id: 3,
    subtasks: [],
  );

  group('grouping.groupTasksBySection()', () {
    var sectionOne = Section(id: 1, name: 'Shopping', ranking: 2);
    var sectionTwo = Section(id: 2, name: 'Repairs', ranking: 1);
    var tasks = [notDue, dueLast, dueLastAgain, dueFirstEvening];
    var sections = [sectionOne, sectionTwo];

    test('it groups tasks', () {
      var result = grouping.groupTasksBySection(sections, tasks);
      expect(result.length, equals(3));

      // Check the none group
      expect(result[0].section, isNull);
      var taskGroup = result[0].tasks;
      expect(taskGroup.length, equals(2));
      expect(taskGroup[0].id, equals(notDue.id));
      expect(taskGroup[1].id, equals(dueLast.id));

      // Check the section groups
      expect(result[1].section?.id, equals(sectionOne.id));
      taskGroup = result[1].tasks;
      expect(taskGroup.length, equals(1));

      expect(result[2].section?.id, equals(sectionTwo.id));
      taskGroup = result[2].tasks;
      expect(taskGroup.length, equals(1));
    });
  });
}
