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

  bool get hasToken {
    var current = state;

    return !(current == null || current['data']['token'] == null);
  }

  /// Get the current token. Requires that a token be loaded with get() or set() first.
  String get token {
    var current = state;
    if (current == null || current['data']['token'] == null) {
      throw Exception('Cannot access `token` as it has not been loaded.');
    }
    return current['data']['token'];
  }

  /// Set a new API token into the database.
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
