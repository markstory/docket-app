import 'package:docket/components/loadingindicator.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/providers/session.dart';
import 'package:docket/screens/today.dart';

class BootScreen extends StatelessWidget {
  const BootScreen({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Consumer<SessionProvider>(
      builder: (context, session, child) {
        if (session.hasToken) {
          // Then redirect to Today.
          Navigator.pushNamed(context, TodayScreen.routeName);
        }
        // TODO make this less ugly
        return Scaffold(
          appBar: AppBar(),
          body: Column(
            children: const [
              Text('Docket'),
              LoadingIndicator(),
            ]
          )
        );
      }
    );
  }
}
