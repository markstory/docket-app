import 'dart:io';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/taskadd.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var today = DateUtils.dateOnly(DateTime.now());
  var file = File('test_resources/task_create_today.json');
  final taskDetails = file.readAsStringSync().replaceAll('__TODAY__', formatters.dateString(today));

  file = File('test_resources/project_list.json');
  final projectListResponse = file.readAsStringSync();
  var decoded = jsonDecode(projectListResponse);

  group('$TaskAddScreen', () {
    var db = LocalDatabase(inTest: true);
    var projects = (decoded['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectMap.addMany(projects);
    });

    testWidgets('saves task', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects') {
          return Response(projectListResponse, 200);
        }
        if (request.url.path == '/tasks/add') {
          callCount += 1;
          expect(request.body.contains("Rake leaves"), isTrue);
          return Response(taskDetails, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      const child = TaskAddScreen();
      await tester.pumpWidget(EntryPoint(
          database: db,
          child: child,
      ));
      await tester.runAsync(() async {
        await tester.pumpAndSettle();
      });

      await tester.enterText(find.byKey(const ValueKey('title')), "Rake leaves");

      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
