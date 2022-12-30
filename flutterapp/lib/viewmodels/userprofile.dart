import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/providers/session.dart';

class UserProfileViewModel extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;
  bool _loading = false;

  UserProfile? _profile;

  UserProfileViewModel(LocalDatabase database, this.session) {
    _database = database;
  }

  void setSession(SessionProvider session) {
    this.session = session;
  }

  bool get loading => _loading;

  UserProfile get profile {
    var value = _profile;
    assert(value != null, 'cannot access profile as it has not been loaded yet.');

    return value!;
  }

  /// Load data from local database or fetch from server.
  Future<void> loadData() async {
    await fetchData();
    if (!_loading && (_profile == null || !_database.profile.isFresh())) {
      await refresh();
    }
  }

  /// Load local data and notify.
  Future<void> fetchData() async {
    _loading = true;
    _profile = await _database.profile.get();
    _loading = false;

    notifyListeners();
  }

  /// Update the user on the server
  Future<void> update(UserProfile profile) async {
    profile = await actions.updateUser(session!.apiToken, profile);
    await _database.profile.set(profile);
    _profile = profile;

    notifyListeners();
  }

  /// Reload the profile from the server
  Future<void> refresh() async {
    _loading = true;
    _profile = await actions.fetchUser(session!.apiToken);
    await _database.profile.set(profile);
    _loading = false;

    notifyListeners();
  }
}
