import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart';
import 'package:http/testing.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('actions', () {
    setUp(() {
      actions.resetClient();
    });

    // Test actions that don't have coverage via viewmodels.
    test('updateTimezone()', () async {
      var counter = CallCounter();
      actions.client = MockClient((request) async {
        if (request.url.path == '/users/profile') {
          counter.call();
          expect(request.body, contains('timezone'));

          return Response('', 200);
        }
        throw "Unexpected request to ${request.url.path}";
      });

      await actions.updateTimezone('abc123');
      expect(counter.callCount, equals(1));
    });
  });

  group('ValidationError', () {
    test('fromResponseBody() decodes error key', () {
      var payload = utf8.encode('{"error":"Something bad"}');
      var error = actions.ValidationError.fromResponseBody('failure message', payload);

      expect(error.message, equals('failure message'));
      expect(error.errors.length, equals(1));
      expect(error.errors[0], equals('Something bad'));
    });

    test('fromResponseBody() decodes errors list', () {
      var payload = utf8.encode('{"errors":["Something bad", "Another one"]}');
      var error = actions.ValidationError.fromResponseBody('failure message', payload);

      expect(error.message, equals('failure message'));
      expect(error.errors.length, equals(2));
      expect(error.errors[0], equals('Something bad'));
      expect(error.errors[1], equals('Another one'));
    });

    test('fromResponseBody() decodes errors map', () {
      var payload = utf8.encode('{"errors": {"title": "Something bad", "body": "Another one"}}');
      var error = actions.ValidationError.fromResponseBody('failure message', payload);

      expect(error.message, equals('failure message'));
      expect(error.errors.length, equals(2));
      expect(error.errors[0], equals('title: Something bad'));
      expect(error.errors[1], equals('body: Another one'));
    });

    test('fromResponseBody() handles empty string', () {
      var payload = utf8.encode('');
      var error = actions.ValidationError.fromResponseBody('failure message', payload);

      expect(error.message, equals('failure message'));
      expect(error.errors.length, equals(0));
    });
  });
}
