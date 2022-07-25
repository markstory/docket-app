import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

import 'package:docket/components/dueon.dart';
import 'package:docket/dialogs/changedueon.dart';

/// Form layout widget.
/// Includes a leading element that is expected to be ~18px wide
/// Generally an icon but can also be an interactive wiget like a checkbox.
class FormIconRow extends StatelessWidget {
  final Widget child;
  final Widget? icon;

  const FormIconRow({this.icon, required this.child, super.key});

  @override
  Widget build(BuildContext context) {
    late Widget iconWidget;
    if (icon != null) {
      iconWidget = Padding(padding: EdgeInsets.fromLTRB(0, space(1), space(2), 0), child: icon);
    } else {
      iconWidget = const SizedBox(width: 34);
    }

    return Container(
      padding: EdgeInsets.all(space(1)),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          iconWidget,
          Expanded(child: child),
        ]
      )
    );
  }
}

/// Form widget for updating the dueOn attribute of a task.
class DueOnInput extends StatelessWidget {
  final DateTime? dueOn;
  final bool evening;

  final Function(DateTime? dueOn, bool evening) onUpdate;

  const DueOnInput({
    required this.onUpdate,
    required this.dueOn,
    required this.evening,
    super.key
  });

  @override
  Widget build(BuildContext context) {
    return TextButton(
      child: DueOn(dueOn: dueOn, evening: evening, showNull: true),
      onPressed: () {
        showChangeDueOnDialog(context, dueOn, evening, onUpdate);
      }
    );
  }
}
