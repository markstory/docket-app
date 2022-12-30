import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/project.dart';

class ProjectArchiveRepo extends Repository<List<Project>> {
  static const String name = 'projectarchive';

  ProjectArchiveRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'upcoming' view.
  @override
  Future<void> set(List<Project> data) async {
    return setMap({'projects': data.map((project) => project.toMap()).toList()});
  }

  Future<List<Project>?> get() async {
    var data = await getMap();
    // Likely loading.
    if (data == null || data['projects'] == null) {
      return null;
    }

    return (data['projects'] as List).map<Project>((item) => Project.fromMap(item)).toList();
  }
}

