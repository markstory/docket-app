import 'dart:developer' as developer;
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/calendaritem.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/models/calendarsource.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';
import 'package:docket/models/userprofile.dart';

/// This needs to come from a props/config file but I don't know
/// how to do that yet.
const baseUrl = 'https://docket.mark-story.com';

class ValidationError implements Exception {
  final String message;
  final List<String> errors;

  const ValidationError(this.message, this.errors);

  /// Parse the provided body as a standard API error response
  /// The created exception will have the parsed errors available
  /// in `err.errors`.
  factory ValidationError.fromResponseBody(String message, List<int> body) {
    List<String> errors = [];
    try {
      var bodyData = utf8.decode(body);
      developer.log('$message. Response: $bodyData', name: 'docket.actions');

      var decoded = jsonDecode(bodyData);
      if (decoded == null || decoded['errors'] == null) {
        throw Exception('Could not parse response, or find `errors` key.');
      }
      if (decoded['errors'] is List) {
        for (var line in decoded['errors']) {
          errors.add(line.toString());
        }
      }
      if (decoded['errors'] is Map) {
        for (var key in decoded['errors'].keys) {
          var fieldError = decoded['errors'][key].toString();
          errors.add("$key: $fieldError");
        }
      }
    } catch (e) {
      errors = [e.toString()];
    }

    throw ValidationError(message, errors);
  }

  @override
  String toString() {
    var details = errors.reduce((error, built) => "$built $error");

    return "$message $details";
  }
}

Uri _makeUrl(String pathAndQuery) {
  return Uri.parse('$baseUrl$pathAndQuery');
}

Future<http.Response> httpGet(Uri url, {String? apiToken, String? errorMessage}) async {
  var headers = {
    'User-Agent': 'docket-flutter',
    'Accept': 'application/json',
  };
  if (apiToken != null) {
    headers['Authorization'] = 'Bearer $apiToken';
  }
  var response = await client.get(
    url,
    headers: headers,
  );
  developer.log('Sending GET request to $url', name: 'docket.actions');
  if (response.statusCode >= 400) {
    developer.log('Request to $url failed', name: 'docket.actions');
    errorMessage ??= 'Request Failed to ${url.path}';
    throw ValidationError.fromResponseBody(errorMessage, response.bodyBytes);
  }

  return response;
}

Future<http.Response> httpPost(
  Uri url, {
  String? apiToken,
  Map<String, dynamic>? body,
  String? errorMessage,
}) async {
  var headers = {
    'User-Agent': 'docket-flutter',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };
  if (apiToken != null && apiToken.isNotEmpty) {
    headers['Authorization'] = 'Bearer $apiToken';
  }
  developer.log('Sending POST request to $url', name: 'docket.actions');
  var response = await client.post(
    url,
    headers: headers,
    body: jsonEncode(body),
  );
  if (response.statusCode >= 400) {
    errorMessage ??= 'Request Failed to ${url.path}';
    var err = ValidationError.fromResponseBody(errorMessage, response.bodyBytes);
    developer.log(err.toString(), name: 'docket.actions');
    throw err;
  }

  return response;
}

var client = http.Client();

/// Reset the HTTP client to a new instance
void resetClient() {
  client = http.Client();
}

/// Perform a login request.
/// The entity returned contains an API token
/// that can be used until revoked serverside.
Future<ApiToken> doLogin(String email, String password) async {
  var url = _makeUrl('/mobile/login');

  var body = {'email': email, 'password': password};

  var response = await httpPost(url, body: body, errorMessage: 'Login Failed');
  developer.log('login complete', name: 'docket.actions');

  try {
    var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
    return ApiToken.fromMap(decoded['apiToken']);
  } catch (e) {
    developer.log('failed to decode ${e.toString()}', name: 'docket.actions');
    rethrow;
  }
}

// Profile Methods {{{
Future<UserProfile> fetchUser(String apiToken) async {
  var url = _makeUrl('/users/profile');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Failed to fetch user');
    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      return UserProfile.fromMap(decoded['user']);
    } catch (e) {
      developer.log('failed to decode ${e.toString()}', name: 'docket.actions');
      rethrow;
    }
  });
}

Future<UserProfile> updateUser(String apiToken, UserProfile profile) async {
  var url = _makeUrl('/users/profile');

  return Future(() async {
    var body = profile.toMap();
    // TODO: Update the server to return the updated user.
    await httpPost(url, apiToken: apiToken, body: body, errorMessage: 'Failed to update user');
    return profile;
  });
}
// }}}

// Task Methods {{{
/// Fetch the tasks for the 'Today' view
Future<TaskViewData> fetchTodayTasks(String apiToken) async {
  var url = _makeUrl('/tasks/today');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      List<CalendarItem> calendarItems = [];
      for (var item in decoded['tasks']) {
        tasks.add(Task.fromMap(item));
      }
      for (var item in decoded['calendarItems']) {
        calendarItems.add(CalendarItem.fromMap(item));
      }
      return TaskViewData(tasks: tasks, calendarItems: calendarItems);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions.today');
      rethrow;
    }
  });
}

