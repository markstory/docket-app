import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/models/apitoken.dart';
import 'package:docket/providers/session.dart';

void main() {
  late SessionProvider provider;
  late ApiToken token;

  setUp(() async {
    var dbhandler = LocalDatabase();
    provider = SessionProvider(dbhandler);
    token = ApiToken.fromMap({
      'token': 'abc123', 'lastUsed':null
    });
  });
  
  test('setting token', () {
    expect(provider.apiToken, equals(null));

    provider.set(token);
    expect(provider.apiToken, equals('abc123'));
  });

  test('cleaing token', () {
    expect(provider.apiToken, equals(null));

    provider.set(token);
    expect(provider.apiToken, equals('abc123'));

    provider.clear();
    expect(provider.apiToken, equals(null));
  });
}
