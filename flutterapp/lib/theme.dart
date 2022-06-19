import 'package:flutter/material.dart';

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

@immutable
class DocketColors extends ThemeExtension<DocketColors> {
  static const Color white = Color(0xFFffffff);
  static const Color black = Color(0xFF120a08);

  static const Color gray000 = Color(0xFFf8f7f6);
  static const Color gray100 = Color(0xFFf0ecea);
  static const Color gray200 = Color(0xFFebe4e1);
  static const Color gray300 = Color(0xFFe4dad4);
  static const Color gray400 = Color(0xFFc3b6af);
  static const Color gray500 = Color(0xFF937d71);
  static const Color gray600 = Color(0xFF7d6354);
  static const Color gray700 = Color(0xFF533c32);
  static const Color gray800 = Color(0xFF302317);
  static const Color gray900 = Color(0xFF2a1813);

  static const Color red100 = Color(0xFFffe2d9);
  static const Color red300 = Color(0xFFe27e5a);
  static const Color red500 = Color(0xFFb82c00);
  static const Color red700 = Color(0xFF8c2100);
  static const Color red900 = Color(0xFF501300);

  static const Color ochre100 = Color(0xFFfcf1dc);
  static const Color ochre300 = Color(0xFFf0c064);
  static const Color ochre500 = Color(0xFFd9940f);
  static const Color ochre700 = Color(0xFF8d6113);
  static const Color ochre900 = Color(0xFF4b3105);

  static const Color green100 = Color(0xFFf4fbdd);
  static const Color green300 = Color(0xFFbfe04c);
  static const Color green500 = Color(0xFF93b228);
  static const Color green700 = Color(0xFF6f8818);
  static const Color green900 = Color(0xFF36420e);

  static const Color purple100 = Color(0xFFf9edf9);
  static const Color purple300 = Color(0xFFe5b5e5);
  static const Color purple500 = Color(0xFFa848a8);
  static const Color purple700 = Color(0xFF843884);
  static const Color purple900 = Color(0xFF381838);

  static const Color blue100 = Color(0xFFdfeef9);
  static const Color blue300 = Color(0xFF77b9e3);
  static const Color blue500 = Color(0xFF287bb2);
  static const Color blue700 = Color(0xFF1d5a83);
  static const Color blue900 = Color(0xFF052f4b);

  final Color? actionLock;
  final Color? actionEdit;
  final Color? actionDelete;
  final Color? actionComplete;

  final Color? dueNone;
  final Color? dueOverdue;
  final Color? dueToday;
  final Color? dueEvening;
  final Color? dueWeek;
  final Color? dueFortnight;

  const DocketColors({
    required this.actionLock,
    required this.actionEdit,
    required this.actionDelete,
    required this.actionComplete,
    required this.dueNone,
    required this.dueOverdue,
    required this.dueToday,
    required this.dueEvening,
    required this.dueWeek,
    required this.dueFortnight,
  });

  @override
  DocketColors copyWith({
    Color? actionLock,
    Color? actionEdit,
    Color? actionDelete,
    Color? actionComplete,
    Color? dueNone,
    Color? dueOverdue,
    Color? dueToday,
    Color? dueEvening,
    Color? dueWeek,
    Color? dueFortnight,
  }) {
    return DocketColors(
      actionLock: actionLock ?? this.actionLock,
      actionEdit: actionEdit ?? this.actionEdit,
      actionDelete: actionDelete ?? this.actionDelete,
      actionComplete: actionComplete ?? this.actionComplete,
      dueNone: dueNone ?? this.dueNone,
      dueOverdue: dueOverdue ?? this.dueOverdue,
      dueToday: dueToday ?? this.dueToday,
      dueEvening: dueEvening ?? this.dueEvening,
      dueWeek: dueWeek ?? this.dueWeek,
      dueFortnight: dueFortnight ?? this.dueFortnight,
    );
  }

  @override
  DocketColors lerp(ThemeExtension<DocketColors>? other, double t) {
    if (other is! DocketColors) {
      return this;
    }
    return DocketColors(
      actionLock: Color.lerp(actionLock, other.actionLock, t),
      actionEdit: Color.lerp(actionEdit, other.actionEdit, t),
      actionDelete: Color.lerp(actionDelete, other.actionDelete, t),
      actionComplete: Color.lerp(actionComplete, other.actionComplete, t),
      dueNone: Color.lerp(dueNone, other.dueNone, t),
      dueOverdue: Color.lerp(dueOverdue, other.dueOverdue, t),
      dueToday: Color.lerp(dueToday, other.dueToday, t),
      dueEvening: Color.lerp(dueEvening, other.dueEvening, t),
      dueWeek: Color.lerp(dueWeek, other.dueWeek, t),
      dueFortnight: Color.lerp(dueFortnight, other.dueFortnight, t),
    );
  }

  static const light = DocketColors(
    actionLock: ochre500,
    actionEdit: ochre700,
    actionDelete: red500,
    actionComplete: green700,
    dueNone: gray600,
    dueOverdue: red500,
    dueToday: purple500,
    dueEvening: blue500,
    dueWeek: blue700,
    dueFortnight: gray500,
  );

  static const dark = DocketColors(
    actionLock: ochre500,
    actionEdit: ochre300,
    actionDelete: red300,
    actionComplete: green300,
    dueNone: gray300,
    dueOverdue: red500,
    dueToday: purple500,
    dueEvening: blue500,
    dueWeek: blue300,
    dueFortnight: gray500,
  );
}

final lightTheme = ThemeData(
  colorScheme: const ColorScheme(
    brightness: Brightness.light,
    primary: DocketColors.purple500,
    onPrimary: DocketColors.purple700,
    primaryContainer: DocketColors.purple100,
    secondary: DocketColors.ochre500,
    onSecondary: DocketColors.ochre700,
    secondaryContainer: DocketColors.ochre100,
    tertiary: DocketColors.blue500,
    onTertiary: DocketColors.blue700,
    tertiaryContainer: DocketColors.blue100,
    error: DocketColors.red500,
    onError: DocketColors.red700,
    errorContainer: DocketColors.red100,
    background: DocketColors.white,
    onBackground: DocketColors.gray000,
    surface: DocketColors.gray100,
    onSurface: DocketColors.gray200,
    surfaceVariant: DocketColors.gray000,
    surfaceTint: DocketColors.purple100,
  ),
  extensions: const [DocketColors.light],
);

final darkTheme = ThemeData(
  colorScheme: const ColorScheme(
    brightness: Brightness.dark,
    primary: DocketColors.purple500,
    onPrimary: DocketColors.purple700,
    primaryContainer: DocketColors.purple900,
    secondary: DocketColors.ochre500,
    onSecondary: DocketColors.ochre700,
    secondaryContainer: DocketColors.ochre900,
    tertiary: DocketColors.blue500,
    onTertiary: DocketColors.blue700,
    tertiaryContainer: DocketColors.blue900,
    error: DocketColors.red300,
    onError: DocketColors.red500,
    errorContainer: DocketColors.red900,
    background: DocketColors.gray900,
    onBackground: DocketColors.gray800,
    surface: DocketColors.gray800,
    onSurface: DocketColors.gray700,
    surfaceVariant: DocketColors.purple900,
    surfaceTint: DocketColors.purple100,
  ),
  extensions: const [DocketColors.dark],
);
