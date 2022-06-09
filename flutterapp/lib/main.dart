import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'model/session.dart';
import 'screens/login.dart';
import 'screens/projectdetails.dart';
import 'screens/today.dart';
import 'screens/upcoming.dart';
import 'screens/unknown.dart';

void main() {
  runApp(
    ChangeNotifierProvider(
      create: (context) => SessionModel(),
      child: const DocketApp()
    )
  );
}

class DocketApp extends StatelessWidget {
  const DocketApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      onGenerateRoute: (settings) {
        // The named route and the default application route go to Today.
        // Should the user not have a session they are directed to Login.
        if (settings.name == TodayScreen.routeName || settings.name == '/') {
          return MaterialPageRoute(builder: (context) => const LoginRequired(child: TodayScreen()));
        }
        // Upcoming tasks in the next 28 days.
        if (settings.name == UpcomingScreen.routeName) {
          return MaterialPageRoute(builder: (context) => const LoginRequired(child: UpcomingScreen()));
        }
        // Login
        if (settings.name == LoginScreen.routeName) {
          return MaterialPageRoute(builder: (context) => const LoginScreen());
        }
        // Project Detailed View.
        var uri = Uri.parse(settings.name.toString());
        if (uri.pathSegments.length == 2 && uri.pathSegments[0] == 'projects') {
          var slug = uri.pathSegments[1].toString();
          return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectDetailsScreen(slug)));
        }

        return MaterialPageRoute(builder: (context) => const UnknownScreen());
      },
    );
  }
}
