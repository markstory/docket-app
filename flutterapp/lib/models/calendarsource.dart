import 'package:docket/formatters.dart' as formatters;

class CalendarSource {
  String id = '';
  String name;
  int calendarProviderId;
  String providerId;
  int color;
  DateTime? lastSync;

  CalendarSource({
    this.id = '',
    required this.name,
    required this.calendarProviderId,
    required this.providerId,
    this.color = 0,
    this.lastSync,
  }); 

  factory CalendarSource.fromMap(Map<String, dynamic> json) {
    DateTime? lastSync;

    if (json['last_sync'] != null) {
      lastSync = formatters.parseToLocal(json['last_sync']);
    }

    return CalendarSource(
      id: json['id'].toString(),
      name: json['name'],
      calendarProviderId: json['calendar_provider_id'],
      providerId: json['provider_id'],
      color: json['color'] ?? 0,
      lastSync: lastSync,
    );
  }

  /// Linked sources are those with ids or providers.
  get isLinked {
    return id.isNotEmpty || calendarProviderId != 0;
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'calendar_provider_id': calendarProviderId,
      'provider_id': providerId,
      'name': name,
      'color': color,
      'last_sync': lastSync?.toString(),
    };
  }
}
