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
import 'package:docket/viewmodel/calendarproviderdetails.dart';

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

enum Menu { link, sync, delete }

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

    return ListTile(
        leading: CalendarColourPicker(
            color: source.color,
            onChanged: (color) async {
              var messenger = ScaffoldMessenger.of(context);

              source.color = color;
              await viewmodel.updateSource(source);
              messenger.showSnackBar(successSnackBar(context: context, text: "Calendar updated"));
            }),
        title: Text(source.name),
        subtitle: Text('Last synced: $lastSync',
            style: TextStyle(color: docketColors.disabledText)),
        trailing: buildMenu(context));
  }

  Widget buildMenu(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.link: () async {
          var messenger = ScaffoldMessenger.of(context);
          await viewmodel.linkSource(source);
          messenger.showSnackBar(successSnackBar(context: context, text: "Calendar linked"));
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
      return <PopupMenuEntry<Menu>>[
        PopupMenuItem<Menu>(
          value: Menu.sync,
          child: ListTile(
            leading: Icon(Icons.sync, color: customColors.actionComplete),
            title: const Text('Sync'),
          ),
        ),
        source.isLinked
            ? PopupMenuItem<Menu>(
                value: Menu.delete,
                child: ListTile(
                  leading: Icon(Icons.delete, color: customColors.actionDelete),
                  title: const Text('Delete'),
                ),
              )
            : PopupMenuItem<Menu>(
                value: Menu.link,
                child: ListTile(
                  leading: Icon(Icons.link, color: theme.colorScheme.primary),
                  title: const Text('Link'),
                ),
              )
      ];
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
      value: color,
      onChanged: (int? color) {
        if (color == null) {
          return;
        }
        onChanged(color);
      },
      items: getProjectColors().map((item) {
        var selectedColor = findProjectColor(color);
        var isSelected = selectedColor != null;

        return DropdownMenuItem(
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
