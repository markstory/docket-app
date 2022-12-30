import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/calendarprovider.dart';

class CalendarProviderDetailsRepo extends Repository<CalendarProvider> {
  static const String name = 'calendarproviderdetails';

  CalendarProviderDetailsRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set a provider to the lookup map
  @override
  Future<void> set(CalendarProvider provider) async {
    var data = await getMap() ?? {};
    data[provider.id.toString()] = provider.toMap();

    return setMap(data);
  }

  Future<CalendarProvider?> get(int id) async {
    var data = await getMap();
    if (data == null) {
      return null;
    }
    var providerId = id.toString();
    if (data[providerId] == null) {
      return null;
    }
    return CalendarProvider.fromMap(data[providerId]);
  }

  /// Remove a provider by id and notify.
  Future<void> remove(int id) async {
    var data = await getMap() ?? {};
    data.remove(id.toString());
    await setMap(data);

    notifyListeners();
  }
}
