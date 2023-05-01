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
import 'package:docket/formatters.dart' as formatters;

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
    if (body.isEmpty) {
      return ValidationError(message, errors);
    }

    try {
      var bodyData = utf8.decode(body);
      developer.log('$message. Response: $bodyData', name: 'docket.actions');

      var decoded = jsonDecode(bodyData);
      if (decoded == null || (decoded['error'] == null && decoded['errors'] == null)) {
        throw Exception('Could not parse response, or find `errors` key.');
      }
      if (decoded['error'] is String) {
        errors.add(decoded['error']);
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

    return ValidationError(message, errors);
  }

  @override
  String toString() {
    var details = '';
    if (errors.isNotEmpty) {
      details = errors.reduce((error, built) => "$built $error");
    }

    return "$message $details";
  }
}

/// Thrown when response data cannot be decoded as JSON.
/// Likely culprits are failure modes where an HTML page
/// is returned. The `responseData` attribute contains
/// the string value of the response data if available.
class DecodeError implements Exception {
  final String message;
  final String? responseData; 
  final StackTrace? stack;

  const DecodeError(this.message, {this.responseData, this.stack});

  @override
  String toString() {
    return "DecodeError<message=$message responseData=$responseData stack=$stack>";
  }
}

// {{{ Client basics

var client = http.Client();

Uri _makeUrl(String pathAndQuery) {
  return Uri.parse('$baseUrl$pathAndQuery');
}

/// Do an HTTP GET request.
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

/// Do an HTTP POST request.
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

/// Reset the HTTP client to a new instance
void resetClient() {
  client = http.Client();
}

/// Decode the response as utf-8 JSON. If either the utf8
/// or json decoding fail an `DecodeError` exception will be raised.
T _decodeResponse<T>(
  List<int> responseBody,
  T Function(Map<dynamic, dynamic> decoded) decoder
) {
  String responseString;
  try {
    responseString = utf8.decode(responseBody);
  } catch (err) {
    throw const DecodeError('Could not decode unicode');
  }
  try {
    var mapData = jsonDecode(responseString) as Map;
    return decoder(mapData);
  } catch (err, stack) {
    developer.log('Failed to decode: $err', name: 'docket.actions.decodeResponse');
    throw DecodeError('Failed to decode $err', responseData: responseString, stack: stack);
  }
}



// }}}

/// Perform a login request.
/// The entity returned contains an API token
/// that can be used until revoked serverside.
Future<ApiToken> doLogin(String email, String password) async {
  var url = _makeUrl('/mobile/login');

  var body = {'email': email, 'password': password};

  var response = await httpPost(url, body: body, errorMessage: 'Login failed: ');
  developer.log('login complete', name: 'docket.actions');

  return _decodeResponse(response.bodyBytes, (mapData) => ApiToken.fromMap(mapData['apiToken']));
}

// Profile Methods {{{

/// Update the timezone. Fired during application startup to automatically 
/// sync the account timezone to where the user is.
Future<void> updateTimezone(String apiToken) async {
  var url = _makeUrl('/users/profile');
  try {
    var date = DateTime.now();
    var body = {'timezone': date.timeZoneName};
    await httpPost(url, body: body);
  } catch (err) {
    developer.log('failed to update timezone. $err');
  }
}

/// Get the current user's profile
Future<UserProfile> fetchUser(String apiToken) async {
  var url = _makeUrl('/users/profile');

  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Failed to fetch user');
  return _decodeResponse(response.bodyBytes, (mapData) => UserProfile.fromMap(mapData['user']));
}

/// Update a user's profile
Future<UserProfile> updateUser(String apiToken, UserProfile profile) async {
  var url = _makeUrl('/users/profile');

  var body = profile.toMap();
  // TODO: Update the server to return the updated user.
  await httpPost(url, apiToken: apiToken, body: body, errorMessage: 'Failed to update user');
  return profile;
}

// }}}

// Task Methods {{{

