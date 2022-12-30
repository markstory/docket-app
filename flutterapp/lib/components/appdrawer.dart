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
      return Drawer(
          child: ListView(shrinkWrap: true, padding: EdgeInsets.zero, children: [
        buildHeader(context, viewmodel),
        ListTile(
          onTap: () {
            Navigator.pushNamed(context, '/tasks/today');
          },
          leading: Icon(Icons.today, color: customColors.dueToday),
          title: const Text('Today'),
        ),
        ListTile(
          onTap: () {
            Navigator.pushNamed(context, '/tasks/upcoming');
          },
          leading: Icon(Icons.calendar_today, color: customColors.dueTomorrow),
          title: const Text('Upcoming'),
        ),
        ListTile(
          title: Text('Projects', style: theme.textTheme.subtitle1),
        ),
        const ProjectSorter(),
        const Divider(),
        ListTile(
            leading: Icon(Icons.add, color: theme.colorScheme.primary),
            title: Text('Add Project', style: TextStyle(color: theme.colorScheme.primary)),
            onTap: () {
              Navigator.pushNamed(context, Routes.projectAdd);
            }),
        ListTile(
            leading: Icon(Icons.archive, color: customColors.dueNone),
            title: Text('Archived Projects', style: TextStyle(color: customColors.dueNone)),
            onTap: () {
              Navigator.pushNamed(context, Routes.projectArchive);
            }),
        ListTile(
            leading: Icon(Icons.sync, color: customColors.dueNone),
            title: Text('Calendar Sync', style: TextStyle(color: customColors.dueNone)),
            onTap: () {
              Navigator.pushNamed(context, Routes.calendarList);
            }),
        ListTile(
            leading: Icon(Icons.delete, color: customColors.dueNone),
            title: Text('Trash Bin', style: TextStyle(color: customColors.dueNone)),
            onTap: () {
              Navigator.pushNamed(context, Routes.trashbin);
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
        currentAccountPicture: CircleAvatar(foregroundImage: NetworkImage(gravatarurl)),
        onDetailsPressed: () {
          Navigator.pushNamed(context, Routes.profileSettings);
        });
  }
}
