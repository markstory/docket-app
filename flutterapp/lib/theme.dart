import 'package:flutter/material.dart';

class ProjectColor {
  final int id;
  final String name;
  final Color color;

  const ProjectColor(this.id, this.name, this.color);
}

const _projectColors = [
  ProjectColor(0, 'green', Color(0xFF28aa48)),
  ProjectColor(1, 'teal', Color(0xFF6fd19d)),
  ProjectColor(2, 'plum', Color(0xFF5d3688)),
  ProjectColor(3, 'lavender', Color(0xFFb86fd1)),
  ProjectColor(4, 'sea blue', Color(0xFF218fa7)),
  ProjectColor(5, 'light blue', Color(0xFF78f0f6)),
  ProjectColor(6, 'toffee', Color(0xFFab6709)),
  ProjectColor(7, 'peach', Color(0xFFfbaf45)),
  ProjectColor(8, 'berry', Color(0xFFa00085)),
  ProjectColor(9, 'pink', Color(0xFFfb4fc8)),
  ProjectColor(10, 'olive', Color(0xFF818c00)),
  ProjectColor(11, 'lime', Color(0xFFcef226)),
  ProjectColor(12, 'ultramarine', Color(0xFF4755ff)),
  ProjectColor(13, 'sky', Color(0xFF91b5ff)),
  ProjectColor(14, 'slate', Color(0xFF525876)),
  ProjectColor(15, 'smoke', Color(0xFF9197af)),
  ProjectColor(16, 'brick', Color(0xFFb60909)),
  ProjectColor(17, 'flame', Color(0xFFf14949)),
];

/// Convert a server side colour 'id' to material
/// colors.
Color getProjectColor(int colorId) {
  for (var color in _projectColors) {
    if (color.id == colorId) {
      return color.color;
    }
  }

  return _projectColors[0].color;
}

List<ProjectColor> getProjectColors() {
  return _projectColors;
}

@immutable
class DocketColors extends ThemeExtension<DocketColors> {
  static const double iconSize = 24;
  static const BorderRadius borderRadius = BorderRadius.all(Radius.circular(14));

  // Color values for docket theme.
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

  // Semantic names that vary in light/dark mode.
  final Color? actionLock;
  final Color? actionEdit;
  final Color? actionDelete;
  final Color? actionComplete;

  final Color? dueNone;
  final Color? dueOverdue;
  final Color? dueToday;
  final Color? dueEvening;
  final Color? dueTomorrow;
  final Color? dueWeek;
  final Color? dueFortnight;

  final Color? secondaryText;
  final Color? disabledText;

