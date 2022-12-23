import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/providers/session.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late SessionProvider provider;
  late ApiToken token;
  var dbhandler = LocalDatabase.instance();

  group('$SessionProvider', () {
    setUp(() {
      provider = SessionProvider(dbhandler, token: 'old-value');
      token = ApiToken.fromMap({'token': 'abc123', 'lastUsed': null});
    });

    test('saveToken()', () async {
      var counter = CallCounter();
      provider.addListener(counter);

      await provider.saveToken(token);
      expect(provider.apiToken, equals('abc123'));
      expect(counter.callCount, equals(1));
    });

    test('clearing token', () async {
      await provider.saveToken(token);
      expect(provider.apiToken, equals('abc123'));

      provider.clear();
      expect(provider.hasToken, isFalse);
      expect(() => provider.apiToken, throwsA(isA<Exception>()));
    });
  });
}
