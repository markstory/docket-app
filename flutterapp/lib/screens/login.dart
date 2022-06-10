import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/actions.dart';
import 'package:docket/forms/login.dart';
import 'package:docket/model/session.dart';
import 'package:docket/screens/today.dart';

class LoginScreen extends StatelessWidget {
  static const routeName = '/login';

  const LoginScreen({super.key});

  Future<void> _handleSubmit(String email, String password, SessionModel session) async {
    try {
      // Do the login request and set the token to application state.
      var apiToken = await doLogin(email, password);
      debugPrint('$apiToken');
      session.set(apiToken.token);
    } catch (e) {
      throw Exception('Could not login');
    }
  }

  @override
  Widget build(BuildContext context) {
    // Build a Form widget using the _formKey created above.
    return Consumer<SessionModel>(
      builder: (context, session, child) {
        if (session.apiToken != null) {
          debugPrint('Doing redirect');
          // Then redirect to Today.
          Navigator.pushNamed(context, TodayScreen.routeName);
        }

        return Scaffold(
          appBar: AppBar(),
          body: Column(
            children: [
              const Text('Login to your Docket instance.'),
              Text('API token=${session.apiToken.toString()}'),
              LoginForm(onSubmit: (String? email, String? password) {
                if (email != null && password != null) {
                  _handleSubmit(email, password, session);
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
