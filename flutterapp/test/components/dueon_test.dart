import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/components/dueon.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  final today = DateUtils.dateOnly(DateTime.now());
  final tomorrow = today.add(const Duration(days: 1));
  var database = LocalDatabase(inTest: true);

  group('DueOn', () {
    testWidgets('Render for today', (tester) async {
      await tester.pumpWidget(
          EntryPoint(database: database, child: Scaffold(body: DueOn(dueOn: today, evening: false, showNull: true))));
      expect(find.text('Today'), findsOneWidget);
    });

    testWidgets('Render for this evening', (tester) async {
      await tester.pumpWidget(
          EntryPoint(database: database, child: Scaffold(body: DueOn(dueOn: today, evening: true, showNull: true))));
      expect(find.text('This evening'), findsOneWidget);
    });

    testWidgets('render for tomorrow', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: database, child: Scaffold(body: DueOn(dueOn: tomorrow, evening: false, showNull: true))));
      expect(find.text('Tomorrow'), findsOneWidget);
    });
  });
}
