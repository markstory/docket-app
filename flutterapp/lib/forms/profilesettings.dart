import 'package:flutter/material.dart';

import 'package:docket/models/userprofile.dart';
import 'package:docket/theme.dart';

class ProfileSettingsForm extends StatefulWidget {
  final UserProfile userprofile;
  final void Function(UserProfile userprofile) onSave;

  const ProfileSettingsForm({super.key, required this.userprofile, required this.onSave});

  @override
  State<ProfileSettingsForm> createState() => _ProfileSettingsFormState();
}

class _ProfileSettingsFormState extends State<ProfileSettingsForm> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  late UserProfile userprofile;

  @override
  void initState() {
    super.initState();
    userprofile = UserProfile.fromMap(widget.userprofile.toMap());
  }

  @override
  Widget build(BuildContext context) {
    var theme = Theme.of(context);
    var customColors = getCustomColors(context);

    return Form(
        key: _formKey,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          TextFormField(
              key: const ValueKey('name'),
              decoration: InputDecoration(
                labelText: 'Name',
                icon: Icon(Icons.person_outlined, color: theme.colorScheme.primary),
              ),
              validator: (String? value) {
                return (value != null && value.isNotEmpty) ? null : 'Name is required';
              },
              initialValue: userprofile.name,
              onSaved: (value) {
                if (value != null) {
                  userprofile.name = value;
                }
              }),
          TextFormField(
              key: const ValueKey('email'),
              decoration: InputDecoration(
                labelText: 'E-mail',
                icon: Icon(Icons.mail_outlined, color: theme.colorScheme.secondary),
              ),
              validator: (String? value) {
                return (value != null && value.isNotEmpty) ? null : 'E-mail is required';
              },
              initialValue: userprofile.email,
              onSaved: (value) {
                if (value != null) {
                  userprofile.email = value;
                }
              }),
          DropdownButtonFormField(
              decoration: InputDecoration(
                labelText: 'Theme',
                icon: Icon(Icons.format_paint_outlined, color: customColors.actionComplete),
              ),
              key: const ValueKey('theme'),
              value: userprofile.theme,
              items: const [
                DropdownMenuItem(value: 'light', child: Text('Light')),
                DropdownMenuItem(value: 'dark', child: Text('Dark')),
                DropdownMenuItem(value: 'system', child: Text('Follow system')),
              ],
              onChanged: (String? value) {
                if (value != null) {
                  userprofile.theme = value;
                }
              }),
          ButtonBar(children: [
            ElevatedButton(
                child: const Text('Save'),
                onPressed: () async {
                  if (_formKey.currentState!.validate()) {
                    _formKey.currentState!.save();
                    widget.onSave(userprofile);
                  }
                })
          ])
        ]));
  }
}
