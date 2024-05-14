import 'package:docket/formatters.dart' as formatters;

class CalendarSource {
  int id = 0;
  String name;
  int calendarProviderId;
  String providerId;
  int color;
  bool synced;
  DateTime? lastSync;

  CalendarSource({
    this.id = 0,
    required this.name,
    required this.calendarProviderId,
    required this.providerId,
    this.color = 0,
    this.synced = true,
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
      synced: json['synced'] ?? true,
      lastSync: lastSync,
    );
  }

  /// Linked sources are those with ids or providers.
  get isLinked {
    return synced;
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
      'synced': synced,
      'last_sync': syncStr,
    };
  }
}
