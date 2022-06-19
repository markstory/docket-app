import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

SnackBar successSnackBar({required BuildContext context, required String text}) {
  var customColors = Theme.of(context).extension<DocketColors>()!;
  return SnackBar(
    content: Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, right: 4),
          child: Icon(Icons.check_circle, color: customColors.actionComplete),
        ),
        Text(text),
      ]
    )
  );
}

SnackBar errorSnackBar({required BuildContext context, required String text}) {
  var theme = Theme.of(context);
  return SnackBar(
    content: Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, right: 4),
          child: Icon(Icons.error_outline, color: theme.colorScheme.error),
        ),
        Text(text),
      ]
    )
  );
}
