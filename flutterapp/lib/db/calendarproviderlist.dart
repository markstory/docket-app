import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/calendarprovider.dart';

class CalendarProviderListRepo extends Repository<List<CalendarProvider>> {
  static const String name = 'calendarproviderlist';

  CalendarProviderListRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(List<CalendarProvider> providers) async {
    return setMap({"items": providers.map((p) => p.toMap()).toList()});
  }

  /// Get the list of providers.
  Future<List<CalendarProvider>?> get() async {
    var data = await getMap();
    if (data == null) {
      return null;
    }
    var items = data['items'];
    if (items == null) {
      return null;
    }
    if (items.runtimeType != List && items.runtimeType != List<Map<String, Object?>>) {
      return null;
    }
    return (items as List).map<CalendarProvider>((item) => CalendarProvider.fromMap(item)).toList();
  }

  /// Remove a provider by id and notify.
  Future<void> remove(int id) async {
    var items = await get() ?? [];
    items.removeWhere((item) => item.id == id);
    await set(items);

    notifyListeners();
  }
}

