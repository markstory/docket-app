import 'package:docket/formatters.dart' as formatters;

class CalendarItem {
  String id;
  int? calendarSourceId;
  String? providerId;
  String title;
  int color;
  DateTime? startTime;
  DateTime? endTime;
  DateTime? startDate;
  DateTime? endDate;
  bool allDay;
  String htmlLink;

  CalendarItem({
    this.id = '',
    this.calendarSourceId,
    this.providerId,
    this.title = '',
    this.color = 0,
    this.startTime,
    this.endTime,
    this.startDate,
    this.endDate,
    this.allDay = false,
    this.htmlLink = '',
  }); 

  factory CalendarItem.fromMap(Map<String, dynamic> json) {
    DateTime? startTime;
    DateTime? endTime;
    DateTime? startDate;
    DateTime? endDate;

    if (json['start_time'] != null) {
      startTime = formatters.parseToLocal(json['start_time']);
    }
    if (json['start_time'] != null) {
      endTime = formatters.parseToLocal(json['start_time']);
    }
    if (json['start_date'] != null) {
      startDate = formatters.parseToLocal(json['start_date']);
    }
    if (json['end_date'] != null) {
      endDate = formatters.parseToLocal(json['end_date']);
    }

    return CalendarItem(
      id: json['id'].toString(),
      calendarSourceId: json['calendar_source_id'],
      providerId: json['provider_id'],
      title: json['title'] ?? '',
      color: json['color'] ?? 0,
      startTime: startTime,
      endTime: endTime,
      startDate: startDate,
      endDate: endDate,
      allDay: json['all_day'] ?? false,
      htmlLink: json['html_link'] ?? '',
    );
  }

  CalendarItem copy({
    String? id,
    int? calendarSourceId,
    String? providerId,
    String? title,
    int? color,
    DateTime? startTime,
    DateTime? endTime,
    DateTime? startDate,
    DateTime? endDate,
    bool? allDay,
    String? htmlLink,
  }) {
    return CalendarItem(
      id: id ?? this.id,
      calendarSourceId: calendarSourceId ?? this.calendarSourceId,
      providerId: providerId ?? this.providerId,
      title: title ?? this.title,
      color: color ?? this.color,
      startTime: startTime ?? this.startTime,
      endTime: endTime ?? this.endTime,
      startDate: startDate ?? this.startDate,
      endDate: endDate ?? this.endDate,
      allDay: allDay ?? this.allDay,
      htmlLink: htmlLink ?? this.htmlLink,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'calendar_source_id': calendarSourceId,
      'provider_id': providerId,
      'title': title,
      'color': color,
      'start_time': startTime?.toString(),
      'end_time': endTime?.toString(),
      'start_date': startDate?.toString(),
      'end_date': endDate?.toString(),
      'all_day': allDay,
      'html_link': htmlLink,
    };
  }

  /// Get the list of datekeys that this calendar should appear in.
  List<String> dateKeys() {
    var allOptions = [startDate, endDate, startTime, endTime];
    var options = allOptions.whereType<DateTime>().toList();

    if (options.length == 2) {
      return _getRangeInDays.call(options[0], options[1]);
    }
    return [];
  }

  List<String> _getRangeInDays(DateTime start, DateTime end) {
    List<String> days = [];
    var current = start;
    var inDays = start.difference(end).inDays;
    for (var i = 0; i <= inDays; i++) {
      days.add(formatters.dateString(current));
      current = current.add(const Duration(days: 1));
    }
    return days;
  }
}
