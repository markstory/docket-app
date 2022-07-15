import 'package:flutter/material.dart';

import 'package:docket/formatters.dart' as formatters;
import 'package:docket/theme.dart';

class TaskDue extends StatelessWidget {
  final DateTime? dueOn;
  final bool evening;
  final bool showNull;
  final bool showDate;

  const TaskDue({
    required this.dueOn,
    required this.evening,
    this.showNull = false,
    this.showDate = false,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;
    if (dueOn == null && showNull) {
      return Text('No due date', style: TextStyle(color: customColors.dueNone));
    }

    List<Widget> children = [];
    if (evening) {
      children.add(Icon(
        Icons.bedtime_outlined,
        color: customColors.dueEvening,
        size: 14,
      ));
    }
    if (showDate && dueOn != null) {
      var today = DateTime.now();
      var diff = dueOn!.difference(today).inDays;
      var color = customColors.dueToday;
      if (diff < 0) {
        color = customColors.dueOverdue;
      } else if (diff == 0 && evening == false) {
        color = customColors.dueToday;
      } else if (diff == 0 && evening) {
        color = customColors.dueEvening;
      } else if (diff >= 1 && diff < 2) {
        color = customColors.dueTomorrow;
      } else if (diff >= 2 && diff < 8) {
        color = customColors.dueWeek;
      } else if (diff >= 8 && diff < 15) {
        color = customColors.dueFortnight;
      }
      var text = formatters.compactDate(dueOn);
      children.add(Text(text, style: TextStyle(color: color)));
    }
    return Row(children: children);
  }
}
