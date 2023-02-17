import 'package:docket/formatters.dart' as formatters;

class CalendarSource {
  int id = 0;
  String name;
  int calendarProviderId;
  String providerId;
  int color;
  DateTime? lastSync;

  CalendarSource({
    this.id = 0,
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
      id: json['id'] ?? 0,
      name: json['name'],
      calendarProviderId: json['calendar_provider_id'] ?? 0,
      providerId: json['provider_id'] ?? '',
      color: json['color'] ?? 0,
      lastSync: lastSync,
    );
  }

  /// Linked sources are those with ids or providers.
  get isLinked {
    return id != 0 || calendarProviderId != 0;
  }

  Map<String, Object?> toMap() {
    String? syncStr;
    if (lastSync != null) {
      syncStr = formatters.dateString(lastSync!);
    }
    return {
      'id': id,
      'calendar_provider_id': calendarProviderId,
      'provider_id': providerId,
      'name': name,
      'color': color,
      'last_sync': syncStr,
    };
  }
}
