import 'package:docket/models/calendarsource.dart';

class CalendarProvider {
  int id = 0;
  String kind;
  String identifier;
  String displayName;
  List<CalendarSource> sources = [];
  bool brokenAuth;

  CalendarProvider({
    this.id = 0,
    required this.kind,
    required this.identifier,
    this.displayName = '',
    this.sources = const [],
    this.brokenAuth = false,
  });

  factory CalendarProvider.fromMap(Map<dynamic, dynamic> json) {
    List<CalendarSource> sources = [];
    if (json['calendar_sources'] != null &&
        (json['calendar_sources'].runtimeType == List ||
            json['calendar_sources'].runtimeType == List<Map<String, Object?>>)) {
      for (var item in json['calendar_sources']) {
        sources.add(CalendarSource.fromMap(item));
      }
    }

    return CalendarProvider(
      id: json['id'],
      kind: json['kind'],
      identifier: json['identifier'],
      displayName: json['display_name'],
      sources: sources,
      brokenAuth: json['broken_auth'] ?? false,
    );
  }

  int _findSource(CalendarSource source) {
    return sources
        .indexWhere((item) => ((item.id != 0 && item.id == source.id) || item.providerId == source.providerId));
  }

  /// Replace or append a source to the provider.
  void replaceSource(CalendarSource source) {
    var index = _findSource(source);
    if (index >= 0) {
      sources[index] = source;
    } else {
      sources.insert(0, source);
    }
  }

  /// Remove a source from the provider
  void removeSource(CalendarSource source) {
    sources.remove(source);
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'kind': kind,
      'identifier': identifier,
      'display_name': displayName,
      'calendar_sources': sources.map((source) => source.toMap()).toList(),
      'broken_auth': brokenAuth,
    };
  }
}
