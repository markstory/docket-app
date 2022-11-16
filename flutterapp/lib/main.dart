import 'dart:developer' as dev;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

import 'database.dart';
import 'routes.dart';
import 'theme.dart' as app_theme;
import 'providers/projects.dart';
import 'providers/session.dart';
import 'providers/tasks.dart';
import 'providers/userprofile.dart';
import 'screens/login.dart';
import 'screens/profilesettings.dart';
import 'screens/projectdetails.dart';
import 'screens/projectdetails_view_model.dart';
import 'screens/projectadd.dart';
import 'screens/projectarchive.dart';
import 'screens/projectarchive_view_model.dart';
import 'screens/projectedit.dart';
import 'screens/projectcompleted.dart';
import 'screens/projectcompleted_view_model.dart';
import 'screens/today.dart';
import 'screens/today_view_model.dart';
import 'screens/taskadd.dart';
import 'screens/taskdetails.dart';
import 'screens/trashbin.dart';
import 'screens/trashbin_view_model.dart';
import 'screens/upcoming.dart';
import 'screens/upcoming_view_model.dart';
import 'screens/unknown.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final themeMode = await AdaptiveTheme.getThemeMode();
  final dbHandler = LocalDatabase();

  await SentryFlutter.init(
    (options) => {
      options.dsn = 'https://43cccc99aabb4755bfa8ac28ed9e9992@o200338.ingest.sentry.io/5976713',
      options.tracesSampleRate = 0.2,
    },
    appRunner: () => runApp(EntryPoint(database: dbHandler, themeMode: themeMode)),
  );
}

class EntryPoint extends StatelessWidget {
  final LocalDatabase database;
  final Widget? child;
  final AdaptiveThemeMode? themeMode;

  const EntryPoint({required this.database, this.child, this.themeMode, super.key});

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
        ChangeNotifierProxyProvider<SessionProvider, UserProfileProvider>(
            create: (_) => UserProfileProvider(database, null),
            update: (_, session, provider) {
              provider!.setSession(session);
              return provider;
            }),
        ChangeNotifierProxyProvider<SessionProvider, TodayViewModel>(
          create: (_) => TodayViewModel(database, null),
          update: (_, session, viewmodel) {
            viewmodel!.setSession(session);
            return viewmodel;
          }),
        ChangeNotifierProxyProvider<SessionProvider, UpcomingViewModel>(
          create: (_) => UpcomingViewModel(database, null),
          update: (_, session, viewmodel) {
            viewmodel!.setSession(session);
            return viewmodel;
          }),
        ChangeNotifierProxyProvider<SessionProvider, ProjectArchiveViewModel>(
          create: (_) => ProjectArchiveViewModel(database, null),
          update: (_, session, viewmodel) {
            viewmodel!.setSession(session);
            return viewmodel;
          }),
        ChangeNotifierProxyProvider<SessionProvider, ProjectCompletedViewModel>(
          create: (_) => ProjectCompletedViewModel(database, null),
          update: (_, session, viewmodel) {
            viewmodel!.setSession(session);
            return viewmodel;
          }),
        ChangeNotifierProxyProvider<SessionProvider, TrashbinViewModel>(
          create: (_) => TrashbinViewModel(database, null),
          update: (_, session, viewmodel) {
            viewmodel!.setSession(session);
            return viewmodel;
          }),
      ],
      child: DocketApp(themeMode: themeMode, child: child),
    );
  }
}

class DocketApp extends StatelessWidget {
  final AdaptiveThemeMode? themeMode;
  final Widget? child;

  const DocketApp({this.child, this.themeMode, super.key});

  Route unknownScreen(BuildContext context) {
    return MaterialPageRoute(builder: (context) => const UnknownScreen());
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveTheme(
        light: app_theme.lightTheme,
        dark: app_theme.darkTheme,
        initial: themeMode ?? AdaptiveThemeMode.system,
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
            navigatorObservers: [SentryNavigatorObserver()],
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
              // Trashbin
              if (settings.name == Routes.trashbin) {
                return MaterialPageRoute(builder: (context) => const TrashbinScreen());
              }
              // Task Add
              if (settings.name == Routes.taskAdd) {
                final args = settings.arguments as TaskAddArguments;
                return MaterialPageRoute(builder: (context) => LoginRequired(child: TaskAddScreen(task: args.task)));
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

              // Project Detailed View.
              if (settings.name == Routes.projectDetails) {
                var args = settings.arguments as ProjectDetailsArguments;

                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectDetailsScreen(args.project)));
              }
              if (settings.name == Routes.projectEdit) {
                var args = settings.arguments as ProjectDetailsArguments;

                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectEditScreen(args.project)));
              }
              if (settings.name == Routes.projectCompleted) {
                var args = settings.arguments as ProjectDetailsArguments;

                return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectCompletedScreen(args.project)));
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
              // Profile settings
              if (settings.name == Routes.profileSettings) {
                return MaterialPageRoute(builder: (context) => const ProfileSettingsScreen());
              }

              return unknownScreen(context);
            },
          );
        });
  }
}
