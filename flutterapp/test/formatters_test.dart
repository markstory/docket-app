import 'package:intl/intl.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/formatters.dart' as formatters;


void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('formatters.compactDate()', () {
    test('it handles null', () {
      expect(formatters.compactDate(null), equals(''));
    });

    test('it handles past dates', () {
      var date = DateTime.now().subtract(const Duration(days: 91));
      var result = formatters.compactDate(date);
      var expected = DateFormat('MMM d yyyy').format(date);
      expect(result, equals(expected));
    });

    test('it handles today', () {
      var date = DateTime.now();
      var result = formatters.compactDate(date);
      expect(result, equals('Today'));
    });

    test('it handles tomorrow', () {
      var date = DateTime.now().add(const Duration(days: 1));
      var result = formatters.compactDate(date);
      expect(result, equals('Tomorrow'));
    });

    test('it handles this week', () {
      var date = DateTime.now().add(const Duration(days: 3));
      var expected = {
        1: 'Monday',
        2: 'Tuesday',
        3: 'Wednesday',
        4: 'Thursday',
        5: 'Friday',
        6: 'Saturday',
        7: 'Sunday',
      };
      var result = formatters.compactDate(date);
      expect(result, equals(expected[date.weekday]));
    });

    test('it handles more than a week away', () {
      var date = DateTime.now().add(const Duration(days: 8));
      var result = formatters.compactDate(date);
      var expected = DateFormat('MMM d').format(date);
      expect(result, equals(expected));
    });
  });
}
