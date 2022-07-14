import 'package:flutter/material.dart';

import 'package:docket/theme.dart';

class ProjectBadge extends StatelessWidget {
  final String text;
  final int color;

  const ProjectBadge({required this.text, required this.color, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var docketColors = theme.extension<DocketColors>()!;

    var projectColor = getProjectColor(color);
    return Row(
      children: [
        Icon(Icons.circle, color: projectColor, size: 12),
        SizedBox(width: space(1)),
        Text(
          text,
          style: TextStyle(color: docketColors.secondaryText),
        ),
      ]
    );
  }
}
