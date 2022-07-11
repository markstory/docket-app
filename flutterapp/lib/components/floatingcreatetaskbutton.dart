import 'package:flutter/material.dart';

import 'package:docket/screens/taskadd.dart';

class FloatingCreateTaskButton extends StatelessWidget {
  const FloatingCreateTaskButton({super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);

    return FloatingActionButton(
      onPressed: () {
        Navigator.pushNamed(context, TaskAddScreen.routeName);
      },
      backgroundColor:theme.colorScheme.primary,
      child: Icon(Icons.add, color: theme.colorScheme.onPrimary),
    );
  }
}