/// Fetch the tasks and calendar items for the 'Upcoming' view
Future<TaskViewData> fetchUpcomingTasks(String apiToken) async {
  var url = _makeUrl('/tasks/upcoming');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      List<CalendarItem> calendarItems = [];
      for (var item in decoded['tasks']) {
        tasks.add(Task.fromMap(item));
      }
      for (var item in decoded['calendarItems']) {
        calendarItems.add(CalendarItem.fromMap(item));
      }
      return TaskViewData(tasks: tasks, calendarItems: calendarItems);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Fetch completed tasks for a project
Future<ProjectWithTasks> fetchCompletedTasks(String apiToken, String slug) async {
  var url = _makeUrl('/projects/$slug?completed=1');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load completed tasks');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      if (decoded['completed'] != null) {
        for (var item in decoded['completed']) {
          tasks.add(Task.fromMap(item));
        }
      }

      return ProjectWithTasks(
        tasks: tasks,
        project: Project.fromMap(decoded['project']),
      );
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Fetch deleted tasks for a project
Future<TaskViewData> fetchTrashbin(String apiToken) async {
  var url = _makeUrl('/tasks/deleted');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load trash bin');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      if (decoded['tasks'] != null) {
        for (var item in decoded['tasks']) {
          tasks.add(Task.fromMap(item));
        }
      }

      return TaskViewData(
        tasks: tasks,
        calendarItems: [],
      );
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}


/// Update a task complete/incomplete state..
Future<void> toggleTask(String apiToken, Task task) async {
  var operation = task.completed ? 'complete' : 'incomplete';
  var url = _makeUrl('/tasks/${task.id}/$operation');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, errorMessage: 'Could not update task');
  });
}

/// Create a task
Future<Task> createTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/add');

  return Future(() async {
    var response = await httpPost(url, apiToken: apiToken, body: task.toMap(), errorMessage: 'Could not create task');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Task.fromMap(decoded['task']);
  });
}

/// Update a task
Future<Task> updateTask(String apiToken, Task task) async {
  if (task.id == null) {
    return createTask(apiToken, task);
  }

  var url = _makeUrl('/tasks/${task.id}/edit');

  return Future(() async {
    var response = await httpPost(url, apiToken: apiToken, body: task.toMap(), errorMessage: 'Could not update task');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Task.fromMap(decoded['task']);
  });
}

/// Delete a task
Future<void> deleteTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/${task.id}/delete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, errorMessage: 'Could not delete task');
  });
}

/// Undelete a task
Future<void> undeleteTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/${task.id}/undelete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, errorMessage: 'Could not undelete task');
  });
}

/// Move a task
Future<void> moveTask(String apiToken, Task task, Map<String, dynamic> updates) async {
  var url = _makeUrl('/tasks/${task.id}/move');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: updates, errorMessage: 'Could not move task');
  });
}

/// Fetch a task by id
Future<Task> fetchTaskById(String apiToken, int id) async {
  var url = _makeUrl('/tasks/$id/view');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');

    try {
      var taskData = jsonDecode(utf8.decode(response.bodyBytes));
      return Task.fromMap(taskData['task']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}
// }}}

// {{{ Subtask methods

/// Update a subtask complete/incomplete state..
Future<void> toggleSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/toggle');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, errorMessage: 'Could not update subtask');
  });
}

/// Move a subtask
Future<void> moveSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/move');
  var updates = {'ranking': subtask.ranking};

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: updates, errorMessage: 'Could not move subtask');
  });
}

