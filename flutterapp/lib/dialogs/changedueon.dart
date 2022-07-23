import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

import 'package:docket/formatters.dart' as formatters;

/// Dialog sheet for changing a task due on.
Future<void> showChangeDueOnDialog(
  BuildContext context,
  DateTime? dueOn,
  bool evening,
  Function(DateTime? newDueOn, bool newEvening) onChange 
) {
  var theme = Theme.of(context);
  var docketColors = theme.extension<DocketColors>()!;

  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (BuildContext context) {
      final today = DateUtils.dateOnly(DateTime.now());
      final tomorrow = today.add(const Duration(days: 1));

      final isToday = dueOn == today && evening == false;
      final isThisEvening = dueOn == today && evening == true;
      final isTomorrow = dueOn == tomorrow;
      final isEvening = evening;
      final futureDue = dueOn != null && dueOn != today;
      final currentValue = DateUtils.dateOnly(dueOn ?? DateTime.now());

      List<Widget> items = [];
      if (!isToday) {
        items.add(
          ListTile(
            dense: true,
            leading: Icon(Icons.today, color: docketColors.dueToday),
            title: const Text('Today'),
            onTap: () {
              var newValue = DateUtils.dateOnly(DateTime.now());
              onChange(newValue, false);
              Navigator.of(context).pop();
            }
          )
        );
      }

      if (!isTomorrow) {
        items.add(
          ListTile(
            dense: true,
            leading: Icon(Icons.light_mode_outlined, color: docketColors.dueTomorrow),
            title: const Text('Tomorrow'),
            onTap: () {
              var newValue = DateUtils.dateOnly(
                currentValue.add(const Duration(days: 1))
              );

              onChange(newValue, evening);
              Navigator.of(context).pop();
            }
          )
        );
      }

      if (!isThisEvening) {
        items.add(
          ListTile(
            dense: true,
            leading: Icon(Icons.bedtime_outlined, color: docketColors.dueEvening),
            title: const Text('This evening'),
            onTap: () {
              var newValue = DateUtils.dateOnly(DateTime.now());

              onChange(newValue, true);
              Navigator.of(context).pop();
            }
          )
        );
      }

      if (futureDue && isEvening) {
        items.add(
          ListTile(
            dense: true,
            leading: Icon(Icons.calendar_today, color: docketColors.dueTomorrow),
            title: Text('${formatters.compactDate(currentValue)} day'),
            onTap: () {
              onChange(currentValue, false);
              Navigator.of(context).pop();
            }
          )
        );
      }

      if (futureDue && !isEvening) {
        items.add(
          ListTile(
            dense: true,
            leading: Icon(Icons.bedtime_outlined, color: docketColors.dueTomorrow),
            title: Text('${formatters.compactDate(currentValue)} evening'),
            onTap: () {
              onChange(currentValue, true);
              Navigator.of(context).pop();
            }
          )
        );
      }

      items.add(
        ListTile(
          dense: true,
          leading: Icon(Icons.delete, color: docketColors.dueNone),
          title: const Text('No Due Date'),
          onTap: () {
            onChange(null, evening);
            Navigator.of(context).pop();
          }
        )
      );

      items.add(
        ListTile(
          dense: true,
          leading: Icon(Icons.calendar_today, color: docketColors.dueFortnight),
          title: const Text('Pick Date'),
          onTap: () async {
            var navigator = Navigator.of(context);
            final newValue = await showDatePicker(
              context: context,
              initialDate: currentValue,
              firstDate: today,
              lastDate: today.add(const Duration(days: 365)),
              helpText: 'Choose a Due Date',
            );
            onChange(newValue, evening);
            navigator.pop();
          }
        )
      );

      return AlertDialog(
        title: const Text('Set Date'),
        content: SingleChildScrollView(
          child: ListBody(
            children: items,
          ),
        )
      );
    },
  );
}