/// Get tasks and calendaritems for a single day.
/// Generally used for today view.
Future<DailyTasksData> fetchDailyTasks(String apiToken, DateTime date, {bool overdue = true}) async {
  var urlDate = formatters.dateString(date);
  var url = _makeUrl('/tasks/day/$urlDate?overdue=$overdue');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<Task> tasks = [];
    List<CalendarItem> calendarItems = [];
    for (var item in mapData['tasks']) {
      tasks.add(Task.fromMap(item));
    }
    for (var item in mapData['calendarItems']) {
      calendarItems.add(CalendarItem.fromMap(item));
    }
    var initial = TaskViewData(tasks: tasks, calendarItems: calendarItems);

    return initial.groupByDay(daysToFill: 0, groupOverdue: overdue);
  });
}

/// Fetch the tasks and calendar items for the 'Upcoming' view
Future<DailyTasksData> fetchUpcomingTasks(String apiToken) async {
  var url = _makeUrl('/tasks/upcoming');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<CalendarItem> calendarItems = [];
    List<Task> tasks = [];
    for (var item in mapData['tasks']) {
      tasks.add(Task.fromMap(item));
    }
    for (var item in mapData['calendarItems']) {
      calendarItems.add(CalendarItem.fromMap(item));
    }
    var singleCollection = TaskViewData(tasks: tasks, calendarItems: calendarItems);

    return singleCollection.groupByDay();
  });
}

/// Fetch completed tasks for a project
Future<ProjectWithTasks> fetchCompletedTasks(String apiToken, String slug) async {
  var url = _makeUrl('/projects/$slug?completed=1');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load completed tasks');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<Task> tasks = [];
    if (mapData['completed'] != null) {
      for (var item in mapData['completed']) {
        tasks.add(Task.fromMap(item));
      }
    }
    if (mapData['tasks'] != null) {
      for (var item in mapData['tasks']) {
        tasks.add(Task.fromMap(item));
      }
    }

    return ProjectWithTasks(
      tasks: tasks,
      project: Project.fromMap(mapData['project']),
    );
  });
}

/// Fetch deleted tasks for a project
Future<TaskViewData> fetchTrashbin(String apiToken) async {
  var url = _makeUrl('/tasks/deleted');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load trash bin');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<Task> tasks = [];
    if (mapData['tasks'] != null) {
      for (var item in mapData['tasks']) {
        tasks.add(Task.fromMap(item));
      }
    }

    return TaskViewData(
      tasks: tasks,
      calendarItems: [],
    );
  });
}


/// Update a task complete/incomplete state..
Future<void> toggleTask(String apiToken, Task task) async {
  var operation = task.completed ? 'complete' : 'incomplete';
  var url = _makeUrl('/tasks/${task.id}/$operation');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not update task');
}

/// Create a task
Future<Task> createTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/add');

  var response = await httpPost(
    url, 
    apiToken: apiToken, 
    body: task.toMap(), 
    errorMessage: 'Could not create task'
  );
  return _decodeResponse(response.bodyBytes, (mapData) => Task.fromMap(mapData['task']));
}

/// Update a task
Future<Task> updateTask(String apiToken, Task task) async {
  if (task.id == null) {
    return createTask(apiToken, task);
  }
  var url = _makeUrl('/tasks/${task.id}/edit');

  var response = await httpPost(url, apiToken: apiToken, body: task.toMap(), errorMessage: 'Could not update task');
  return _decodeResponse(response.bodyBytes, (mapData) => Task.fromMap(mapData['task']));
}

/// Delete a task
Future<void> deleteTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/${task.id}/delete');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not delete task');
}

/// Undelete a task
Future<void> undeleteTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/${task.id}/undelete');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not undelete task');
}

/// Move a task
Future<void> moveTask(String apiToken, Task task, Map<String, dynamic> updates) async {
  var url = _makeUrl('/tasks/${task.id}/move');

  await httpPost(url, apiToken: apiToken, body: updates, errorMessage: 'Could not move task');
}

/// Fetch a task by id
Future<Task> fetchTaskById(String apiToken, int id) async {
  var url = _makeUrl('/tasks/$id/view');

  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load tasks');
  return _decodeResponse(response.bodyBytes, (mapData) => Task.fromMap(mapData['task']));
}
// }}}

