import 'package:flutter/material.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/theme.dart';

class ProfileSettingsScreen extends StatefulWidget {
  const ProfileSettingsScreen({Key? key}) : super(key: key);

  @override
  State<ProfileSettingsScreen> createState() => _ProfileSettingsScreenState();
}

class _ProfileSettingsScreenState extends State<ProfileSettingsScreen> {
  @override
  Widget build(BuildContext context) {
      return Scaffold(
          appBar: AppBar(
            title: const Text('Settings'),
          ),
          drawer: const AppDrawer(),
          body: Padding(
            padding: EdgeInsets.all(space(2)),
            child: Column(children: [
              ProfileSettings()
            ])));
  }
}
