import 'package:flutter/material.dart';

import 'package:docket/models/project.dart';
import 'package:docket/viewmodels/projectdetails.dart';

Future<void> showCreateSectionDialog(BuildContext context, ProjectDetailsViewModel viewmodel) {
  var formKey = GlobalKey<FormState>();
  var section = Section.blank();

  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (BuildContext context) {
      var form = Form(
        key: formKey,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          TextFormField(
            key: const ValueKey("section-name"),
            decoration: const InputDecoration(
              labelText: 'Name',
            ),
            validator: (String? value) {
              return (value != null && value.isNotEmpty) ? null : 'Name is required';
            },
            initialValue: section.name,
            onSaved: (value) {
              if (value != null) {
                section.name = value;
              }
            }
          ),
          ButtonBar(children: [
            TextButton(
              child: const Text('Cancel'),
              onPressed: () {
                Navigator.pop(context);
              }
            ),
            ElevatedButton(
              child: const Text('Save'),
              onPressed: () async {
                if (formKey.currentState!.validate()) {
                  var navigator = Navigator.of(context);

                  formKey.currentState!.save();
                  await viewmodel.createSection(section);

                  formKey.currentState!.reset();
                  navigator.pop();
                }
              }
            )
          ]),
        ])
      );

      return AlertDialog(
        title: const Text('Create Section'),
        content: SingleChildScrollView(child: form)
      );
    }
  );
}
