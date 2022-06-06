import 'package:flutter/material.dart';

import 'today.dart';
import 'login.dart';

class LoginScreen extends StatelessWidget {
  static const routeName = '/login';

  const LoginScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Column(
        children: [
          const Text('Login to your Docket instance.'),
          ElevatedButton(
            child: const Text('Log in'),
            onPressed: () {
              // Set global state for login being active.
              Navigator.pushNamed(context, TodayScreen.routeName);
            },
          )
        ]
      )
    );
  }
}


class LoginRequired extends StatefulWidget {
  final Widget child;

  const LoginRequired({
    Key? key,
    required this.child,
  }) : super(key: key);

  @override
  State<LoginRequired> createState() => _LoginRequiredState(child);
}

class _LoginRequiredState extends State<LoginRequired> {

  final Widget child;

  bool _isLoggedIn = true;

  _LoginRequiredState(this.child);

  @override
  Widget build(BuildContext context) {
    if (_isLoggedIn) {
      return child;
    }
    return const LoginScreen();
  }
}
