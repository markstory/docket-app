import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

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
      iconWidget = SizedBox(width: 34);
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
