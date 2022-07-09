
class CalendarItem {
  int? id;
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
    this.id,
    this.calendarSourceId,, 
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
       startTime = DateTime.parse(json['start_time']);
    }
    if (json['start_time'] != null) {
       endTime = DateTime.parse(json['start_time']);
    }
    if (json['start_date'] != null) {
       startDate = DateTime.parse(json['start_date']);
    }
    if (json['end_date'] != null) {
       endDate = DateTime.parse(json['end_date']);
    }
    var projectSlug = json['project_slug'];
    projectSlug ??= json['project']['slug'];
    var projectColor = json['project_color'];
    projectColor ??= json['project']['color'];
    var projectName = json['project_name'];
    projectName ??= json['project']['name'];

    var evening = json['evening'];
    if (evening is int) {
      evening = evening == 0 ? false : true;
    }
    var completed = json['completed'];
    if (completed is int) {
      completed = completed == 0 ? false : true;
    }

    return CalendarItem(
      id: json['id'],
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
    int? id,
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
      'startTime': startTime?.toString(),
      'endTime': endTime?.toString(),
      'startDate': startDate?.toString(),
      'endDate': endDate?.toString(),
      'all_day': allDay,
      'html_link': htmlLink,
    };
  }
}
