import 'dart:async';
import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

import 'package:docket/formatters.dart' as formatters;

class ChangeDueOnResult {
  final bool evening;
  final DateTime? dueOn;

  const ChangeDueOnResult({required this.evening, required this.dueOn});
}

/// Dialog sheet for changing a task due on.
Future<ChangeDueOnResult> showChangeDueOnDialog(BuildContext context, DateTime? dueOn, bool evening) {
  var theme = Theme.of(context);
  var docketColors = theme.extension<DocketColors>()!;

  var completer = Completer<ChangeDueOnResult>();
  showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (BuildContext context) {
      final today = DateUtils.dateOnly(DateTime.now());
      final tomorrow = today.add(const Duration(days: 1));

      final isToday = dueOn == today && evening == false;
      final isTodayFriday = dueOn == today && today.weekday == DateTime.friday;
      final isWeekend = [6, 7].contains(today.weekday);
      final isThisEvening = dueOn == today && evening == true;
      final isTomorrow = dueOn == tomorrow;
      final isEvening = evening;
      final futureDue = dueOn != null && dueOn != today;
      final currentValue = DateUtils.dateOnly(dueOn ?? DateTime.now());

      // Today is friday, in 3 days it will be monday
      var monday = today.add(const Duration(days: 3));
      if (today.weekday > 1) {
        // Because dayNumber is 1-7, the upcoming monday would
        // be 8.
        monday = today.add(Duration(days: 8 - today.weekday));
      }

      List<Widget> items = [];
      if (!isToday) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.today, color: docketColors.dueToday),
            title: const Text('Today'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: today, evening: false));
              Navigator.of(context).pop();
            }));
      }

      if (!isTomorrow) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.light_mode_outlined, color: docketColors.dueTomorrow),
            title: const Text('Tomorrow'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: tomorrow, evening: evening));
              Navigator.of(context).pop();
            }));
      }

      if (!isThisEvening) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.bedtime_outlined, color: docketColors.dueEvening),
            title: const Text('This evening'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: today, evening: true));
              Navigator.of(context).pop();
            }));
      }

      if (futureDue && isEvening) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.calendar_today, color: docketColors.dueTomorrow),
            title: Text('${formatters.compactDate(currentValue)} day'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: currentValue, evening: false));
              Navigator.of(context).pop();
            }));
      }

      if (futureDue && !isEvening) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.calendar_today, color: docketColors.dueEvening),
            title: Text('${formatters.compactDate(currentValue)} evening'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: currentValue, evening: true));
              Navigator.of(context).pop();
            }));
      }

      if (isTodayFriday || isWeekend) {
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.date_range_outlined, color: docketColors.dueFortnight),
            title: const Text('On monday'),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: monday, evening: evening));
              Navigator.of(context).pop();
            }));
      }

      if (dueOn != null && !isToday && !isTodayFriday) {
        var next = dueOn.add(const Duration(days: 1));
        items.add(ListTile(
            dense: true,
            leading: Icon(Icons.date_range_outlined, color: docketColors.dueWeek),
            title: Text(formatters.compactDate(next)),
            onTap: () {
              completer.complete(ChangeDueOnResult(dueOn: next, evening: evening));
              Navigator.of(context).pop();
            }));
      }

      items.add(ListTile(
          dense: true,
          leading: Icon(Icons.watch_later_outlined, color: docketColors.dueNone),
          title: const Text('Later'),
          onTap: () {
            completer.complete(ChangeDueOnResult(dueOn: null, evening: evening));
            Navigator.of(context).pop();
          }));

      items.add(ListTile(
          dense: true,
          leading: Icon(Icons.calendar_today, color: docketColors.dueFortnight),
          title: const Text('Choose a day'),
          onTap: () async {
            var navigator = Navigator.of(context);
            final newValue = await showDatePicker(
              context: context,
              initialDate: currentValue,
              firstDate: today,
              lastDate: today.add(const Duration(days: 365)),
              helpText: 'Remind me on',
            );
            completer.complete(ChangeDueOnResult(dueOn: newValue, evening: evening));
            navigator.pop();
          }));

      return AlertDialog(
          title: const Text('Select a day'),
          content: SingleChildScrollView(
            child: ListBody(
              children: items,
            ),
          ));
    },
  );

  return completer.future;
}
