import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

SnackBar successSnackBar({BuildContext? context, ThemeData? theme, required String text}) {
  assert(context != null || theme != null, "one of theme or context is required");

  theme = theme ?? Theme.of(context!);
  var colors = theme.extension<DocketColors>()!;

  return SnackBar(
      content: Row(mainAxisAlignment: MainAxisAlignment.start, children: [
    Padding(
      padding: const EdgeInsets.only(left: 4, right: 4),
      child: Icon(Icons.check_circle, color: colors.actionComplete),
    ),
    Text(text),
  ]));
}

SnackBar errorSnackBar({BuildContext? context, ThemeData? theme, required String text}) {
  assert(context != null || theme != null, "one of theme or context is required");
  theme = theme ?? Theme.of(context!);

  return SnackBar(
      content: Row(mainAxisAlignment: MainAxisAlignment.start, children: [
    Padding(
      padding: const EdgeInsets.only(left: 4, right: 4),
      child: Icon(Icons.error_outline, color: theme.colorScheme.error),
    ),
    Text(text),
  ]));
}
