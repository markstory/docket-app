import 'package:flutter/material.dart';

import 'package:docket/models/task.dart';

enum Menu {move, reschedule, delete}

class TaskItem extends StatelessWidget {
  final Task task;

  const TaskItem(this.task, {super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Checkbox(
                  checkColor: Colors.green,
                  value: task.completed,
                  onChanged: (bool? value) {
                    print('Checkbox checked');
                  }
                ),
                Flexible(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        task.title,
                        overflow: TextOverflow.ellipsis,
                      ),
                      Padding(
                        padding: const EdgeInsets.only(top: 2),
                        child:Row(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: const [
                            Padding(
                              padding: EdgeInsets.all(2),
                              child: Icon(Icons.circle, color: Colors.red, size: 12),
                            ),
                            Text('Project name'),
                          ]
                        ),
                      )
                    ]
                  )
                ),
              ]
            )
          ),
          PopupMenuButton<Menu>(
            onSelected: (Menu item) {
              print('selected $item');
            },
            itemBuilder: (BuildContext context) {
              return <PopupMenuEntry<Menu>>[
                const PopupMenuItem<Menu>(
                  value: Menu.move,
                  child: Text('Move To'),
                ),
                const PopupMenuItem<Menu>(
                  value: Menu.reschedule,
                  child: Text('Reschedule'),
                ),
                const PopupMenuItem<Menu>(
                  value: Menu.delete,
                  child: Text('Delete'),
                ),
              ];
            }
          )
          // Container(
          //   height: 30,
          //   width: 40,
          //   color: Colors.orange,
          // )
          /*
          Flexible(
            child: Row(
              children: [
                Checkbox(
                  checkColor: Colors.green,
                  value: task.completed,
                  onChanged: (bool? value) {
                    print('Checkbox checked');
                  }
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      task.title,
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 2),
                      child:Row(
                        crossAxisAlignment: CrossAxisAlignment.center,
                        children: const [
                          Padding(
                            padding: EdgeInsets.all(2),
                            child: Icon(Icons.circle, color: Colors.red, size: 12),
                          ),
                          Text('Project name'),
                        ]
                      ),
                    )
                  ]
                ),
              ]
            ),
          ),
          */
      ]
    );
  }
}
