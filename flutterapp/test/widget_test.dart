// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';

void main() {
  testWidgets('Renders a login screen with an empty database.',
      (WidgetTester tester) async {
    // Build our app and trigger a frame.
    final db = LocalDatabase();
    await tester.pumpWidget(EntryPoint(database: db));
    await tester.pumpAndSettle();

    // Go to login page
    expect(find.text('Login'), findsOneWidget);
  });
}
