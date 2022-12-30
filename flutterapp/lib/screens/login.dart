import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/forms/login.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/login.dart';

class LoginScreen extends StatelessWidget {
  const LoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // Build a Form widget using the _formKey created above.
    return Consumer<LoginViewModel>(builder: (context, viewmodel, child) {
      return Scaffold(
          appBar: AppBar(),
          body: Padding(
              padding: EdgeInsets.all(space(2)),
              child: Column(children: [
                const Text('Login to your Docket instance'),
                LoginForm(onSubmit: (String? email, String? password) async {
                  var navigator = Navigator.of(context);
                  var messenger = ScaffoldMessenger.of(context);

                  await viewmodel.login(email, password);

                  var error = viewmodel.loginError;
                  if (error != null) {
                    messenger.showSnackBar(SnackBar(content: Text(error)));
                  } else {
                    await navigator.pushNamed(Routes.today);
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
    return Consumer<LoginViewModel>(builder: (context, viewmodel, _) {
      if (viewmodel.hasToken) {
        return child;
      }
      return const LoginScreen();
    });
  }
}
