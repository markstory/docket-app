import 'package:json_cache/json_cache.dart';

import 'package:docket/db/repository.dart';
import 'package:docket/models/task.dart';

class TrashbinRepo extends Repository<TaskViewData> {
  static const String name = 'trashbin';

  TrashbinRepo(JsonCache database, Duration duration) : super(database, duration);

  @override
  String keyName() {
    return 'v1:$name';
  }

  /// Refresh the data stored for the 'today' view.
  @override
  Future<void> set(TaskViewData tasks) async {
    return setMap(tasks.toMap());
  }

  Future<TaskViewData> get() async {
    var data = await getMap();
    // Likely loading.
    if (data == null || data['tasks'] == null) {
      return TaskViewData(isEmpty: true, tasks: [], calendarItems: []);
    }
    return TaskViewData.fromMap(data);
  }
}
