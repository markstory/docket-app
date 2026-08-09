import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/theme.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('theme.DocketColors', () {
    // These tests aren't meant to be exhaustive.
    // We're just aiming to get coverage, as the application doesn't
    // actually use these methods but flutter requires them to be implemented.
    test('copyWith()', () {
      var light = DocketColors.light;
      var updated = light.copyWith(actionEdit: Colors.purple);

      expect(light.actionEdit, isNot(equals(Colors.purple)));
      expect(updated.actionEdit, equals(Colors.purple));
    });

    test('lerp()', () {
      var light = DocketColors.light;
      var dark = DocketColors.dark;
      var updated = light.lerp(dark, 0.0);

      expect(updated.disabledText, equals(light.disabledText));
    });

    test('getProjectColor()', () {
      var res = getProjectColor(0);
      expect(res, equals(const Color(0xFF28aa48)));

      res = getProjectColor(1);
      expect(res, equals(const Color(0xFF6fd19d)));

      // no out of bounds
      res = getProjectColor(99);
      expect(res, equals(const Color(0xFF28aa48)));
    });

    test('getProjectTextColor()', () {
      var res = getProjectTextColor(1);
      expect(res, equals(DocketColors.black));

      res = getProjectTextColor(2);
      expect(res, equals(DocketColors.white));
    });
  });
}
