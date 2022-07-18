import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

import 'package:docket/components/dueon.dart';
import 'package:docket/formatters.dart' as formatters;

/// Form layout widget.
/// Includes a leading element that is expected to be ~18px wide
/// Generally an icon but can also be an interactive wiget like a checkbox.
class FormIconRow extends StatelessWidget {
  final Widget child;
  final Widget? icon;

  const FormIconRow({this.icon, required this.child, super.key});

  @override
  Widget build(BuildContext context) {
    late Widget iconWidget;
    if (icon != null) {
      iconWidget = Padding(padding: EdgeInsets.fromLTRB(0, space(1), space(2), 0), child: icon);
    } else {
      iconWidget = const SizedBox(width: 34);
    }

    return Container(
      padding: EdgeInsets.all(space(1)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          iconWidget,
          Expanded(child: child),
        ]
      )
    );
  }
}

/// Form widget for updating the dueOn attribute of a task.
class DueOnInput extends StatelessWidget {
  final DateTime? dueOn;
  final bool evening;

  final Function(DateTime? dueOn, bool evening) onUpdate;

  const DueOnInput({
    required this.onUpdate,
    required this.dueOn,
    required this.evening,
    super.key
  });

  Future<void> _showDialog(BuildContext context) {
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
                onUpdate(newValue, false);
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

                onUpdate(newValue, evening);
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

                onUpdate(newValue, true);
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
                onUpdate(currentValue, false);
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
                onUpdate(currentValue, true);
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
              onUpdate(null, evening);
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
              final navigator = Navigator.of(context);
              final newValue = await showDatePicker(
                context: context,
                initialDate: currentValue,
                firstDate: today,
                lastDate: today.add(const Duration(days: 365)),
                helpText: 'Choose a Due Date',
              );
              onUpdate(newValue, evening);
              navigator.pop();
            }
          )
        );

        return AlertDialog(
          title: const Text('Set Due Date'),
          content: SingleChildScrollView(
            child: ListBody(
              children: items,
            ),
          )
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return TextButton(
      child: DueOn(dueOn: dueOn, evening: evening, showDate: true, showNull: true),
      onPressed: () {
        _showDialog(context);
      }
    );
  }
}