  const DocketColors({
    required this.actionLock,
    required this.actionEdit,
    required this.actionDelete,
    required this.actionComplete,
    required this.dueNone,
    required this.dueOverdue,
    required this.dueToday,
    required this.dueTomorrow,
    required this.dueEvening,
    required this.dueWeek,
    required this.dueFortnight,
    required this.secondaryText,
    required this.disabledText,
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
    Color? dueTomorrow,
    Color? dueEvening,
    Color? dueWeek,
    Color? dueFortnight,
    Color? secondaryText,
    Color? disabledText,
  }) {
    return DocketColors(
      actionLock: actionLock ?? this.actionLock,
      actionEdit: actionEdit ?? this.actionEdit,
      actionDelete: actionDelete ?? this.actionDelete,
      actionComplete: actionComplete ?? this.actionComplete,
      dueNone: dueNone ?? this.dueNone,
      dueOverdue: dueOverdue ?? this.dueOverdue,
      dueToday: dueToday ?? this.dueToday,
      dueTomorrow: dueTomorrow ?? this.dueTomorrow,
      dueEvening: dueEvening ?? this.dueEvening,
      dueWeek: dueWeek ?? this.dueWeek,
      dueFortnight: dueFortnight ?? this.dueFortnight,
      secondaryText: secondaryText ?? this.secondaryText,
      disabledText: disabledText ?? this.disabledText,
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
      dueTomorrow: Color.lerp(dueTomorrow, other.dueTomorrow, t),
      dueEvening: Color.lerp(dueEvening, other.dueEvening, t),
      dueWeek: Color.lerp(dueWeek, other.dueWeek, t),
      dueFortnight: Color.lerp(dueFortnight, other.dueFortnight, t),
      secondaryText: Color.lerp(secondaryText, other.secondaryText, t),
      disabledText: Color.lerp(disabledText, other.disabledText, t),
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
    dueTomorrow: ochre500,
    dueEvening: blue500,
    dueWeek: blue700,
    dueFortnight: gray500,
    secondaryText: gray700,
    disabledText: gray400,
  );

  static const dark = DocketColors(
    actionLock: ochre500,
    actionEdit: ochre300,
    actionDelete: red500,
    actionComplete: green500,
    dueNone: gray500,
    dueOverdue: red500,
    dueToday: purple500,
    dueTomorrow: ochre500,
    dueEvening: blue500,
    dueWeek: blue300,
    dueFortnight: gray500,
    secondaryText: gray300,
    disabledText: gray600,
  );
}

final lightTheme = ThemeData(
  scaffoldBackgroundColor: Colors.white,
  drawerTheme: const DrawerThemeData(
    backgroundColor: DocketColors.gray000,
  ),
  colorScheme: const ColorScheme(
    brightness: Brightness.light,
    primary: DocketColors.purple500,
    onPrimary: DocketColors.white,
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
    surface: DocketColors.gray200,
    onSurface: DocketColors.black,
    // Used for high contrast backgrounds
    surfaceVariant: DocketColors.gray000,
    surfaceTint: DocketColors.purple100,
  ),
  extensions: const [DocketColors.light],
);

final darkTheme = ThemeData(
  scaffoldBackgroundColor: DocketColors.gray900,
  dialogBackgroundColor: DocketColors.gray900,
  popupMenuTheme: const PopupMenuThemeData(
    color: DocketColors.gray900,
  ),
  drawerTheme: const DrawerThemeData(
    backgroundColor: DocketColors.gray900,
  ),
  appBarTheme: const AppBarTheme(
    backgroundColor: DocketColors.purple700,
  ),
  colorScheme: const ColorScheme(
    brightness: Brightness.dark,
    primary: DocketColors.purple700,
    onPrimary: DocketColors.gray300,
    primaryContainer: DocketColors.purple500,
    secondary: DocketColors.ochre700,
    onSecondary: DocketColors.gray300,
    secondaryContainer: DocketColors.ochre900,
    tertiary: DocketColors.blue500,
    onTertiary: DocketColors.blue700,
    tertiaryContainer: DocketColors.blue900,
    error: DocketColors.red500,
    onError: DocketColors.red300,
    errorContainer: DocketColors.red900,
    background: DocketColors.gray900,
    onBackground: DocketColors.gray800,
    surface: DocketColors.gray800,
    onSurface: DocketColors.white,
    // Used for high contrast backgrounds
    surfaceVariant: DocketColors.gray800,
    surfaceTint: DocketColors.purple700,
  ),
  extensions: const [DocketColors.dark],
);

/// Return a value based on the sizing unit of 8px
double space(double units) {
  var unitSize = 8;
  return units * unitSize;
}

DocketColors getCustomColors(BuildContext context) {
  var theme = Theme.of(context);
  return theme.extension<DocketColors>()!;
}

BoxDecoration itemDragBoxDecoration(ThemeData theme) {
  return BoxDecoration(color: theme.colorScheme.background, boxShadow: const [
    BoxShadow(
      color: Color.fromARGB(5, 63, 63, 68),
      spreadRadius: 0,
      blurRadius: 1,
      offset: Offset(0, 0),
    ),
    BoxShadow(
      color: Color.fromARGB(25, 34, 33, 81),
      spreadRadius: 0,
      blurRadius: 15,
      offset: Offset(0, 15),
    ),
  ]);
}

TextStyle? completedStyle(BuildContext context, bool completed) {
  var customColors = getCustomColors(context);
  if (!completed) {
    return null;
  }
  return TextStyle(
    color: customColors.disabledText,
    decoration: TextDecoration.lineThrough,
  );
}
