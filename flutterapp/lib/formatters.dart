import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

var _monthDay = DateFormat('MMM d');
var _monthDayYear = DateFormat('MMM d yyyy');
var _weekday = DateFormat('EEEE');

/// Date formatter for use in task rows
/// or other locations where a short relative date
/// is required.
String compactDate(DateTime? value){
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
