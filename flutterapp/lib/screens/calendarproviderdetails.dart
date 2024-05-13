import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/dialogs/confirmdelete.dart';
import 'package:docket/models/calendarsource.dart';
import 'package:docket/theme.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/appdrawer.dart';
import 'package:docket/components/loadingindicator.dart';
import 'package:docket/formatters.dart' as formatters;
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/viewmodels/calendarproviderdetails.dart';

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
    viewmodel.loadData();
  }

  Future<void> _refresh(CalendarProviderDetailsViewModel viewmodel) {
    return viewmodel.refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CalendarProviderDetailsViewModel>(builder: (context, viewmodel, child) {
      var theme = Theme.of(context);
      var customColors = getCustomColors(context);

      Widget body;
      if (viewmodel.loading) {
        body = const LoadingIndicator();
      } else {
        List<Widget> items = [];
        for (var source in viewmodel.provider.sources) {
          items.add(CalendarSourceItem(source: source, viewmodel: viewmodel));
        }
        if (viewmodel.provider.brokenAuth) {
          items.insert(0, ListTile(
            leading: Icon(Icons.warning_outlined, color: customColors.actionDelete),
            title: const Text(
              'This calendar account has been disconnected in the provider. '
              'Re-link this account to sync calendar data.'
            ),
          ));
        }

        body = RefreshIndicator(
            onRefresh: () => _refresh(viewmodel),
            child: ListView(
              children: items,
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

enum Menu { link, sync, delete, unlink }

class CalendarSourceItem extends StatelessWidget {
  final CalendarSource source;
  final CalendarProviderDetailsViewModel viewmodel;

  const CalendarSourceItem({required this.source, required this.viewmodel, super.key});

  @override
  Widget build(BuildContext context) {
    var docketColors = getCustomColors(context);

    var lastSync = 'Never synced';
    var syncTime = source.lastSync;
    if (syncTime != null) {
      lastSync = formatters.timeAgo(syncTime);
    }

    late Widget leading;
    if (source.synced) {
      leading = CalendarColourPicker(
        key: const ValueKey("source-color"),
        color: source.color,
        onChanged: (color) async {
          var messenger = ScaffoldMessenger.of(context);
          var snackbar = successSnackBar(context: context, text: "Calendar updated");

          source.color = color;
          await viewmodel.updateSource(source);
          messenger.showSnackBar(snackbar);
        });
    } else {
      leading = Padding(
        padding: const EdgeInsets.fromLTRB(0, 13, 30, 10),
        child: Icon(Icons.circle, color: docketColors.disabledText, size: 12),
      );
    }

    return ListTile(
        leading: leading,
        title: Text(source.name),
        subtitle: Text(
            'Last synced: $lastSync',
            style: TextStyle(color: docketColors.disabledText)),
        trailing: buildMenu(context));
  }

  Widget buildMenu(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(
      key: const ValueKey('source-actions'),
      onSelected: (Menu item) {
      var actions = {
        Menu.link: () async {
          var messenger = ScaffoldMessenger.of(context);
          await viewmodel.linkSource(source);
          messenger.showSnackBar(successSnackBar(context: context, text: "Calendar linked"));
        },
        Menu.unlink: () async {
          var messenger = ScaffoldMessenger.of(context);
          await viewmodel.unlinkSource(source);
          messenger.showSnackBar(successSnackBar(context: context, text: "Calendar unlinked"));
        },
        Menu.sync: () async {
          var messenger = ScaffoldMessenger.of(context);
          await viewmodel.syncEvents(source);
          messenger.showSnackBar(successSnackBar(context: context, text: "Calendar refreshed"));
        },
        Menu.delete: () async {
          showConfirmDelete(
              content: "Are you sure you want stop syncing and unlink this calendar?",
              context: context,
              onConfirm: () async {
                var messenger = ScaffoldMessenger.of(context);
                await viewmodel.removeSource(source);
                messenger.showSnackBar(successSnackBar(context: context, text: "Calendar unlinked"));
              });
        }
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
      List<PopupMenuEntry<Menu>> items = [];
      if (source.synced) {
        items.add(
          PopupMenuItem<Menu>(
            value: Menu.sync,
            child: ListTile(
              leading: Icon(Icons.sync, color: customColors.actionComplete),
              title: const Text('Sync'),
            ),
          )
        );
        items.add(
          PopupMenuItem<Menu>(
            value: Menu.unlink,
            child: ListTile(
              leading: Icon(Icons.link_off, color: customColors.actionEdit),
              title: const Text('Sync'),
            ),
          )
        );
        items.add(
          PopupMenuItem<Menu>(
            value: Menu.delete,
            child: ListTile(
              leading: Icon(Icons.delete, color: customColors.actionDelete),
              title: const Text('Delete'),
            ),
          )
        );
      } else {
        items.add(
          PopupMenuItem<Menu>(
            value: Menu.link,
            child: ListTile(
              leading: Icon(Icons.link, color: theme.colorScheme.primary),
              title: const Text('Link'),
            ),
          )
        );
      }
      return items;
    });
  }
}

class CalendarColourPicker extends StatelessWidget {
  final int color;
  final void Function(int color) onChanged;

  const CalendarColourPicker({required this.color, required this.onChanged, super.key});

  @override
  Widget build(BuildContext context) {
    return DropdownButton<int>(
      key: const ValueKey('source-color'),
      value: color,
      onChanged: (int? color) {
        if (color == null) {
          return;
        }
        onChanged(color);
      },
      items: getProjectColors().map((item) {
        var isSelected = findProjectColor(color) != null;

        return DropdownMenuItem(
            key: ValueKey('color-${item.name}'),
            value: item.id,
            child: Row(children: [
              Icon(Icons.circle, color: item.color, size: 12),
              SizedBox(width: space(1)),
              isSelected ? const Text('') : Text(item.name),
            ]));
      }).toList(),
    );
  }
}
