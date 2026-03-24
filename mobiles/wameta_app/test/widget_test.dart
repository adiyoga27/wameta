import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:wameta_app/main.dart';

void main() {
  testWidgets('Splash load test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const WametaApp());

    // Verify that we start at the login screen (since no token)
    expect(find.text('WAMETA'), findsOneWidget);
    expect(find.text('MASUK'), findsOneWidget);
  });
}
