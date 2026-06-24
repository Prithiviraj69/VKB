// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:app/main.dart';

void main() {
  testWidgets('App starts with Splash Screen showing title', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const VishwakarmaBandhuApp());

    // Verify that the splash screen shows 'Vishwakarma Bandhu'.
    expect(find.text('Vishwakarma Bandhu'), findsOneWidget);

    // Let the timers and animations complete so the test framework exits cleanly
    await tester.pump(const Duration(seconds: 3));
  });
}
