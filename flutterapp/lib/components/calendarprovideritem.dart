import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/dialogs/confirmdelete.dart';
import 'package:docket/models/calendarprovider.dart';
import 'package:docket/routes.dart';
import 'package:docket/theme.dart';
import 'package:docket/viewmodel/calendarproviderlist.dart';

class CalendarProviderItem extends StatelessWidget {
  final CalendarProvider provider;

  const CalendarProviderItem({required this.provider, super.key});

  @override
  Widget build(BuildContext context) {
    Widget icon = const Icon(Icons.link);
    if (provider.kind == 'google') {
      icon = const Image(image: AssetImage('assets/google-calendar.png'));
    }
    return ListTile(
      dense: true,
      contentPadding: EdgeInsets.all(space(1)),
      onTap: () {
        Navigator.pushNamed(context, Routes.calendarDetails, arguments: CalendarDetailsArguments(provider));
      },
      title: Text(provider.displayName),
      leading: icon,
      trailing: ProviderActions(provider: provider),
    );
  }
}

enum Menu { delete }

class ProviderActions extends StatelessWidget {
  final CalendarProvider provider;

  const ProviderActions({required this.provider, super.key});

  void handleDelete(context) {
    var viewmodel = Provider.of<CalendarProviderListViewModel>(context, listen: false);
    var messenger = ScaffoldMessenger.of(context);
    showConfirmDelete(
      context: context,
      content: 'Deleting this calendar account will remove all linked calendars.',
      onConfirm: () async {
        await viewmodel.delete(provider);
        messenger.showSnackBar(successSnackBar(context: context, text: 'Calendar provider deleted'));
      });
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(
      key: const ValueKey('provider-actions'),
      onSelected: (Menu item) {
        var actions = {
          Menu.delete: () => handleDelete(context),
        };
        actions[item]?.call();
      },
      itemBuilder: (BuildContext context) {
        return <PopupMenuEntry<Menu>>[
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
