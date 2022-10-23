import 'package:flutter/material.dart';
import 'package:docket/theme.dart';

class LoadingIndicator extends StatelessWidget {
  const LoadingIndicator({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(top: space(4)),
      child: Row(mainAxisAlignment: MainAxisAlignment.center, children: const [
      SizedBox(
        width: 60,
        height: 60,
        child: CircularProgressIndicator(),
      ),
    ]));
  }
}
