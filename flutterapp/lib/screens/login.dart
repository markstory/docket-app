import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:http/http.dart' as http;

import '../forms/login.dart';
import '../model/session.dart';
import 'today.dart';

class LoginScreen extends StatelessWidget {
  static const routeName = '/login';

  const LoginScreen({super.key});

  Future<void> _handleSubmit(String email, String password, SessionModel session) async {
    var url = Uri.parse('https://docket.mark-story.com/mobile/login');
    var body = {'email': email, 'password': password};
    var response = await http.post(url, body: body);
    if (response.statusCode < 400) {
      var decoded = jsonDecode(utf8.decode(response.bodyBytes)) as Map;
      var token = decoded['apiToken']['token'];

      // Update application session provider.
      session.set(token);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Build a Form widget using the _formKey created above.
    return Consumer<SessionModel>(
      builder: (context, session, child) {
        return Scaffold(
          body: Column(
            children: [
              const Text('Login to your Docket instance.'),
              LoginForm(onSubmit: (email, password) => _handleSubmit(email, password, session)),
              ElevatedButton(
                child: const Text('Log in'),
                onPressed: () {
                  // Do HTTP request

                  // Handle success and update the session model.
                  // Set global state for login being active.

                  // Then redirect to Today.
                  Navigator.pushNamed(context, TodayScreen.routeName);
                },
              )
            ]
          )
        );
      }
    );
  }
}


class LoginRequired extends StatelessWidget {
  final Widget child;

  const LoginRequired({
    Key? key,
    required this.child,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Consumer<SessionModel>(
      builder: (context, session, _) {
        if (session.apiToken != null) {
          return child;
        }
        return const LoginScreen();
      }
    );
  }
}
