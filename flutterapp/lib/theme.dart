import 'package:flutter/material.dart';

/*
const PROJECT_COLORS = [
  {'id': 0, 'name': 'green', 'code': '#28aa48'},
  {'id': 1, 'name': 'teal', 'code': '#6fd19d'},
  {'id': 2, 'name': 'plum', 'code': '#5d3688'},
  {'id': 3, 'name': 'lavender', 'code': '#b86fd1'},
  {'id': 4, 'name': 'sea blue', 'code': '#218fa7'},
  {'id': 5, 'name': 'light blue', 'code': '#78f0f6'},
  {'id': 6, 'name': 'toffee', 'code': '#ab6709'},

  {'id': 7, 'name': 'peach', 'code': '#fbaf45'},
  {'id': 8, 'name': 'berry', 'code': '#a00085'},

  {'id': 9, 'name': 'pink', 'code': '#fb4fc8'},
  {'id': 10, 'name': 'olive', 'code': '#818c00'},

  {'id': 11, 'name': 'lime', 'code': '#cef226'},
  {'id': 12, 'name': 'ultramarine', 'code': '#4655ff'},

  {'id': 13, 'name': 'sky', 'code': '#91b5ff'},
  {'id': 14, 'name': 'slate', 'code': '#525876'},

  {'id': 15, 'name': 'smoke', 'code': '#9197af'},
  {'id': 16, 'name': 'brick', 'code': '#b60909'},
  {'id': 17, 'name': 'flame', 'code': '#f14949'},
];
*/
final projectColors = {
  0: const Color(0xFF28aa48),
  1: const Color(0xFF6fd19d),
  2: const Color(0xFF5d3688),
  3: const Color(0xFFb86fd1),
  4: const Color(0xFF218fa7),
  5: const Color(0xFF78f0f6),
  6: const Color(0xFFab6709),
  7: const Color(0xFFfbaf45),
  8: const Color(0xFFa00085),
  9: const Color(0xFFfb4fc8),
  10: const Color(0xFF818c00),
  11: const Color(0xFFcef226),
  12: const Color(0xFF4755ff),
  13: const Color(0xFF91b5ff),
  14: const Color(0xFF525876),
  15: const Color(0xFF91971f),
  16: const Color(0xFFb60909),
  17: const Color(0xFFf14949),
};

/// Convert a server side colour 'id' to material
/// colors.
Color getProjectColor(int colorId) {
  var color = projectColors[colorId];
  if (color == null) {
    var color = projectColors[0];
    if (color == null) {
      throw Exception('Invalid fallback color');
    }
    return color;
  }
  return color;
}
