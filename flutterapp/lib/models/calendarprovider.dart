import 'package:docket/models/calendarsource.dart';

class CalendarProvider {
  String id = '';
  String kind;
  String identifier;
  String displayName;
  List<CalendarSource> sources = [];

  CalendarProvider({
    this.id = '',
    required this.kind,
    required this.identifier,
    this.displayName = '',
    sources = const [],
  }); 

  factory CalendarProvider.fromMap(Map<String, dynamic> json) {
    List<CalendarSource> sources = [];
    for (var item in json['calendar_sources'] ?? []) {
      sources.add(CalendarSource.fromMap(item));
    }

    return CalendarProvider(
      id: json['id'].toString(),
      kind: json['kind'],
      identifier: json['identifier'],
      displayName: json['display_name'],
      sources: sources,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'kind': kind,
      'identifier': identifier,
      'display_name': displayName,
      'calendar_sources': sources.map((source) => source.toMap()),
    };
  }
}
