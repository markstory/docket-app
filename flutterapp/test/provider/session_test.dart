import 'package:flutter_test/flutter_test.dart';

import 'package:flutterapp/provider/session.dart';

void main() {
  test('setting token', () {
    final model = SessionProvider();
    expect(model.apiToken, equals(null));

    model.set('abc123');
    expect(model.apiToken, equals('abc123'));
  });

  test('cleaing token', () {
    final model = SessionProvider();
    expect(model.apiToken, equals(null));

    model.set('abc123');
    expect(model.apiToken, equals('abc123'));

    model.clear();
    expect(model.apiToken, equals(null));
  });
}
