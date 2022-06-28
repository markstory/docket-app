import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:adaptive_theme/adaptive_theme.dart';

import 'database.dart';
import 'theme.dart' as app_theme;
import 'providers/projects.dart';
import 'providers/session.dart';
import 'providers/tasks.dart';
import 'screens/login.dart';
import 'screens/projectdetails.dart';
import 'screens/today.dart';
import 'screens/taskdetails.dart';
import 'screens/upcoming.dart';
import 'screens/unknown.dart';

void main() {
  // TODO implement theme saving with profile/settings.
  // WidgetsFlutterBinding.ensureInitialized();
  // final savedThemeMode = await AdaptiveTheme.getThemeMode();

  final dbHandler = LocalDatabase();

  runApp(EntryPoint(database: dbHandler));
}

class EntryPoint extends StatelessWidget {
  final LocalDatabase database;

  const EntryPoint({required this.database, super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ListenableProvider<SessionProvider>(
          create: (_) => SessionProvider(database)
        ),
        ListenableProvider<TasksProvider>(
          create: (_) => TasksProvider(database)
        ),
        ListenableProvider<ProjectsProvider>(
          create: (_) => ProjectsProvider(database)
        ),
      ],
      child: const DocketApp()
    );
  }
}

class DocketApp extends StatelessWidget {
  // TODO implement theme saving with profile/settings.
  final AdaptiveThemeMode? savedThemeMode;

  const DocketApp({this.savedThemeMode, super.key});

  @override
  Widget build(BuildContext context) {
    return AdaptiveTheme(
      light: app_theme.lightTheme,
    dark: app_theme.darkTheme,
      initial: savedThemeMode ?? AdaptiveThemeMode.light,
      builder: (theme, darkTheme) {
        return MaterialApp(
          theme: theme,
          darkTheme: darkTheme,
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

            // Task Detailed View.
            if (
              uri.pathSegments.length == 3 && 
              uri.pathSegments[0] == 'tasks' &&
              uri.pathSegments[2] == 'view'
            ) {
              var id = int.parse(uri.pathSegments[1]);
              return MaterialPageRoute(builder: (context) => LoginRequired(child: TaskDetailsScreen(id)));
            }

            return MaterialPageRoute(builder: (context) => const UnknownScreen());
          },
        );
      }
    );
  }
}
