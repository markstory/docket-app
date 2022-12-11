import 'package:docket/theme.dart';
import 'package:flutter/material.dart';

import 'package:docket/routes.dart';
import 'package:docket/models/calendarprovider.dart';

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

  void handleDelete() {
    // TODO implement with confirm.
    // see SubtaskItem for an example of confirm and delete.
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = theme.extension<DocketColors>()!;

    return PopupMenuButton<Menu>(onSelected: (Menu item) {
      var actions = {
        Menu.delete: handleDelete,
      };
      actions[item]?.call();
    }, itemBuilder: (BuildContext context) {
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