/// Update a subtask
Future<Subtask> updateSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/edit');

  return Future(() async {
    var response = await httpPost(url, apiToken: apiToken, body: subtask.toMap(), errorMessage: 'Could not update subtask');
    try {
      var data = jsonDecode(utf8.decode(response.bodyBytes));
      if (data['subtask'] != null) { 
        return Subtask.fromMap(data['subtask']);
      }
      throw Exception('Invalid response data received');
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Create a subtask
Future<Subtask> createSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks');

  return Future(() async {
    var response = await httpPost(url, apiToken: apiToken, body: subtask.toMap(), errorMessage: 'Could not update subtask');
    try {
      var data = jsonDecode(utf8.decode(response.bodyBytes));
      if (data['subtask'] != null) { 
        return Subtask.fromMap(data['subtask']);
      }
      throw Exception('Invalid response data received');
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Delete a subtask
Future<void> deleteSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/delete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, errorMessage: 'Could not delete subtask.');
  });
}

// }}}

// Project methods {{{
Future<ProjectWithTasks> fetchProjectBySlug(String apiToken, String slug) async {
  var url = _makeUrl('/projects/$slug');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load project');

    try {
      var data = jsonDecode(utf8.decode(response.bodyBytes));
      if (data['project'] != null && data['tasks'] != null) {
        var project = Project.fromMap(data['project']);
        List<Task> tasks = [];
        for (var item in data['tasks']) {
          // TODO do this on the server so that tasks are serialized consistently.
          item['project'] = {'id': project.id, 'slug': project.slug, 'name': project.name, 'color': project.color};

          tasks.add(Task.fromMap(item));
        }
        return ProjectWithTasks(
          project: project,
          tasks: tasks,
        );
      }
      throw Exception('Invalid response data received');
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

Future<List<Project>> fetchProjects(String apiToken) async {
  var url = _makeUrl('/projects');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load projects');

    try {
      var projectData = jsonDecode(utf8.decode(response.bodyBytes));
      List<Project> projects = [];
      for (var item in projectData['projects']) {
        projects.add(Project.fromMap(item));
      }
      return projects;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Fetch archived projects
Future<List<Project>> fetchProjectArchive(String apiToken) async {
  var url = _makeUrl('/projects/archived');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load projects');

    try {
      var projectData = jsonDecode(utf8.decode(response.bodyBytes));
      List<Project> projects = [];
      for (var item in projectData['projects']) {
        projects.add(Project.fromMap(item));
      }
      return projects;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Create a project
Future<Project> createProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/add');

  return Future(() async {
    var response =
        await httpPost(url, apiToken: apiToken, body: project.toMap(), errorMessage: 'Could not create project');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Project.fromMap(decoded['project']);
  });
}

/// Update a project
Future<Project> updateProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/edit');

  return Future(() async {
    var response =
        await httpPost(url, apiToken: apiToken, body: project.toMap(), errorMessage: 'Could not update project');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Project.fromMap(decoded['project']);
  });
}

/// Move a project
Future<Project> moveProject(String apiToken, Project project, int newRank) async {
  var url = _makeUrl('/projects/${project.slug}/move');

  return Future(() async {
    var response =
        await httpPost(url, apiToken: apiToken, body: {'ranking': newRank}, errorMessage: 'Could not move project');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Project.fromMap(decoded['project']);
  });
}

/// Archive a project
Future<void> archiveProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/archive');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not archive project');
  });
}

/// Unarchive a project
Future<void> unarchiveProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/unarchive');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not unarchive project');
  });
}

/// Delete a project
Future<void> deleteProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/delete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete project');
  });
}
// }}}

// {{{ Section Methods
/// Create a project section
Future<void> createSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: section.toMap(), errorMessage: 'Could not create section');
  });
}

/// Delete a project section
Future<void> deleteSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/delete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete section');
  });
}

/// Move a project section
Future<void> moveSection(String apiToken, Project project, Section section, int newIndex) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/move');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {'ranking': newIndex}, errorMessage: 'Could not move section');
  });
}

/// Update a project section
Future<void> updateSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/edit');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: section.toMap(), errorMessage: 'Could not update section');
  });
}
// }}}

// CalendarProviders
/// Fetch a list of calendar providers.
Future<List<CalendarProvider>> fetchCalendarProviders(String apiToken) async {
  var url = _makeUrl('/calendars');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load calendar settings');

    try {
      var respData = jsonDecode(utf8.decode(response.bodyBytes));
      List<CalendarProvider> providers = [];
      for (var item in respData['providers']) {
        providers.add(CalendarProvider.fromMap(item));
      }
      return providers;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Fetch a provider by details
Future<CalendarProvider> fetchCalendarProvider(String apiToken, int id) async {
  var url = _makeUrl('/calendars/$id/view');

  return Future(() async {
    var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load calendar provider');

    try {
      var respData = jsonDecode(utf8.decode(response.bodyBytes));
      var provider = CalendarProvider.fromMap(respData['provider']);

      // Add un-linked calendars as well.
      var calendars = respData['calendars'];
      if (calendars != null && (calendars.runtimeType == List || calendars.runtimeType == List<Map<String, Object?>> )) {
        for (var item in calendars) {
          provider.sources.add(CalendarSource.fromMap(item));
        }
      }
      return provider;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Create a calendar source on the server.
Future<CalendarSource> createSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources');

  return Future(() async {
    var response = await httpPost(url, body: source.toMap(), apiToken: apiToken, errorMessage: 'Could not update calendar settings');

    try {
      var respData = jsonDecode(utf8.decode(response.bodyBytes));
      return CalendarSource.fromMap(respData['source']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Update the settings on a source.
Future<CalendarSource> updateSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/edit');
  var body = {'color': source.color, 'name': source.name};

  return Future(() async {
    var response = await httpPost(url, body: body, apiToken: apiToken, errorMessage: 'Could not update calendar settings');

    try {
      var respData = jsonDecode(utf8.decode(response.bodyBytes));
      return CalendarSource.fromMap(respData['source']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Sync events on a source.
Future<CalendarSource> syncSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/sync');

  return Future(() async {
    var response = await httpPost(url, body: source.toMap(), apiToken: apiToken, errorMessage: 'Could not refresh calendar events');

    try {
      var respData = jsonDecode(utf8.decode(response.bodyBytes));
      return CalendarSource.fromMap(respData['source']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace', name: 'docket.actions');
      rethrow;
    }
  });
}

/// Delete a source.
Future<void> deleteSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/delete');

  return Future(() async {
    await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete calendar');
  });
}
// }}}
