class Task {
  int? id;
  String projectSlug;
  String projectName;
  int projectColor;
  int? sectionId;
  String title;
  String body;
  DateTime? dueOn;
  int childOrder;
  int dayOrder;
  bool evening;
  bool completed;

  Task({
    this.id,
    required this.projectSlug, 
    required this.projectName,
    required this.projectColor,
    this.sectionId,
    required this.title,
    required this.body,
    this.dueOn,
    required this.childOrder,
    required this.dayOrder,
    required this.evening,
    required this.completed,
  });

  factory Task.fromMap(Map<String, dynamic> json) {
    DateTime? dueOn;
    if (json['dueOn'] != null) {
      dueOn = DateTime.parse(json['due_on']);
    }
    var projectSlug = json['project_slug'];
    projectSlug ??= json['project']['slug'];
    var projectColor = json['project_color'];
    projectColor ??= json['project']['color'];
    var projectName = json['project_name'];
    projectName ??= json['project']['name'];

    var evening = json['evening'];
    if (evening is int) {
      evening = evening == 0 ? false : true;
    }
    var completed = json['completed'];
    if (completed is int) {
      completed = completed == 0 ? false : true;
    }

    return Task(
      id: json['id'],
      projectSlug: projectSlug,
      projectName: projectName,
      projectColor: projectColor,
      sectionId: json['section_id'],
      title: json['title'] ?? '',
      body: json['body'] ?? '',
      dueOn: dueOn,
      childOrder: json['child_order'],
      dayOrder: json['day_order'],
      evening: evening,
      completed: completed,
    );
  }

  Task copy({
    int? id,
    String? projectSlug,
    String? projectName,
    int? projectColor,
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
      projectSlug: projectSlug ?? this.projectSlug,
      projectName: projectName ?? this.projectName,
      projectColor: projectColor ?? this.projectColor,
      sectionId: sectionId ?? this.sectionId,
      title: title ?? this.title,
      body: body ?? this.body,
      dueOn: dueOn ?? this.dueOn,
      childOrder: childOrder ?? this.childOrder,
      dayOrder: dayOrder ?? this.dayOrder,
      evening: evening ?? this.evening,
      completed: completed ?? this.completed,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'project_slug': projectSlug,
      'project_name': projectName,
      'project_color': projectColor,
      'section_id': sectionId,
      'title': title,
      'body': body,
      'due_on': dueOn.toString(),
      'child_order': childOrder,
      'day_order': dayOrder,
      'evening': evening,
      'completed': completed,
    };
  }
}
