import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/apitoken.dart';

class ApiTokenRepo extends Repository<ApiToken> {
  static const String name = 'apitoken';

  ApiTokenRepo(JsonCache database, Duration? duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Set completed tasks for a project into the lookup
  @override
  Future<void> set(ApiToken token) async {
    return setMap(token.toMap());
  }

  Future<ApiToken?> get() async {
    var data = await getMap();
    if (data == null) {
      return null;
    }
    return ApiToken.fromMap(data);
  }
}
