import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/components/calendarprovideritem.dart';
import 'package:docket/screens/calendarproviderlist_view_model.dart';

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

    _refresh(viewmodel);
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
        body = RefreshIndicator(
            onRefresh: () => _refresh(viewmodel),
            child: ListView(
              children: viewmodel.providers
                  .map(
                    (provider) => CalendarProviderItem(provider: provider),
                  )
                  .toList(),
            ));
      }

      return Scaffold(
        appBar: AppBar(
          backgroundColor: theme.colorScheme.primary,
          title: const Text('Synced Calendars'),
        ),
        drawer: const AppDrawer(),
        body: body,
      );
    });
  }
}
