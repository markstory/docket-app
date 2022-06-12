class Task {
  final int? id;
  final int projectId;
  final int? sectionId;
  final String? title;
  final String? body;
  final DateTime? dueOn;
  final int childOrder;
  final int dayOrder;
  final bool evening;
  final bool completed;

  Task({
    this.id,
    required this.projectId, 
    this.sectionId,
    this.title,
    this.body,
    this.dueOn,
    required this.childOrder,
    required this.dayOrder,
    required this.evening,
    required this.completed,
  });

  factory Task.fromMap(Map<String, dynamic> json) {
    return Task(
      id: json['id'],
      projectId: json['project_id'],
      sectionId: json['section_id'],
      title: json['title'],
      body: json['body'],
      dueOn: json['due_on'],
      childOrder: json['child_order'],
      dayOrder: json['day_order'],
      evening: json['evening'],
      completed: json['completed'],
    );
  }

  Task copy({
    int? id,
    int projectId,
    int? sectionId,
    String? title,
    String? body,
    DateTime? dueOn,
    int? childOrder,
    int? dayOrder,
    bool? evening,
    bool? completed
  }) {
    return Task(
      id: id ?? this.id,
      projectId: projectId ?? this.projectId,
      sectionId: sectionId ?? this.sectionId,
      title: title ?? this.title,
      body: body ?? this.body,
      dueOn: dueOn ?? this.dueOne,
      childOrder: childOrder ?? this.childOrder,
      dayOrder: dayOrder ?? this.dayOrder,
      evening: evening ?? this.evening,
      completed: completed ?? this.completed,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'project_id': projectId,
      'section_id': sectionId,
      'title': title,
      'body': body,
      'due_on': dueOn,
      'child_order': childOrder,
      'day_order': dayOrder,
      'evening': evening,
      'completed': completed,
    };
  }
}
