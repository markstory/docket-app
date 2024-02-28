import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/models/project.dart';
import 'package:docket/screens/projectedit.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_details.json');
  final projectDetails = file.readAsStringSync();
  var decoded = jsonDecode(projectDetails) as Map<String, dynamic>;

  group('$ProjectEditScreen', () {
    var db = LocalDatabase(inTest: true);
    var viewdata = ProjectWithTasks.fromMap(decoded);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectDetails.set(viewdata);
    });

    testWidgets('edit project and send request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/projects/home/edit') {
          expect(request.body.contains('New name'), isTrue);
          callCount += 1;
          return Response(projectDetails, 200);
        }
        if (request.url.path == '/api/projects/home') {
          return Response(projectDetails, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await tester.pumpWidget(EntryPoint(
          database: db,
          child: ProjectEditScreen(viewdata.project),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('project-name')), "New name");
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    // TODO should have a test for slug redirects but I'm running into
    // async tasks that aren't finishing.
  });
}
