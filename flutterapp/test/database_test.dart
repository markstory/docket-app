import 'package:clock/clock.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/models/project.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  var database = LocalDatabase.instance();
  var project = Project.blank();
  project.id = 1;
  project.slug = 'home';
  project.name = 'Home';

  group('database.LocalViewCache', () {
    test('data read when fresh', () async {
      await database.projectMap.set(project);
      var value = await database.projectMap.get('not-there');
      expect(value, isNull);

      value = await database.projectMap.get('home');
      expect(value, isNotNull);
      expect(value!.slug, equals('home'));
    });

    test('data read when stale', () async {
      await database.projectMap.set(project);
      expect(database.projectMap.isFresh(), isTrue, reason: 'Should be fresh');
      var expires = DateTime.now().add(const Duration(hours: 2));

      withClock(Clock.fixed(expires), () async {
        var value = await database.projectMap.get('home');
        expect(value, isNotNull, reason: 'stale reads are ok');
        expect(database.projectMap.isFresh(), isFalse, reason: 'no longer fresh.');
      });
    });

    test('session data has no expiration', () async {
      var token = ApiToken.fromMap({'token': 'abc123', 'lastUsed': null});
      await database.apiToken.set(token);

      var expires = DateTime.now().add(const Duration(hours: 2));
      withClock(Clock.fixed(expires), () async {
        var value = await database.apiToken.get();
        expect(value, isNotNull);
      });

      expires = DateTime.now().add(const Duration(days: 2));
      withClock(Clock.fixed(expires), () async {
        var value = await database.apiToken.get();
        expect(value, isNotNull);
      });
    });
  });
}
