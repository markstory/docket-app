import 'dart:io';
import 'package:docket/models/apitoken.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/viewmodels/projectarchive.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var file = File('test_resources/project_list.json');
  final projectListResponse = file.readAsStringSync();

  group('$ProjectArchiveViewModel', () {
    var db = LocalDatabase(inTest: true);

    setUp(() async {
      await db.apiToken.set(ApiToken.fake());
      await db.projectArchive.clear();
    });

    test('loadData() refreshes from server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/projects/archived') {
          return Response(projectListResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectArchiveViewModel(db);
      expect(viewmodel.projects.length, equals(0));

      await viewmodel.loadData();
      expect(viewmodel.projects.length, equals(2));
    });

    test('refresh() loads data from the server', () async {
      actions.client = MockClient((request) async {
        if (request.url.path == '/api/projects/archived') {
          return Response(projectListResponse, 200);
        }
        throw "Unexpected request to ${request.url.path} ${request.url.query}";
      });

      var viewmodel = ProjectArchiveViewModel(db);
      expect(viewmodel.projects.length, equals(0));

      await viewmodel.refresh();
      expect(viewmodel.projects.length, equals(2));
    });
  });
}
