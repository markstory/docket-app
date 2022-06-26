import 'dart:developer' as developer;
import 'dart:convert';
import 'package:http/http.dart' as http;

import 'package:docket/models/apitoken.dart';
import 'package:docket/models/task.dart';
import 'package:docket/models/project.dart';

/// This needs to come from a props/config file but I don't know
/// how to do that yet.
const baseUrl = 'https://docket.mark-story.com';

var client = http.Client();

/// Reset the HTTP client to a new instance
void resetClient() {
  client = http.Client();
}

/// Perform a login request.
/// The entity returned contains an API token
/// that can be used until revoked serverside.
Future<ApiToken> doLogin(String email, String password) async {
  var url = Uri.parse('$baseUrl/mobile/login');
  developer.log('http.request url=$url');

  var body = {'email': email, 'password': password};

  return Future(() async {
    var response = await client.post(
      url,
      headers: {'Accept': 'application/json'},
      body: body
    );

    if (response.statusCode >= 400) {
      developer.log('Could not login. Login response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Login failed');
    }
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
  var url = Uri.parse('$baseUrl/tasks/today');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.get(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode > 200) {
      developer.log('Could not fetch today tasks. Response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Could not load tasks');
    }

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
  var url = Uri.parse('$baseUrl/tasks/upcoming');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.get(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode > 200) {
      developer.log('Could not fetch today tasks. Response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Could not load tasks');
    }

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
  var url = Uri.parse('$baseUrl/tasks/${task.id}/$operation');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.post(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode >= 400) {
      developer.log('Could not update task. Response: ${utf8.decode(response.bodyBytes)} ${response.statusCode}');
      throw Exception('Could not toggle task');
    }
  });
}

/// Delete a task
Future<void> deleteTask(String apiToken, Task task) async {
  var url = Uri.parse('$baseUrl/tasks/${task.id}/delete');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.post(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode >= 400) {
      developer.log('Could not delete task. Response: ${utf8.decode(response.bodyBytes)} $apiToken');
      throw Exception('Could not load delete task');
    }
  });
}

/// Fetch a task by id
Future<Task> fetchTaskById(String apiToken, int id) async {
  var url = Uri.parse('$baseUrl/tasks/$id/view');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.get(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode > 200) {
      developer.log('Could not fetch today tasks. Response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Could not load tasks');
    }

    try {
      var taskData = jsonDecode(utf8.decode(response.bodyBytes));
      return Task.fromMap(taskData['task']);
    } catch (e, stacktrace) {
      developer.log('Failed to decode ${e.toString()} $stacktrace');
      rethrow;
    }
  });
}

Future<ProjectWithTasks> fetchProjectBySlug(String apiToken, String slug) async {
  var url = Uri.parse('$baseUrl/projects/$slug');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.get(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode > 200) {
      developer.log('Could not fetch project by id. Response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Could not load project.');
    }

    try {
      var data = jsonDecode(utf8.decode(response.bodyBytes));
      if (data['project'] != null && data['tasks'] != null) {
        var project = Project.fromMap(data['project']);
        List<Task> tasks = [];
        for (var item in data['tasks']) {
          // TODO do this on the server so that tasks are serialized consistently.
          item['project'] = {'slug': project.slug, 'name': project.name, 'color': project.color};

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
  var url = Uri.parse('$baseUrl/projects');
  developer.log('http.request url=$url');

  return Future(() async {
    var response = await client.get(
      url,
      headers: {
        'Authorization': 'Bearer $apiToken',
        'Accept': 'application/json'
      }
    );

    if (response.statusCode > 200) {
      developer.log('Could not fetch projects. Response: ${utf8.decode(response.bodyBytes)}');
      throw Exception('Could not load projects.');
    }

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
