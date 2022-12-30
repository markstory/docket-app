import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/userprofile.dart';

class ProfileRepo extends Repository<UserProfile> {
  static const String name = 'userprofile';

  ProfileRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(UserProfile token) async {
    return setMap(token.toMap());
  }

  Future<UserProfile?> get() async {
    var data = await getMap();
    if (data == null) {
      return null;
    }
    return UserProfile.fromMap(data);
  }
}

