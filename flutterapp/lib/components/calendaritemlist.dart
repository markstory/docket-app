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
    return Material(
        elevation: 1,
        child: Container(
            padding: EdgeInsets.all(space(1)),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.start,
              children: calendarItems.map((item) => CalendarItemTile(calendarItem: item)).toList(),
            )));
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
      color: color,
      width: space(1),
      height: space(2.5),
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
