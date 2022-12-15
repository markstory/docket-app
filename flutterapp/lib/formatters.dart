import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

var _calendarTime = DateFormat('Hm');
var _monthDay = DateFormat('MMM d');
var _monthDayYear = DateFormat('MMM d yyyy');
var _weekday = DateFormat('EEEE');

/// Date formatter for use in task rows
/// or other locations where a short relative date
/// is required.
String compactDate(DateTime? value) {
  if (value == null) {
    return '';
  }
  var today = DateUtils.dateOnly(DateTime.now());
  var delta = DateUtils.dateOnly(value).difference(today).inDays;

  // In the past? Show the date.
  if (delta < -90) {
    return _monthDayYear.format(value);
  }
  if (delta < 0) {
    return _monthDay.format(value);
  }
  if (delta < 1) {
    return 'Today';
  } else if (delta < 2) {
    return 'Tomorrow';
  }
  if (delta < 7) {
    return _weekday.format(value);
  }
  return _monthDay.format(value);
}

/// Parse a string into a local DateTime.
/// Server data is generally in UTC but
/// local time is easier for formatting.
/// Not really a formatter, but meh.
DateTime parseToLocal(String input) {
  var parsed = DateTime.parse(input);
  if (parsed.isUtc) {
    return parsed.toLocal();
  }
  return parsed;
}


String dateString(DateTime value) {
  var month = value.month.toString();
  if (value.month < 10) {
    month = '0$month';
  }
  var day = value.day.toString();
  if (value.day < 10) {
    day = '0$day';
  }
  return '${value.year}-$month-$day';
}

String monthDay(DateTime value) {
  return _monthDay.format(value);
}

String paddedTime(DateTime value) {
  return _calendarTime.format(value);
}

String timeAgo(DateTime value) {
  final now = DateTime.now();
  final difference = now.difference(value);

  if ((difference.inDays / 7).floor() >= 1) {
    return '1 week ago';
  } else if (difference.inDays >= 2) {
    return '${difference.inDays} days ago';
  } else if (difference.inDays >= 1) {
    return 'Yesterday';
  } else if (difference.inHours >= 2) {
    return '${difference.inHours} hours ago';
  } else if (difference.inHours >= 1) {
    return 'An hour ago';
  } else if (difference.inMinutes >= 2) {
    return '${difference.inMinutes} minutes ago';
  } else if (difference.inMinutes >= 1) {
    return 'A minute ago';
  } else if (difference.inSeconds >= 3) {
    return '${difference.inSeconds} seconds ago';
  } else {
    return 'Just now';
  }
}
