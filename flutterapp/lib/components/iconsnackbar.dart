import 'package:flutter/material.dart';

SnackBar successSnackBar({required String text}) {
  return SnackBar(
    content: Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        const Padding(
          padding:EdgeInsets.only(left: 4, right: 4),
          child: Icon(Icons.check_circle, color: Colors.green),
        ),
        Text(text),
      ]
    )
  );
}

SnackBar errorSnackBar({required String text}) {
  return SnackBar(
    content: Row(
      mainAxisAlignment: MainAxisAlignment.start,
      children: [
        const Padding(
          padding:EdgeInsets.only(left: 4, right: 4),
          child: Icon(Icons.error_outline, color: Colors.red),
        ),
        Text(text),
      ]
    )
  );
}
