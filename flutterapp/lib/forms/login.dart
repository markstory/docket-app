import 'package:flutter/material.dart';

// Login Form Wrapper
class LoginForm extends StatefulWidget {
  final Function onSubmit;

  const LoginForm({super.key, required this.onSubmit});

  @override
  LoginFormState createState() {
    return LoginFormState();
  }
}

// Define a corresponding State class.
// This class holds data related to the form.
class LoginFormState extends State<LoginForm> {
  // Create a global key that uniquely identifies the Form widget
  // and allows validation of the form.
  //
  // Note: This is a `GlobalKey<FormState>`,
  // not a GlobalKey<MyCustomFormState>.
  final _formKey = GlobalKey<FormState>();

  String? _email;
  String? _password;

  @override
  Widget build(BuildContext context) {
    return AutofillGroup(
      child: Form(
        key: _formKey,
        child: Column(
          children: <Widget>[
            TextFormField(
              key: const ValueKey('email'),
              decoration: const InputDecoration(
                labelText: 'E-Mail',
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'E-mail is required';
                }
                return null;
              },
              onSaved: (value) => _email = value,
              autofillHints: const [AutofillHints.username, AutofillHints.email],
            ),
            TextFormField(
              key: const ValueKey('password'),
              decoration: const InputDecoration(
                labelText: 'Password',
              ),
              obscureText: true,
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Password is required';
                }
                return null;
              },
              autofillHints: const [AutofillHints.password],
              onSaved: (value) => _password = value,
            ),
            ElevatedButton(
                child: const Text('Log in'),
                onPressed: () async {
                  if (!_formKey.currentState!.validate()) {
                    return;
                  }
                  _formKey.currentState!.save();
                  await widget.onSubmit(_email, _password);
                })
          ],
        ),
      )
    );
  }
}
