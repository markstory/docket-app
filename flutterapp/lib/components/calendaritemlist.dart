import 'package:flutter/material.dart';

import 'package:docket/formatters.dart';
import 'package:docket/models/calendaritem.dart';
import 'package:docket/theme.dart';

class CalendarItemList extends StatelessWidget {
  final List<CalendarItem> calendarItems;

  const CalendarItemList({required this.calendarItems, super.key});

  @override
  Widget build(BuildContext context) {
    if (calendarItems.isEmpty) {
      return const SizedBox();
    }
    var padding = EdgeInsets.all(space(1));

    return Container(
        padding: padding,
        child: Material(
            elevation: 1,
            borderRadius: DocketColors.borderRadius,
            child: Container(
                padding: padding,
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.start,
                  children: calendarItems.map((item) => CalendarItemTile(calendarItem: item)).toList(),
                ))));
  }
}

class CalendarItemTile extends StatelessWidget {
  final CalendarItem calendarItem;

  const CalendarItemTile({required this.calendarItem, super.key});

  @override
  Widget build(BuildContext context) {
    var color = getProjectColor(calendarItem.color);
    // Block element for all day events
    Widget timeWidget = Container(
      width: space(1),
      height: space(1.5),
      decoration: BoxDecoration(color: color, borderRadius: DocketColors.borderRadius),
    );

    // Time based events show up on the start time.
    var startTime = calendarItem.startTime;
    if (startTime != null) {
      timeWidget = Text(
        paddedTime(startTime),
        style: TextStyle(color: color),
      );
    }

    return Padding(
        padding: EdgeInsets.symmetric(vertical: space(1), horizontal: space(0.5)),
        child: Row(children: [
          timeWidget,
          SizedBox(width: space(1)),
          Expanded(child: Text(calendarItem.title, overflow: TextOverflow.ellipsis)),
        ]));
  }
}
