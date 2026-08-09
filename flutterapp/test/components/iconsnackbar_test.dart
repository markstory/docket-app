import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences_platform_interface/shared_preferences_async_platform_interface.dart';
import 'package:shared_preferences_platform_interface/in_memory_shared_preferences_async.dart';

import 'package:docket/database.dart';
import 'package:docket/components/iconsnackbar.dart';
import 'package:docket/main.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  SharedPreferencesAsyncPlatform.instance = InMemorySharedPreferencesAsync.empty();

  var database = LocalDatabase(inTest: true);

  group('iconsnackbar.successSnackBar()', () {
    testWidgets('render', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: database,
          child: Scaffold(
            body: Builder(builder: (context) {
              return ElevatedButton(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(successSnackBar(context: context, text: 'Success'));
                },
                child: const Text('Click'),
              );
            }),
        )));
      await tester.tap(find.text('Click'));
      await tester.pump();

      expect(find.text('Success'), findsOneWidget);
      expect(find.byIcon(Icons.check_circle), findsOneWidget);
    });
  });

  group('iconsnackbar.errorSnackBar()', () {
    testWidgets('render', (tester) async {
      await tester.pumpWidget(EntryPoint(
          database: database,
          child: Scaffold(
            body: Builder(builder: (context) {
              return ElevatedButton(
                onPressed: () {
                  ScaffoldMessenger.of(context).showSnackBar(errorSnackBar(context: context, text: 'Error'));
                },
                child: const Text('Click'),
              );
            }),
        )));
      await tester.tap(find.text('Click'));
      await tester.pump();

      expect(find.text('Error'), findsOneWidget);
      expect(find.byIcon(Icons.error_outline), findsOneWidget);
    });
  });
}

