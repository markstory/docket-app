import 'package:flutter/foundation.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/providers/session.dart';

class UserProfileProvider extends ChangeNotifier {
  late LocalDatabase _database;
  SessionProvider? session;
  bool loading = false;

  UserProfileProvider(LocalDatabase database, this.session) {
    _database = database;
  }

  void setSession(SessionProvider session) {
    this.session = session;
  }

  /// Update the user on the server
  Future<void> update(UserProfile profile) async {
    profile = await actions.updateUser(session!.apiToken, profile);
    await _database.profile.set(profile);

    notifyListeners();
  }

  Future<UserProfile> refresh() async {
    if (loading) {
      return UserProfile.blank();
    }
    loading = true;
    var profile = await actions.fetchUser(session!.apiToken);
    await _database.profile.set(profile);
    loading = false;

    notifyListeners();

    return profile;
  }

  Future<UserProfile> get() async {
    var profile = await _database.profile.get();
    if (profile != null) {
      return profile;
    }
    return refresh();
  }

  Future<void> clear() {
    return _database.profile.clear();
  }
}
