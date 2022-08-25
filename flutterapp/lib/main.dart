import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:adaptive_theme/adaptive_theme.dart';

import 'database.dart';
import 'routes.dart';
import 'theme.dart' as app_theme;
import 'providers/projects.dart';
import 'providers/session.dart';
import 'providers/tasks.dart';
import 'screens/login.dart';
import 'screens/projectdetails.dart';
import 'screens/projectadd.dart';
import 'screens/projectarchive.dart';
import 'screens/projectedit.dart';
import 'screens/today.dart';
import 'screens/taskadd.dart';
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
  final Widget? child;

  const EntryPoint({required this.database, this.child, super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<SessionProvider>(create: (_) => SessionProvider(database)),
        ChangeNotifierProxyProvider<SessionProvider, ProjectsProvider>(
            create: (_) => ProjectsProvider(database, null),
            update: (_, session, provider) {
              provider!.setSession(session);
              return provider;
            }),
        ChangeNotifierProxyProvider<SessionProvider, TasksProvider>(
            create: (_) => TasksProvider(database, null),
            update: (_, session, provider) {
              provider!.setSession(session);
              return provider;
            }),
      ],
      child: DocketApp(child: child),
    );
  }
}

class DocketApp extends StatelessWidget {
  // TODO implement theme saving with profile/settings.
  final AdaptiveThemeMode? savedThemeMode;
  final Widget? child;

  const DocketApp({this.savedThemeMode, this.child, super.key});

  Route unknownScreen(BuildContext context) {
    return MaterialPageRoute(builder: (context) => const UnknownScreen());
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveTheme(
        light: app_theme.lightTheme,
        dark: app_theme.darkTheme,
        initial: savedThemeMode ?? AdaptiveThemeMode.light,
        builder: (theme, darkTheme) {
          if (child != null) {
            return MaterialApp(
              theme: theme,
              darkTheme: darkTheme,
              home: child,
            );
          }
          return MaterialApp(
            theme: theme,
            darkTheme: darkTheme,
            onGenerateRoute: (settings) {
              // The named route and the default application route go to Today.
              // Should the user not have a session they are directed to Login.
              if (settings.name == Routes.today || settings.name == '/') {
                return MaterialPageRoute(builder: (context) => const LoginRequired(child: TodayScreen()));
              }
              // Upcoming tasks in the next 28 days.
              if (settings.name == Routes.upcoming) {
                return MaterialPageRoute(builder: (context) => const LoginRequired(child: UpcomingScreen()));
              }
              // Task Add
              if (settings.name == Routes.taskAdd) {
                final args = settings.arguments as TaskAddArguments;
                return MaterialPageRoute(builder: (context) => LoginRequired(child: TaskAddScreen(task: args.task)));
              }
              // Project Detailed View.
              if (settings.name == Routes.projectDetails) {
                var args = settings.arguments as ProjectDetailsArguments;

                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectDetailsScreen(args.project)));
              }
              // Task Detailed View.
              if (settings.name == Routes.taskDetails) {
                final args = settings.arguments as TaskDetailsArguments;
                var task = args.task;
                if (task.id == null) {
                  return unknownScreen(context);
                }

                return MaterialPageRoute(builder: (context) => LoginRequired(child: TaskDetailsScreen(task)));
              }

              if (settings.name == Routes.projectEdit) {
                var args = settings.arguments as ProjectEditArguments;
                var project = args.project;

                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectEditScreen(project)));
              }
              if (settings.name == Routes.projectAdd) {
                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectAddScreen()));
              }
              if (settings.name == Routes.projectArchive) {
                return MaterialPageRoute(builder: (context) => const LoginRequired(child: ProjectArchiveScreen()));
              }

              // Login
              if (settings.name == Routes.login) {
                return MaterialPageRoute(builder: (context) => const LoginScreen());
              }

              return unknownScreen(context);
            },
          );
        });
  }
}
