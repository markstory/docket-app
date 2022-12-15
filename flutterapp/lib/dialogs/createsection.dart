import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:docket/models/project.dart';
import 'package:docket/providers/projects.dart';

Future<void> showCreateSectionDialog(BuildContext context, Project project) {
  var formKey = GlobalKey<FormState>();
  var section = Section.blank();

  return showDialog<void>(
    context: context,
    barrierDismissible: true,
    builder: (BuildContext context) {
      var projectsProvider = Provider.of<ProjectsProvider>(context, listen: false);
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
                  await projectsProvider.createSection(project, section);
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
