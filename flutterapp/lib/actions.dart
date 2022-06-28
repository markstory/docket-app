import 'dart:developer' as developer;
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';

/// This needs to come from a props/config file but I don't know
/// how to do that yet.
const baseUrl = 'https://docket.mark-story.com';

class ValidationError implements Exception {
  final String message;
  final List<Object> errors;

  const ValidationError(this.message, this.errors);

  /// Parse the provided body as a standard API error response
  /// The created exception will have the parsed errors available
  /// in `err.errors`.
  factory ValidationError.fromResponseBody(String message, List<int> body) {
    List<String> errors = [];
    try {
      var bodyData = utf8.decode(body);
      developer.log('$message. Response: $bodyData');

      var decoded = jsonDecode(bodyData);
      if (decoded == null || decoded['errors'] == null) {
        throw Exception('Could not parse response, or find `errors` key.');
      }
      for (var line in decoded['errors']) {
        errors.add(line);
      }
    } catch (e) {
      errors = [e.toString()];
    }

    throw ValidationError(message, errors);
  }

  @override
  String toString() {
    return message;
  }
}

Uri _makeUrl(String path) {
  var url = Uri.parse('$baseUrl$path');
  developer.log('http.request url=$url');

  return url;
}

Future<http.Response> httpGet(Uri url,
    {String? apiToken, String? errorMessage}) async {
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
  if (response.statusCode >= 400) {
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
  };
  if (apiToken != null) {
    headers['Authorization'] = 'Bearer $apiToken';
  }
  var response = await client.post(
    url,
    headers: headers,
    body: body,
  );
  if (response.statusCode >= 400) {
    errorMessage ??= 'Request Failed to ${url.path}';
    throw ValidationError.fromResponseBody(errorMessage, response.bodyBytes);
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

  return Future(() async {
    var response =
        await httpPost(url, body: body, errorMessage: 'Login Failed');
    developer.log('login complete');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      return ApiToken.fromMap(decoded['apiToken']);
    } catch (e) {
      developer.log('failed to decode ${e.toString()}');
      rethrow;
    }
  });
}

/// Fetch the tasks for the 'Today' view
Future<List<Task>> loadTodayTasks(String apiToken) async {
  var url = _makeUrl('/tasks/today');

  return Future(() async {
    var response = await httpGet(url,
        apiToken: apiToken, errorMessage: 'Could not load tasks');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      for (var item in decoded['tasks']) {
        tasks.add(Task.fromMap(item));
      }
      return tasks;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

/// Fetch the tasks for the 'Upcoming' view
Future<List<Task>> loadUpcomingTasks(String apiToken) async {
  var url = _makeUrl('/tasks/upcoming');

  return Future(() async {
    var response = await httpGet(url,
        apiToken: apiToken, errorMessage: 'Could not load tasks');

    try {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      List<Task> tasks = [];
      for (var item in decoded['tasks']) {
        tasks.add(Task.fromMap(item));
      }
      return tasks;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

/// Update a task complete/incomplete state..
Future<void> toggleTask(String apiToken, Task task) async {
  var operation = task.completed ? 'complete' : 'incomplete';
  var url = _makeUrl('/tasks/${task.id}/$operation');

  return Future(() async {
    await httpPost(url,
        apiToken: apiToken, errorMessage: 'Could not update task');
  });
}

/// Create a task
Future<Task> createTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/add');

  return Future(() async {
    var response = await httpPost(url,
        apiToken: apiToken,
        body: task.toMap(),
        errorMessage: 'Could not create task');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Task.fromMap(decoded['task']);
  });
}

/// Delete a task
Future<void> deleteTask(String apiToken, Task task) async {
  var url = _makeUrl('/tasks/${task.id}/delete');

  return Future(() async {
    await httpPost(url,
        apiToken: apiToken, errorMessage: 'Could not delete task');
  });
}

/// Fetch a task by id
Future<Task> fetchTaskById(String apiToken, int id) async {
  var url = _makeUrl('/tasks/$id/view');

  return Future(() async {
    var response = await httpGet(url,
        apiToken: apiToken,
        errorMessage: 'Could not load tasks');

    try {
      var taskData = jsonDecode(utf8.decode(response.bodyBytes));
      return Task.fromMap(taskData['task']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

Future<ProjectWithTasks> fetchProjectBySlug(
    String apiToken, String slug) async {
  var url = _makeUrl('/projects/$slug');

  return Future(() async {
    var response = await httpGet(url,
        apiToken: apiToken,
        errorMessage: 'Could not load project');

    try {
      var data = jsonDecode(utf8.decode(response.bodyBytes));
      if (data['project'] != null && data['tasks'] != null) {
        var project = Project.fromMap(data['project']);
        List<Task> tasks = [];
        for (var item in data['tasks']) {
          // TODO do this on the server so that tasks are serialized consistently.
          item['project'] = {
            'slug': project.slug,
            'name': project.name,
            'color': project.color
          };

          tasks.add(Task.fromMap(item));
        }
        return ProjectWithTasks(
          project: project,
          tasks: tasks,
        );
      }
      throw Exception('Invalid response data received');
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

Future<List<Project>> fetchProjects(String apiToken) async {
  var url = _makeUrl('/projects');

  return Future(() async {
    var response = await httpGet(url,
        apiToken: apiToken,
        errorMessage: 'Could not load projects');

    try {
      var projectData = jsonDecode(utf8.decode(response.bodyBytes));
      List<Project> projects = [];
      for (var item in projectData['projects']) {
        projects.add(Project.fromMap(item));
      }
      return projects;
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

/// Create a project
Future<Project> createProject(String apiToken, Project project) async {
  var url = _makeUrl('/projects/add');

  return Future(() async {
    var response = await httpPost(url,
        apiToken: apiToken,
        body: project.toMap(),
        errorMessage: 'Could not create project');
    var decoded = jsonDecode(utf8.decode(response.bodyBytes));

    return Project.fromMap(decoded['project']);
  });
}
