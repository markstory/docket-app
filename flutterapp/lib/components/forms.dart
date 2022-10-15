import 'package:flutter/material.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:docket/theme.dart';

import 'package:docket/components/dueon.dart';
import 'package:docket/dialogs/changedueon.dart';

/// Form layout widget.
/// Includes a leading element that is expected to be ~18px wide
/// Generally an icon but can also be an interactive wiget like a checkbox.
class FormIconRow extends StatelessWidget {
  final Widget child;
  final Widget? icon;

  const FormIconRow({this.icon, required this.child, super.key});

  @override
  Widget build(BuildContext context) {
    late Widget iconWidget;
    if (icon != null) {
      iconWidget = Padding(padding: EdgeInsets.fromLTRB(space(1), space(1.2), space(2.5), 0), child: icon);
    } else {
      iconWidget = const SizedBox(width: 48);
    }

    return Container(
        padding: EdgeInsets.symmetric(vertical: space(1)),
        child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
          iconWidget,
          Expanded(child: child),
        ]));
  }
}

/// Form widget for updating the dueOn attribute of a task.
class DueOnInput extends StatelessWidget {
  final DateTime? dueOn;
  final bool evening;
  final Alignment? alignment;

  final Function(DateTime? dueOn, bool evening) onUpdate;

  const DueOnInput({required this.onUpdate, required this.dueOn, required this.evening, this.alignment, super.key});

  @override
  Widget build(BuildContext context) {
    Widget child = DueOn(dueOn: dueOn, evening: evening, showNull: true);
    if (alignment != null) {
      child = Align(alignment: alignment!, child: child);
    }
    return TextButton(
      style: TextButton.styleFrom(
        padding: EdgeInsets.zero,
      ),
      child: child,
      onPressed: () async {
        var result = await showChangeDueOnDialog(context, dueOn, evening);
        onUpdate(result.dueOn, result.evening);
      });
  }
}

/// Render text as markdown. Switch to TextInput on tap
/// for editing. Once editing is complete, the onChange
/// callback is triggered.
class MarkdownInput extends StatefulWidget {
  final String value;
  final String label;
  final Function(String newText) onChange;

  const MarkdownInput({required this.value, required this.onChange, this.label = "Notes", super.key});

  @override
  State<MarkdownInput> createState() => _MarkdownInputState();
}

class _MarkdownInputState extends State<MarkdownInput> {
  late FocusNode inputFocus;
  bool _editing = false;

  @override
  void initState() {
    super.initState();
    inputFocus = FocusNode();
  }

  @override
  void dispose() {
    inputFocus.dispose();

    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!_editing) {
      var body = widget.value.isNotEmpty ? widget.value : 'Tap to edit';
      var theme = Theme.of(context);

      return Padding(
        padding: EdgeInsets.fromLTRB(0, space(1.8), 0, 0),
        child: MarkdownBody(
          styleSheet: MarkdownStyleSheet.fromTheme(theme).copyWith(
            p: theme.textTheme.bodyMedium,
            code: TextStyle(
              color: theme.colorScheme.primary,
              backgroundColor: theme.colorScheme.surface,
            ),
            blockquoteDecoration: BoxDecoration(color: theme.colorScheme.surface),
            blockquote: const TextStyle(fontStyle: FontStyle.italic),
            codeblockDecoration: BoxDecoration(color: theme.colorScheme.surface),
          ),
          key: const ValueKey('markdown-preview'),
          data: body,
          selectable: true,
          onTapText: () {
            setState(() {
              _editing = true;
            });
            inputFocus.requestFocus();
          }));
    }

    return TextFormField(
        key: const ValueKey('markdown-input'),
        keyboardType: TextInputType.multiline,
        minLines: 1,
        maxLines: null,
        focusNode: inputFocus,
        decoration: InputDecoration(
          labelText: widget.label,
        ),
        initialValue: widget.value,
        onSaved: (value) {
          if (value != null) {
            widget.onChange(value);
            setState(() {
              _editing = false;
            });
          }
        });
  }
}
