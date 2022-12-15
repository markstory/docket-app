import 'dart:convert';
import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/project.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/dialogs/renamesection.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var database = LocalDatabase.instance();
  var file = File('test_resources/project_details.json');

  final projectDetails = file.readAsStringSync();
  var decoded = jsonDecode(projectDetails) as Map<String, dynamic>;

  Widget buildButton(Project project) {
    return EntryPoint(
        database: database,
        child: Builder(builder: (BuildContext context) {
          return TextButton(
              child: const Text('Open'),
              onPressed: () async {
                await showRenameSectionDialog(context, project, project.sections[0]);
              });
        }));
  }

  group('showRenameSectionDialog', () {
    var project = ProjectWithTasks.fromMap(decoded);

    setUp(() async {
      await database.apiToken.set(ApiToken(token: 'abc123'));
      await database.projectDetails.set(project);
    });

    testWidgets('fill form and send request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/projects/home/sections/1/edit') {
          expect(request.body.contains('Renamed'), isTrue);
          callCount += 1;
          return Response('', 200);
        }
        if (request.url.path == '/projects/home') {
          return Response(projectDetails, 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await tester.pumpWidget(buildButton(project.project));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('section-name')), 'Renamed');
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
