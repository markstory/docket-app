import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/forms/login.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/screens/today.dart';

class LoginScreen extends StatelessWidget {
  static const routeName = '/login';

  const LoginScreen({super.key});

  Future<void> _handleSubmit(String email, String password, SessionProvider session) async {
    try {
      // Do the login request and set the token to application state.
      var apiToken = await actions.doLogin(email, password);
      session.set(apiToken);
    } catch (e) {
      // Raise an error to the UI State
      throw Exception('Could not login');
    }
  }

  @override
  Widget build(BuildContext context) {
    // Build a Form widget using the _formKey created above.
    return Consumer<SessionProvider>(
      builder: (context, session, child) {
        return Scaffold(
          appBar: AppBar(),
          body: Column(
            children: [
              const Text('Login to your Docket instance.'),
              LoginForm(onSubmit: (String? email, String? password) async {
                if (email != null && password != null) {
                  try {
                    void complete() {
                     Navigator.pushNamed(context, TodayScreen.routeName);
                    }
                    await _handleSubmit(email, password, session);
                    complete();
                  } catch (e) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text(e.toString()))
                    );
                  }
                }
              }),
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
    return Consumer<SessionProvider>(
      builder: (context, session, _) {
        if (session.hasToken) {
          return child;
        }
        return const LoginScreen();
      }
    );
  }
}
