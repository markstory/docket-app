import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:docket/actions.dart' as actions;
import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/calendarprovideritem.dart';
import 'package:docket/viewmodels/calendarproviderlist.dart';
import 'package:docket/theme.dart';

class CalendarProviderListScreen extends StatefulWidget {
  const CalendarProviderListScreen({super.key});

  @override
  State<CalendarProviderListScreen> createState() => _CalendarProviderListScreenState();
}

class _CalendarProviderListScreenState extends State<CalendarProviderListScreen> {
  late CalendarProviderListViewModel viewmodel;

  @override
  void initState() {
    super.initState();

    viewmodel = Provider.of<CalendarProviderListViewModel>(context, listen: false);
    viewmodel.loadData();
  }

  Future<void> _refresh(CalendarProviderListViewModel viewmodel) {
    return viewmodel.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CalendarProviderListViewModel>(builder: (context, viewmodel, child) {
      var theme = Theme.of(context);
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        List<Widget> children = [
          Padding(
              padding: EdgeInsets.all(space(2)),
              child: const Text('Events from linked calendars will be displayed in "today" and "upcoming" views.')),
        ];
        children.addAll(viewmodel.providers.map((provider) => CalendarProviderItem(provider: provider)).toList());

        body = RefreshIndicator(
            onRefresh: () => _refresh(viewmodel),
            child: ListView(
              children: children,
            ));
      }

      return Scaffold(
        appBar: AppBar(
          backgroundColor: theme.colorScheme.primary,
          title: const Text('Synced Calendars'),
          actions: [
            IconButton(
              onPressed: () async {
                // Might want to use https://pub.dev/packages/flutter_inappwebview
                // This would make it easier for this flutter application to read
                // state out of the webview. Going down this path would also
                // require adding small amount of JS to the 'complete' template
                // to call the reload method on the viewmodel.
                var uri = actions.googleAuthorizeUri();
                if (await canLaunchUrl(uri)) {
                  await launchUrl(uri);
                }
                viewmodel.expire();
              },
              icon: const Icon(Icons.add)
            )
          ]
        ),
        drawer: const AppDrawer(),
        body: body,
      );
    });
  }
}
