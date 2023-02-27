import 'package:flutter/material.dart';

import 'package:docket/theme.dart';

class EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  final String? text;
  const EmptyState({required this.icon, required this.title, this.text, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = getCustomColors(context);
    Widget textBody = const SizedBox();
    if (text != null) {
      textBody = Padding(
        padding: EdgeInsets.symmetric(horizontal: space(1)),
        child: const Text('When you delete tasks they will go here for 14 days. '
          'After that time they will be deleted permanently.',
          textAlign: TextAlign.center,
      ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, size: 84, color: customColors.dueNone),
        const SizedBox(width: 0, height: 12),
        Text(title, style: theme.textTheme.titleLarge, textAlign: TextAlign.center),
        const SizedBox(width: 0, height: 12),
        textBody,
    ]);
  }
}
