import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/theme.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/forms/profilesettings.dart';
import 'package:docket/viewmodels/userprofile.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({Key? key}) : super(key: key);

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  late Future<UserProfile> profile;

  @override
  void initState() {
    super.initState();
    var viewmodel = Provider.of<UserProfileViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<UserProfileViewModel>(builder: (context, viewmodel, child) {
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = SingleChildScrollView(padding: EdgeInsets.all(space(1)), 
          child: Column(children: [
            ProfileSettingsForm(
              userprofile: viewmodel.profile,
              onSave: (profile) {
                viewmodel.update(profile);
                AdaptiveTheme.of(context).setThemeMode(profile.themeMode);
                Navigator.of(context).pop();
              },
            )
          ]
        ));
      }

      return Scaffold(
          appBar: AppBar(
            title: const Text('Profile Settings'),
          ),
          drawer: const AppDrawer(),
          body: body,
        );
    });
  }
}
