import 'dart:developer' as developer;
import 'dart:convert';
import 'package:http/http.dart' as http;

/// This needs to come from a props/config file but I don't know
/// how to do that yet.
const baseUrl = 'https://docket.mark-story.com';

class ApiToken {
  final String token;
  final String lastUsed;

  ApiToken({required this.token, required this.lastUsed});

  factory ApiToken.fromJson(Map<String, dynamic> json) {
    return ApiToken(
      token: json['token'],
      lastUsed: json['last_used'],
    );
  }
}


/// Perform a login request.
/// The entity returned contains an API token
/// that can be used until revoked serverside.
Future<ApiToken> doLogin(String email, String password) async {
  var url = Uri.parse('$baseUrl/mobile/login');
  developer.log('http.request url=$url');

  var body = {'email': email, 'password': password};

  return Future(() async {
    var response = await http.post(url, body: body);
    if (response.statusCode >= 400) {
      developer.log('Could not login. Login response: ${response.bodyBytes}');
      throw Exception('Login failed');
    }
    developer.log('login complete');

    var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
    return ApiToken.fromJson(decoded['apiToken']);
  });
}
