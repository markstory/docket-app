import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:docket/database.dart';
import 'package:docket/main.dart';
import 'package:docket/dialogs/confirmdelete.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  var database = LocalDatabase(inTest: true);

  Widget buildButton(void Function() onConfirm) {
    return EntryPoint(
        database: database,
        child: Builder(builder: (BuildContext context) {
          return TextButton(
              child: const Text('Open'),
              onPressed: () {
                showConfirmDelete(context: context, onConfirm: onConfirm);
              });
        }));
  }

  group('showConfirmDelete', () {
    testWidgets('ok triggers onConfirm', (tester) async {
      var callCount = 0;
      void handleConfirm() {
        callCount += 1;
      }
      await tester.pumpWidget(buildButton(handleConfirm));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Yes'));
      await tester.pumpAndSettle();

      expect(callCount, equals(1));
    });

    testWidgets('cancel does not trigger onConfirm', (tester) async {
      var callCount = 0;
      void handleConfirm() {
        callCount += 1;
      }
      await tester.pumpWidget(buildButton(handleConfirm));

      // Open dialog.
      await tester.tap(find.text('Open'));
      await tester.pumpAndSettle();

      await tester.tap(find.text('Cancel'));
      await tester.pumpAndSettle();

      expect(callCount, equals(0));
    });
  });
}
