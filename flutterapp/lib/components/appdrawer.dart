import 'package:docket/dialogs/confirmdelete.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/projectsorter.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodels/userprofile.dart';

class AppDrawer extends StatefulWidget {
  const AppDrawer({Key? key}) : super(key: key);

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {
  @override
  void initState() {
    super.initState();
    var viewmodel = Provider.of<UserProfileViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return Consumer<UserProfileViewModel>(builder: (context, viewmodel, child) {
      var navigator = Navigator.of(context);
      var modalRoute = ModalRoute.of(context);
      var currentRoute = modalRoute?.settings.name;

      return Drawer(
          child: ListView(shrinkWrap: true, padding: EdgeInsets.zero, children: [
        buildHeader(context, viewmodel),
        ListTile(
          onTap: () {
            navigator.pushNamed(Routes.today);
          },
          leading: Icon(Icons.today, color: customColors.dueToday),
          title: const Text('Today'),
          selected: Routes.activeRoute == Routes.today,
        ),
        ListTile(
          onTap: () {
            navigator.pushNamed(Routes.upcoming);
          },
          leading: Icon(Icons.calendar_today, color: customColors.dueTomorrow),
          title: const Text('Upcoming'),
          selected: Routes.activeRoute == Routes.upcoming,
        ),
        ListTile(
          title: Text('Projects', style: theme.textTheme.subtitle1),
        ),
        const ProjectSorter(),
        const Divider(),
        ListTile(
            leading: Icon(Icons.add, color: theme.colorScheme.primary),
            title: Text('Add Project', style: TextStyle(color: theme.colorScheme.primary)),
            selected: Routes.activeRoute == Routes.projectAdd,
            onTap: () {
              navigator.pushNamed(Routes.projectAdd);
            }),
        ListTile(
            leading: Icon(Icons.archive, color: customColors.dueNone),
            title: Text('Archived Projects', style: TextStyle(color: customColors.dueNone)),
            selected: Routes.activeRoute == Routes.projectArchive,
            onTap: () {
              navigator.pushNamed(Routes.projectArchive);
            }),
        ListTile(
            leading: Icon(Icons.sync, color: customColors.dueNone),
            title: Text('Calendar Sync', style: TextStyle(color: customColors.dueNone)),
            selected: Routes.activeRoute == Routes.calendarList,
            onTap: () {
              navigator.pushNamed(Routes.calendarList);
            }),
        ListTile(
            leading: Icon(Icons.delete, color: customColors.dueNone),
            title: Text('Trash Bin', style: TextStyle(color: customColors.dueNone)),
            selected: Routes.activeRoute == Routes.trashbin,
            onTap: () {
              navigator.pushNamed(Routes.trashbin);
            }),
        const Divider(),
        ListTile(
            leading: Icon(Icons.logout, color: customColors.dueNone),
            title: Text('Logout', style: TextStyle(color: customColors.dueNone)),
            onTap: () {
              showConfirmDelete(
                context: context,
                content: (
                  'Logging out will remove all data from this device. '
                  'Your data will still be stored in your docket account.'
                ),
                onConfirm: () async {
                  await viewmodel.logout();
                  navigator.pushNamed(Routes.login);
                });
            }),
      ]));
    });
  }

  Widget buildHeader(BuildContext context, UserProfileViewModel viewmodel) {
    if (viewmodel.loading) {
      return const DrawerHeader(child: Text('Docket'));
    }

    var profile = viewmodel.profile;
    var theme = Theme.of(context);
    var gravatarurl = 'https://www.gravatar.com/avatar/${profile.avatarHash}?s=50&default=retro';

    return UserAccountsDrawerHeader(
        decoration: BoxDecoration(
          color: theme.colorScheme.primary,
        ),
        accountEmail: Text(profile.email),
        accountName: Text(profile.name),
        currentAccountPicture: CircleAvatar(
          foregroundImage: NetworkImage(gravatarurl),
          backgroundColor: theme.colorScheme.surfaceTint,
        ),
        onDetailsPressed: () {
          Navigator.pushNamed(context, Routes.profileSettings);
        });
  }
}
