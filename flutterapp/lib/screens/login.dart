import 'package:docket/components/iconsnackbar.dart';
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
      var theme = Theme.of(context);
      return Scaffold(
        body: Column(
          children: [
              Container(
                padding: EdgeInsets.fromLTRB(space(2), space(4), space(2), space(2)),
                alignment: Alignment.centerLeft,
                color: theme.colorScheme.primary,
                child: Row(
                  children: [
                    const Image(image: AssetImage('assets/docket-logo.png'), width: 64, height: 64),
                    SizedBox(width: space(2)),
                    Text('Login', style: theme.textTheme.headline5!.copyWith(color: theme.colorScheme.onPrimary)),
                  ])
              ),
              Padding(
                padding: EdgeInsets.symmetric(horizontal: space(2), vertical: space(2)),
                child: LoginForm(onSubmit: (String? email, String? password) async {
                  var navigator = Navigator.of(context);
                  var messenger = ScaffoldMessenger.of(context);
                  var theme = Theme.of(context);

                  await viewmodel.login(email, password);

                  var error = viewmodel.loginError;
                  if (error != null) {
                    messenger.showSnackBar(errorSnackBar(text: error.toString(), theme: theme));
                  } else {
                    await navigator.pushNamed(Routes.today);
                  }
                }),
              )
          ])
        );
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