// {{{ Subtask methods

/// Update a subtask complete/incomplete state..
Future<void> toggleSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/toggle');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not update subtask');
}

/// Move a subtask
Future<void> moveSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/move');
  var updates = {'ranking': subtask.ranking};

  await httpPost(url, apiToken: apiToken, body: updates, errorMessage: 'Could not move subtask');
}

/// Update a subtask
Future<Subtask> updateSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/edit');
  var response = await httpPost(url, apiToken: apiToken, body: subtask.toMap(), errorMessage: 'Could not update subtask');
  return _decodeResponse(response.bodyBytes, (mapData) => Subtask.fromMap(mapData['subtask']));
}

/// Create a subtask
Future<Subtask> createSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks');
  var response = await httpPost(url, apiToken: apiToken, body: subtask.toMap(), errorMessage: 'Could not update subtask');
  return _decodeResponse(response.bodyBytes, (mapData) => Subtask.fromMap(mapData['subtask']));
}

/// Delete a subtask
Future<void> deleteSubtask(String apiToken, Task task, Subtask subtask) async {
  var url = _makeUrl('/tasks/${task.id}/subtasks/${subtask.id}/delete');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not delete subtask.');
}

// }}}

// Project methods {{{
Future<ProjectWithTasks> fetchProjectBySlug(String apiToken, String slug) async {
  var url = _makeUrl('/projects/$slug');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load project');
  return _decodeResponse(response.bodyBytes, (mapData) {
    var project = Project.fromMap(mapData['project']);
    List<Task> tasks = [];
    for (var item in mapData['tasks']) {
      // TODO do this on the server so that tasks are serialized consistently.
      item['project'] = {'id': project.id, 'slug': project.slug, 'name': project.name, 'color': project.color};

      tasks.add(Task.fromMap(item));
    }
    return ProjectWithTasks(
      project: project,
      tasks: tasks,
    );
  });
}

Future<List<Project>> fetchProjects(String apiToken) async {
  var url = _makeUrl('/projects');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load projects');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<Project> projects = [];
    for (var item in mapData['projects']) {
      projects.add(Project.fromMap(item));
    }
    return projects;
  });
}

/// Fetch archived projects
Future<List<Project>> fetchProjectArchive(String apiToken) async {
  var url = _makeUrl('/projects/archived');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load projects');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<Project> projects = [];
    for (var item in mapData['projects']) {
      projects.add(Project.fromMap(item));
    }
    return projects;
  });
}

/// Create a project
Future<Project> createProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/add');
  var response = await httpPost(
    url,
    apiToken: apiToken, 
    body: project.toMap(), 
    errorMessage: 'Could not create project');
  return _decodeResponse(response.bodyBytes, (mapData) => Project.fromMap(mapData['project']));
}

/// Update a project
Future<Project> updateProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/edit');
  var response = await httpPost(
    url,
    apiToken: apiToken,
    body: project.toMap(),
    errorMessage: 'Could not update project'
  );
  return _decodeResponse(response.bodyBytes, (mapData) => Project.fromMap(mapData['project']));
}

/// Move a project
Future<Project> moveProject(String apiToken, Project project, int newRank) async {
  var url = _makeUrl('/projects/${project.slug}/move');
  var response = await httpPost(
    url,
    apiToken: apiToken,
    body: {'ranking': newRank},
    errorMessage: 'Could not move project'
  );
  return _decodeResponse(response.bodyBytes, (mapData) => Project.fromMap(mapData['project']));
}

/// Archive a project
Future<void> archiveProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/archive');

  await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not archive project');
}

/// Unarchive a project
Future<void> unarchiveProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/unarchive');

  await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not unarchive project');
}

/// Delete a project
Future<void> deleteProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/${project.slug}/delete');

  await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete project');
}
// }}}

// {{{ Section Methods
/// Create a project section
Future<void> createSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections');

  await httpPost(url, apiToken: apiToken, body: section.toMap(), errorMessage: 'Could not create section');
}

