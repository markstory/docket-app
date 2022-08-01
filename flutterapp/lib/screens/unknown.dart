import 'package:flutter/material.dart';

class UnknownScreen extends StatelessWidget {
  static const routeName = '/404';

  const UnknownScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
        appBar: AppBar(),
        body: const Center(
          child: Text('404!'),
        ));
  }
}
