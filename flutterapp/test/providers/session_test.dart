import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/providers/session.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late SessionProvider provider;
  late ApiToken token;
  int listenerCallCount = 0;

  group('$SessionProvider', () {
    setUp(() {
      var dbhandler = LocalDatabase();
      listenerCallCount = 0;
      provider = SessionProvider(dbhandler)
          ..addListener(() {
            listenerCallCount += 1;
          });
      token = ApiToken.fromMap({
        'token': 'abc123', 'lastUsed':null
      });
    });

    test('setting token', () async {
      expect(() => provider.apiToken, throwsA(isA<Exception>()));

      await provider.set(token);
      expect(provider.apiToken, equals('abc123'));
      expect(listenerCallCount, greaterThan(0));
    });

    test('clearing token', () async {
      expect(() => provider.apiToken, throwsA(isA<Exception>()));

      await provider.set(token);
      expect(provider.apiToken, equals('abc123'));

      provider.clear();
      expect(() => provider.apiToken, throwsA(isA<Exception>()));
    });
  });
}
