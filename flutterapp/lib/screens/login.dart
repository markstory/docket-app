import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/forms/login.dart';
import 'package:docket/providers/session.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';

class LoginScreen extends StatelessWidget {
  const LoginScreen({super.key});

  Future<void> _handleSubmit(String email, String password, SessionProvider session) async {
    try {
      // Do the login request and set the token to application state.
      var apiToken = await actions.doLogin(email, password);
      session.saveToken(apiToken);
    } catch (e) {
      // Raise an error to the UI State
      throw Exception('Could not login');
    }
  }

  @override
  Widget build(BuildContext context) {
    // Build a Form widget using the _formKey created above.
    return Consumer<SessionProvider>(builder: (context, session, child) {
      return Scaffold(
          appBar: AppBar(),
          body: Padding(
              padding: EdgeInsets.all(space(2)),
              child: Column(children: [
                const Text('Login to your Docket instance'),
                LoginForm(onSubmit: (String? email, String? password) async {
                  if (email != null && password != null) {
                    try {
                      void complete() {
                        Navigator.pushNamed(context, Routes.today);
                      }

                      await _handleSubmit(email, password, session);
                      complete();
                    } catch (e) {
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.toString())));
                    }
                  }
                }),
              ])));
    });
  }
}

/// Render the child widget if we have a session token
/// available or render the login screen if we don't.
/// Because loading the session token is async we show a loading
/// indicator while loading is in progress.
class LoginRequired extends StatelessWidget {
  final Widget child;

  const LoginRequired({
    required this.child,
    super.key,
  });

  @override
  Widget build(BuildContext context) {
    return Consumer<SessionProvider>(builder: (context, session, _) {
      if (session.loading) {
        return const LoadingIndicator();
      }
      if (session.hasToken) {
        return child;
      }
      return const LoginScreen();
    });
  }
}
