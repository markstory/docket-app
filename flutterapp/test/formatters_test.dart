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

  group('formatters.paddedTime()', () {
    test('it formats', () {
      var today = DateTime.now();
      var result = formatters.paddedTime(today);
      var expected = DateFormat('Hm').format(today);
      expect(result, equals(expected));
    });
  });

  group('formatters.timeAgo()', () {
    test('formats values', () {
      var today = DateTime.now();
      var eightDays = today.subtract(const Duration(days: 8));
      expect(formatters.timeAgo(eightDays), equals('1 week ago'));

      var threeDays = today.subtract(const Duration(days: 3));
      expect(formatters.timeAgo(threeDays), equals('3 days ago'));

      var yesterday = today.subtract(const Duration(days: 1));
      expect(formatters.timeAgo(yesterday), equals('Yesterday'));

      var hoursAgo = today.subtract(const Duration(hours: 2));
      expect(formatters.timeAgo(hoursAgo), equals('2 hours ago'));

      var hourAgo = today.subtract(const Duration(hours: 1));
      expect(formatters.timeAgo(hourAgo), equals('An hour ago'));

      var minutesAgo = today.subtract(const Duration(minutes: 10));
      expect(formatters.timeAgo(minutesAgo), equals('10 minutes ago'));

      var minuteAgo = today.subtract(const Duration(minutes: 1));
      expect(formatters.timeAgo(minuteAgo), equals('A minute ago'));

      var secondsAgo = today.subtract(const Duration(seconds: 10));
      expect(formatters.timeAgo(secondsAgo), equals('10 seconds ago'));
    });
  });
}
