import 'dart:convert';
import 'dart:io';
import 'package:docket/models/project.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/dialogs/changeproject.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var database = LocalDatabase.instance();
  var file = File('test_resources/project_list.json');

  final projectList = file.readAsStringSync();
  var decoded = jsonDecode(projectList) as Map<String, dynamic>;

  Widget buildButton(int? current, Function(int id) onUpdate) {
    return EntryPoint(
        database: database,
        child: Builder(builder: (BuildContext context) {
          return TextButton(
              child: const Text('Open'),
              onPressed: () async {
                var result = await showChangeProjectDialog(context, current);
                onUpdate(result);
              });
        }));
  }

  group('showChangeProjectDialog', () {
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    setUp(() async {
      await database.projectMap.addMany(projects);
    });

    testWidgets('show options and select value', (tester) async {
      var callCount = 0;
      var updated = 0;
      void onUpdate(int projectid) {
        updated = projectid;
        callCount += 1;
      }

      await tester.pumpWidget(buildButton(null, onUpdate));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Home'));
      await tester.pumpAndSettle();
      expect(callCount, equals(1));
      expect(updated, equals(1));
    });
  });
}
