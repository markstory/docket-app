import 'package:flutter/material.dart';

import 'package:docket/theme.dart';

class ProjectBadge extends StatelessWidget {
  final String text;
  final int color;
  final bool isActive;

  const ProjectBadge({required this.text, required this.color, this.isActive = false, super.key});

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var docketColors = theme.extension<DocketColors>()!;
    var projectColor = getProjectColor(color);
    var icon = isActive ? Icons.star : Icons.circle;

    return Wrap(spacing: space(1.1), children: [
      Icon(icon, color: projectColor, size: 14),
      Text(
        text,
        style: TextStyle(color: docketColors.secondaryText),
      ),
    ]);
  }
}
