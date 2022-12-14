import 'package:flutter/material.dart';

void showConfirmDelete({
  required BuildContext context,
  required void Function () onConfirm,
  String title = 'Are you sure?',
  String content = 'Are you sure you want to proceed?',
  String cancelButtonText = 'Cancel',
  String confirmButtonText = 'Yes',
}) {
  showDialog(
    context: context,
    builder: (BuildContext context) {
      return AlertDialog(
        title: Text(title),
        content: Text(content),
        actions: [
          TextButton(
            onPressed: onConfirm,
            child: Text(confirmButtonText),
          ),
          ElevatedButton(child: Text(cancelButtonText), onPressed: () {
            Navigator.pop(context);
          }),
        ]
      );
    }
  );
}
