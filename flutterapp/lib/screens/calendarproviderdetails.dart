import 'package:docket/models/calendarsource.dart';
import 'package:docket/theme.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/screens/calendarproviderdetails_view_model.dart';

class CalendarProviderDetailsScreen extends StatefulWidget {
  final CalendarProvider provider;

  const CalendarProviderDetailsScreen(this.provider, {super.key});

  @override
  State<CalendarProviderDetailsScreen> createState() => _CalendarProviderDetailsScreenState();
}

class _CalendarProviderDetailsScreenState extends State<CalendarProviderDetailsScreen> {
  late CalendarProviderDetailsViewModel viewmodel;

  @override
  void initState() {
    super.initState();
    viewmodel = Provider.of<CalendarProviderDetailsViewModel>(context, listen: false);
    viewmodel.setId(widget.provider.id);

    _refresh(viewmodel);
  }

  Future<void> _refresh(CalendarProviderDetailsViewModel viewmodel) {
    return viewmodel.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CalendarProviderDetailsViewModel>(builder: (context, viewmodel, child) {
      var theme = Theme.of(context);
      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        body = RefreshIndicator(
            onRefresh: () => _refresh(viewmodel),
            child: ListView(
              children: viewmodel.provider.sources
                  .map(
                    (source) => CalendarSourceItem(source: source, viewmodel: viewmodel),
                  )
                  .toList(),
            ));
      }

      return Scaffold(
        appBar: AppBar(
          backgroundColor: theme.colorScheme.primary,
          title: Text("${widget.provider.displayName} Calendar"),
        ),
        drawer: const AppDrawer(),
        body: body,
      );
    });
  }
}

enum Menu { sync, delete }

class CalendarSourceItem extends StatelessWidget {
  final CalendarSource source;
  final CalendarProviderDetailsViewModel viewmodel;

  const CalendarSourceItem({required this.source, required this.viewmodel, super.key});

  @override
  Widget build(BuildContext context) {
    var docketColors = getCustomColors(context);
    var itemColor = getProjectColor(source.color);
    return ListTile(
      // TODO make this an actionable button.
      leading: Icon(Icons.circle, color: itemColor, size: 12),
      title: Text(source.name),
      subtitle:
          Text('Last synced: ${source.lastSync ?? "Never Synced"}', style: TextStyle(color: docketColors.disabledText)),
      trailing: buildMenu(context)
    );
  }

  Widget buildMenu(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.sync: () => viewmodel.syncEvents(source),
        Menu.delete: () => viewmodel.removeSource(source),
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.sync,
          child: ListTile(
            leading: Icon(Icons.sync, color: customColors.actionComplete),
            title: const Text('Sync'),
          ),
        ),
        PopupMenuItem<Menu>(
          value: Menu.delete,
          child: ListTile(
            leading: Icon(Icons.delete, color: customColors.actionDelete),
            title: const Text('Delete'),
          ),
        ),
      ];
    });
  }
}
