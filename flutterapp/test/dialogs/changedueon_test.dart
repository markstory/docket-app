import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/dialogs/changedueon.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  Widget buildButton(DateTime? dueOn, bool evening, Function(DateTime? x, bool y) onUpdate) {
    var database = LocalDatabase.instance();
    return EntryPoint(
        database: database,
        child: Builder(builder: (BuildContext context) {
          return TextButton(
              child: const Text('Open'),
              onPressed: () async {
                var result = await showChangeDueOnDialog(context, dueOn, evening);
                onUpdate(result.dueOn, result.evening);
              });
        }));
  }

  group('ChangeDueOnDialog', () {
    final today = DateUtils.dateOnly(DateTime.now());
    final tomorrow = today.add(const Duration(days: 1));

    testWidgets('select tomorrow value', (tester) async {
      var callCount = 0;
      void onUpdate(DateTime? dueOn, bool evening) {
        expect(dueOn, equals(tomorrow));
        expect(evening, equals(false));
        callCount++;
      }

      await tester.pumpWidget(buildButton(today, false, onUpdate));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Tomorrow'));
      await tester.pumpAndSettle();
      expect(callCount, equals(1));
    });

    testWidgets('select evening value', (tester) async {
      var callCount = 0;
      void onUpdate(DateTime? dueOn, bool evening) {
        expect(dueOn, equals(today));
        expect(evening, equals(true));
        callCount++;
      }

      await tester.pumpWidget(buildButton(today, false, onUpdate));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('This evening'));
      await tester.pumpAndSettle();
      expect(callCount, equals(1));
    });

    testWidgets('displays options for today', (tester) async {
      var callCount = 0;
      void onUpdate(DateTime? dueOn, bool evening) {
        callCount++;
      }

      await tester.pumpWidget(buildButton(today, false, onUpdate));
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      expect(find.text('This evening'), findsOneWidget);
      expect(find.text('Tomorrow'), findsOneWidget);
      expect(find.text('Later'), findsOneWidget);
      expect(find.text('Choose a day'), findsOneWidget);
      expect(find.text('Today'), findsNothing);
      expect(callCount, equals(0));
    });

    testWidgets('displays options for tomorrow', (tester) async {
      var callCount = 0;
      void onUpdate(DateTime? dueOn, bool evening) {
        callCount++;
      }

      await tester.pumpWidget(buildButton(tomorrow, false, onUpdate));
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      expect(find.text('This evening'), findsOneWidget);
      expect(find.text('Today'), findsOneWidget);
      expect(find.text('Later'), findsOneWidget);
      expect(find.text('Choose a day'), findsOneWidget);
      expect(find.text('Tomorrow'), findsNothing);
      expect(callCount, equals(0));
    });

    testWidgets('displays options for friday', (tester) async {
      var callCount = 0;
      void onUpdate(DateTime? dueOn, bool evening) {
        callCount++;
      }
      await tester.pumpWidget(buildButton(tomorrow, false, onUpdate));
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      expect(find.text('This evening'), findsOneWidget);
      expect(find.text('Today'), findsOneWidget);
      expect(find.text('Later'), findsOneWidget);
      expect(find.text('Choose a day'), findsOneWidget);
      expect(find.text('Tomorrow'), findsNothing);
      expect(callCount, equals(0));
    });
  });
}
