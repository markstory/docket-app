import 'dart:developer' as dev;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

import 'actions.dart' as actions;
import 'database.dart';
import 'routes.dart';
import 'theme.dart' as app_theme;
import 'providers/projects.dart';
import 'providers/tasks.dart';
import 'screens/calendarproviderdetails.dart';
import 'screens/calendarproviderlist.dart';
import 'screens/login.dart';
import 'screens/profilesettings.dart';
import 'screens/projectdetails.dart';
import 'screens/projectadd.dart';
import 'screens/projectarchive.dart';
import 'screens/projectedit.dart';
import 'screens/projectcompleted.dart';
import 'screens/today.dart';
import 'screens/taskadd.dart';
import 'screens/taskdetails.dart';
import 'screens/trashbin.dart';
import 'screens/upcoming.dart';
import 'screens/unknown.dart';
import 'viewmodels/login.dart';
import 'viewmodels/projectdetails.dart';
import 'viewmodels/projectarchive.dart';
import 'viewmodels/projectedit.dart';
import 'viewmodels/projectcompleted.dart';
import 'viewmodels/today.dart';
import 'viewmodels/taskdetails.dart';
import 'viewmodels/trashbin.dart';
import 'viewmodels/upcoming.dart';
import 'viewmodels/calendarproviderdetails.dart';
import 'viewmodels/calendarproviderlist.dart';
import 'viewmodels/userprofile.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final themeMode = await AdaptiveTheme.getThemeMode();
  final database = LocalDatabase.instance();

  // Load the access token if available.
  await database.apiToken.get();
  if (database.apiToken.hasToken) {
    await actions.updateTimezone(database.apiToken.token);
  } 

  await SentryFlutter.init(
    (options) => {
      options.dsn = 'https://43cccc99aabb4755bfa8ac28ed9e9992@o200338.ingest.sentry.io/5976713',
      options.tracesSampleRate = 0.2,
    },
    appRunner: () => runApp(EntryPoint(database: database, themeMode: themeMode)),
  );
}

class EntryPoint extends StatelessWidget {
  final LocalDatabase database;
  final Widget? child;
  final AdaptiveThemeMode? themeMode;
  final Map<String, WidgetBuilder>? routes;

  const EntryPoint({required this.database, this.child, this.routes, this.themeMode, super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<ProjectsProvider>(
            create: (_) => ProjectsProvider(database),
        ),
        ChangeNotifierProvider<LoginViewModel>(
            create: (_) => LoginViewModel(database),
        ),
        ChangeNotifierProvider<ProjectArchiveViewModel>(
          create: (_) => ProjectArchiveViewModel(database),
        ),
        ChangeNotifierProvider<ProjectCompletedViewModel>(
          create: (_) => ProjectCompletedViewModel(database),
        ),
        ChangeNotifierProvider<ProjectDetailsViewModel>(
          create: (_) => ProjectDetailsViewModel(database),
        ),
        ChangeNotifierProvider<ProjectEditViewModel>(
          create: (_) => ProjectEditViewModel(database),
        ),
        ChangeNotifierProvider<TaskDetailsViewModel>(
          create: (_) => TaskDetailsViewModel(database),
        ),
        ChangeNotifierProvider<TasksProvider>(
          create: (_) => TasksProvider(database),
        ),
        ChangeNotifierProvider<TodayViewModel>(
          create: (_) => TodayViewModel(database),
        ),
        ChangeNotifierProvider<TrashbinViewModel>(
          create: (_) => TrashbinViewModel(database),
        ),
        ChangeNotifierProvider<UpcomingViewModel>(
          create: (_) => UpcomingViewModel(database),
        ),
        ChangeNotifierProvider<UserProfileViewModel>(
          create: (_) => UserProfileViewModel(database),
        ),
        ChangeNotifierProvider<CalendarProviderListViewModel>(
          create: (_) => CalendarProviderListViewModel(database),
        ),
        ChangeNotifierProvider<CalendarProviderDetailsViewModel>(
          create: (_) => CalendarProviderDetailsViewModel(database),
        ),
      ],
      child: DocketApp(themeMode: themeMode, routes: routes, child: child),
    );
  }
}

class DocketApp extends StatelessWidget {
  final AdaptiveThemeMode? themeMode;
  final Widget? child;
  final Map<String, WidgetBuilder>? routes;

  const DocketApp({this.child, this.themeMode, this.routes, super.key});

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
              routes: routes ?? {},
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
              if (settings.name == Routes.calendarList) {
                return MaterialPageRoute(builder: (context) => const CalendarProviderListScreen());
              }
              if (settings.name == Routes.calendarDetails) {
                var args = settings.arguments as CalendarDetailsArguments;
                return MaterialPageRoute(builder: (context) => CalendarProviderDetailsScreen(args.provider));
              }

              return unknownScreen(context);
            },
          );
        });
  }
}
