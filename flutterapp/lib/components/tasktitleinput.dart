import 'package:flutter/material.dart';
import 'package:flutter_mentions/flutter_mentions.dart';
import 'package:intl/intl.dart';

import 'package:docket/models/project.dart';
import 'package:docket/formatters.dart' as formatters;

typedef MentionData = Map<String, dynamic>;

final dateParser = DateFormat('yyyy-MM-dd');
final monthdayParser = DateFormat.MMMMd();
final weekdayParser = DateFormat.EEEE();

List<MentionData> generateMonth(String name, int end) {
  var today = DateUtils.dateOnly(DateTime.now());
  List<MentionData> options = [];
  for (var i = 1; i <= end; i++) {
    var value = "$name $i";
    var dateValue = monthdayParser.parse(value);
    if (dateValue.isBefore(today)) {
      dateValue = dateValue.add(const Duration(days: 365));
    }
    options.add({"id": "d:${formatters.dateString(dateValue)}", "display": value});
  }
  return options;
}

List<MentionData> generateDateOptions(DateTime today) {
  final tomorrow = today.add(const Duration(days: 1));
  final monday = weekdayParser.parse('Monday');
  final tuesday = weekdayParser.parse('Tuesday');
  final wednesday = weekdayParser.parse('Wednesday');
  final thursday = weekdayParser.parse('Thursday');
  final friday = weekdayParser.parse('Friday');
  final saturday = weekdayParser.parse('Saturday');
  final sunday = weekdayParser.parse('Sunday');

  return [
    {"id": 'r:${formatters.dateString(today)}', "display": 'Today'},
    {"id": 'r:${formatters.dateString(tomorrow)}', "display": 'Tomorrow'},
    {"id": 'r:${formatters.dateString(monday)}', "display": 'Monday'},
    {"id": 'r:${formatters.dateString(tuesday)}', "display": 'Tuesday'},
    {"id": 'r:${formatters.dateString(wednesday)}', "display": 'Wednesday'},
    {"id": 'r:${formatters.dateString(thursday)}', "display": 'Thursday'},
    {"id": 'r:${formatters.dateString(friday)}', "display": 'Friday'},
    {"id": 'r:${formatters.dateString(saturday)}', "display": 'Saturday'},
    {"id": 'r:${formatters.dateString(sunday)}', "display": 'Sunday'},
    ...generateMonth('January', 31),
    ...generateMonth('February', 28),
    ...generateMonth('March', 31),
    ...generateMonth('April', 30),
    ...generateMonth('May', 31),
    ...generateMonth('June', 30),
    ...generateMonth('July', 31),
    ...generateMonth('August', 31),
    ...generateMonth('September', 30),
    ...generateMonth('October', 31),
    ...generateMonth('November', 30),
    ...generateMonth('December', 31),
  ];
}

class TaskTitleInput extends StatelessWidget {
  final List<Project> projects;
  final Function(DateTime date, bool evening) onChangeDate;
  final Function(int projectId) onChangeProject;
  final Function(String text) onChangeTitle;
  final String value;

  const TaskTitleInput(
      {required this.projects,
      required this.onChangeDate,
      required this.onChangeProject,
      required this.onChangeTitle,
      required this.value,
      super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var today = DateUtils.dateOnly(DateTime.now());

    // TODO These lists might need memoization
    List<MentionData> projectOptions =
        projects.map((project) => ({'id': 'p:${project.id}', 'display': project.name})).toList();
    var dateOptions = generateDateOptions(today);
    var eveningDateOptions = dateOptions.map((item) {
      return {"id": "e${item['id']}", "display": item['display']};
    }).toList();

    // TODO this form should make sure title is not empty.
    return FlutterMentions(
        appendSpaceOnAdd: true,
        autofocus: true,
        enableInteractiveSelection: true,
        key: const ValueKey('title'),
        suggestionPosition: SuggestionPosition.Bottom,
        maxLines: 5,
        minLines: 1,
        defaultText: value,
        onChanged: (title) {
          // TODO this might need to strip out markup.
          onChangeTitle(title);
        },
        onMentionAdd: (item) {
          var parts = item['id'].toString().split(':');
          assert(parts.length == 2);
          var type = parts[0];
          var value = parts[1];

          switch (type) {
            case 'p':
              onChangeProject(int.parse(value));
              break;
            // relative dates
            case 'r':
            case 'er':
              var dateValue = dateParser.parse(value);
              onChangeDate(dateValue, type == 're');
              break;
            // absolute dates
            case 'd':
            case 'ed':
              var dateValue = dateParser.parse(value);
              onChangeDate(dateValue, type == 'de');
              break;
          }
        },
        mentions: [
          Mention(trigger: '#', style: TextStyle(backgroundColor: theme.colorScheme.surfaceTint), data: projectOptions),
          Mention(
            trigger: '%',
            style: TextStyle(backgroundColor: theme.colorScheme.surfaceTint),
            data: dateOptions,
          ),
          Mention(
            trigger: '&',
            style: TextStyle(backgroundColor: theme.colorScheme.surfaceTint),
            data: eveningDateOptions,
          )
        ]);
  }
}
