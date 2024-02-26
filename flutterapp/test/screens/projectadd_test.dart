import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/screens/projectadd.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_details.json');
  final projectDetails = file.readAsStringSync();

  group('$ProjectAddScreen', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
    });

    testWidgets('set name and send request', (tester) async {
      var callCount = 0;
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/projects/add') {
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
          child: ProjectAddScreen(),
      ));
      await tester.pumpAndSettle();

      await tester.enterText(find.byKey(const ValueKey('project-name')), "New name");
      await tester.tap(find.text('Save'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });
  });
}