/// Delete a project section
Future<void> deleteSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/delete');

  await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete section');
}

/// Move a project section
Future<void> moveSection(String apiToken, Project project, Section section, int newIndex) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/move');

  await httpPost(url, apiToken: apiToken, body: {'ranking': newIndex}, errorMessage: 'Could not move section');
}

/// Update a project section
Future<void> updateSection(String apiToken, Project project, Section section) async {
  var url = _makeUrl('/projects/${project.slug}/sections/${section.id}/edit');

  await httpPost(url, apiToken: apiToken, body: section.toMap(), errorMessage: 'Could not update section');
}
// }}}

// CalendarProviders

/// Create a calendar provider from credentials
Future<CalendarProvider> createCalendarProviderFromGoogle(
  String apiToken,
  {String? refreshToken, String? accessToken}
) async {
  var url = _makeUrl('/calendars/google/new');
  var body = {
    'refreshToken': refreshToken,
    'accessToken': accessToken,
  };
  var response = await httpPost(url, apiToken: apiToken, body: body, errorMessage: 'Could not create calendar account');
  return _decodeResponse(response.bodyBytes, (mapData) => CalendarProvider.fromMap(mapData['provider']));
}

/// Fetch a list of calendar providers.
Future<List<CalendarProvider>> fetchCalendarProviders(String apiToken) async {
  var url = _makeUrl('/calendars');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load calendar settings');

  return _decodeResponse(response.bodyBytes, (mapData) {
    List<CalendarProvider> providers = [];
    for (var item in mapData['providers']) {
      providers.add(CalendarProvider.fromMap(item));
    }
    return providers;
  });
}

/// Fetch a provider by details
Future<CalendarProvider> fetchCalendarProvider(String apiToken, int id) async {
  var url = _makeUrl('/calendars/$id/view');
  var response = await httpGet(url, apiToken: apiToken, errorMessage: 'Could not load calendar provider');

  return _decodeResponse(response.bodyBytes, (mapData) {
    var provider = CalendarProvider.fromMap(mapData['provider']);

    // Add un-linked calendars as well.
    var calendars = mapData['calendars'];
    if (calendars != null && (calendars.runtimeType == List || calendars.runtimeType == List<Map<String, Object?>> )) {
      for (var item in calendars) {
        provider.sources.add(CalendarSource.fromMap(item));
      }
    }
    return provider;
  });
}

/// Delete a Calendar Provider
Future<void> deleteCalendarProvider(String apiToken, CalendarProvider provider) async {
  var url = _makeUrl('/calendars/${provider.id}/delete');

  await httpPost(url, apiToken: apiToken, errorMessage: 'Could not delete calendar account');
}

/// Create a calendar source on the server.
Future<CalendarSource> createSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/add');

  var body = source.toMap();
  body.remove('id');

  var response = await httpPost(url, body: body, apiToken: apiToken, errorMessage: 'Could not update calendar settings');
  return _decodeResponse(response.bodyBytes, (mapData) => CalendarSource.fromMap(mapData['source']));
}

/// Update the settings on a source.
Future<CalendarSource> updateSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/edit');
  var body = {'color': source.color, 'name': source.name};
  var response = await httpPost(url, body: body, apiToken: apiToken, errorMessage: 'Could not update calendar settings');
  return _decodeResponse(response.bodyBytes, (mapData) => CalendarSource.fromMap(mapData['source']));
}

/// Sync events on a source.
Future<CalendarSource> syncSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/sync');
  var response = await httpPost(url, body: source.toMap(), apiToken: apiToken, errorMessage: 'Could not refresh calendar events');
  return _decodeResponse(response.bodyBytes, (mapData) => CalendarSource.fromMap(mapData['source']));
}

/// Delete a source.
Future<void> deleteSource(String apiToken, CalendarSource source) async {
  var url = _makeUrl('/calendars/${source.calendarProviderId}/sources/${source.id}/delete');

  await httpPost(url, apiToken: apiToken, body: {}, errorMessage: 'Could not delete calendar');
}
// }}}
