import 'package:docket/formatters.dart' as formatters;

class CalendarSource {
  String id = '';
  String name;
  int providerId;
  int color;
  DateTime? lastSync;

  CalendarSource({
    this.id = '',
    required this.name,
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
      providerId: json['provider_id'],
      color: json['color'] ?? 0,
      lastSync: lastSync,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'provider_id': providerId,
      'name': name,
      'color': color,
      'last_sync': lastSync?.toString(),
    };
  }
}
