import 'package:adaptive_theme/adaptive_theme.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/theme.dart';
import 'package:docket/models/userprofile.dart';
import 'package:docket/providers/userprofile.dart';
import 'package:docket/forms/profilesettings.dart';

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
    _refresh();
  }

  Future<UserProfile> _refresh() async {
    var provider = Provider.of<UserProfileProvider>(context, listen: false);
    return provider.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<UserProfileProvider>(builder: (context, provider, child) {
      return Scaffold(
          appBar: AppBar(
            title: const Text('Profile Settings'),
          ),
          drawer: const AppDrawer(),
          body: FutureBuilder<UserProfile>(
            future: provider.get(),
            builder: (BuildContext context, snapshot) {
              if (snapshot.hasError) {
                return const Card(child: Text('Something terrible has happened.'));
              }
              var profile = snapshot.data;
              if (profile == null) {
                return const LoadingIndicator();
              }
              return SingleChildScrollView(padding: EdgeInsets.all(space(1)), 
                child: Column(children: [
                  ProfileSettingsForm(
                    userprofile: profile,
                    onSave: (profile) {
                      provider.update(profile);
                      AdaptiveTheme.of(context).setThemeMode(profile.themeMode);
                      Navigator.of(context).pop();
                    },
                  )
                ]
              ));
            }
          ),
        );
    });
  }
}
