import 'package:clock/clock.dart';
import 'package:flutter/material.dart';
import 'package:json_cache/json_cache.dart';

/// Abstract class that will act as the base of the Repository 
/// based database implementation.
///
/// Listeners will be notified when this view cache is cleared.
///
/// Subclasses of this are not intended to be used standalone.
/// Instead access them from the `LocalDatabase` in `database.dart`.
abstract class Repository<T> extends ChangeNotifier {
  late JsonCache _database;
  Duration? duration;

  /// In memory copy of raw data. Helps save overhead
  /// of reading from JsonCache repeatedly.
  Map<String, dynamic>? _state;

  /// The time data in this store was expired.
  DateTime? _expiredAt;

  Repository(JsonCache database, this.duration) {
    _database = database;
  }

  Map<String, dynamic>? get state => _state;

  /// Check if the local data is within the cache duration.
  /// Stale data will be returned by _get(). Use this method 
  /// to see if a server refresh should be performed.
  bool isFresh() {
    var state = _state;
    if (state == null) {
      return false;
    }
    // No duration means always fresh.
    if (duration == null) {
      return true;
    }
    // Expired views are not fresh.
    if (_expiredAt != null) {
      return false;
    }
    var updated = state['updatedAt'];
    // No updatedAt means we need to refresh.
    if (updated == null) {
      return false;
    }
    var updatedAt = DateTime.parse(updated);
    var expires = clock.now();
    expires = expires.subtract(duration!);

    return updatedAt.isAfter(expires);
  }

  /// Mark the local data as expired/stale.
  ///
  /// This doesn't remove the data but does flag it as expired.
  /// Can notify if `notify` is set to true.
  void expire({bool notify = false}) {
    _expiredAt = clock.now();

    if (notify) {
      notifyListeners();
    }
  }

  /// Whether or not this view cache has been expired.
  bool get isExpired {
    return _expiredAt != null;
  }

  /// Update local database and in-process state as well.
  Future<void> setMap(Map<String, dynamic> data) async {
    var payload = {'updatedAt': clock.now().toIso8601String(), 'data': data};
    _state = payload;
    _expiredAt = null;
    await _database.refresh(keyName(), payload);
  }

  /// Fetch raw map data from in-process or local database.
  Future<Map<String, dynamic>?> getMap() async {
    var state = _state;
    if (state != null) {
      return state['data'];
    }
    var payload = await _database.value(keyName());
    if (payload == null) {
      return null;
    }
    _state = payload;
    return payload['data'];
  }

  /// Clear the locally cached data. Will notify listeners as well.
  Future<void> clear() async {
    _state = null;
    _expiredAt = null;
    await _database.remove(keyName());
    notifyListeners();
  }

  // Clear locally cached data. Will *not* notify
  Future<void> clearSilent() async {
    _state = null;
    _expiredAt = null;
    return _database.remove(keyName());
  }

  /// Get the database keyname for this repository,
  String keyName();

  /// Set data into this repository
  Future<void> set(T data);
}
