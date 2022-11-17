import 'package:docket/providers/userprofile.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/projectsorter.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';

class AppDrawer extends StatefulWidget {
  const AppDrawer({Key? key}) : super(key: key);

  @override
  State<AppDrawer> createState() => _AppDrawerState();
}

class _AppDrawerState extends State<AppDrawer> {

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return Consumer<UserProfileProvider>(
      builder: (context, provider, child) {
        return FutureBuilder<UserProfile>(
            future: provider.get(),
            builder: (context, snapshot) {
              return Drawer(
                  child: ListView(shrinkWrap: true, padding: EdgeInsets.zero, children: [
                buildHeader(context, snapshot),
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
                    leading: Icon(Icons.delete, color: customColors.dueNone),
                    title: Text('Trash Bin', style: TextStyle(color: customColors.dueNone)),
                    onTap: () {
                      Navigator.pushNamed(context, Routes.trashbin);
                    }),
              ]));
            });
      },
    );
  }

  Widget buildHeader(BuildContext context, AsyncSnapshot<UserProfile> snapshot) {
    var profile = snapshot.data;
    if (snapshot.hasError || profile == null) {
      return const DrawerHeader(child: Text('Docket'));
    }
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
