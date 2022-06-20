import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

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
    var formatter = DateFormat('MMM d yyyy');
    return formatter.format(value);
  }
  if (delta < 0) {
    var formatter = DateFormat('MMM d');
    return formatter.format(value);
  }
  if (delta < 1) {
    return 'Today';
  } else if (delta < 2) {
    return 'Tomorrow';
  }
  if (delta < 7) {
    var formatter = DateFormat('EEEE');
    return formatter.format(value);
  }
  var formatter = DateFormat('MMM d');
  return formatter.format(value);
}
