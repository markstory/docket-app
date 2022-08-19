import 'package:docket/models/task.dart';

class Section {
  static int root = -1;

  int id;
  String name;
  int ranking;

  Section({
    required this.id,
    required this.name,
    required this.ranking,
  });

  factory Section.fromMap(Map<String, dynamic> json) {
    return Section(
      id: json['id'],
      name: json['name'],
      ranking: json['ranking'],
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'name': name,
      'ranking': ranking,
    };
  }
}

class SectionWithTasks {
  final Section? section;
  final List<Task> tasks;

  SectionWithTasks({required this.section, required this.tasks});
}

class Project {
  int id;
  String slug;
  String name;
  int color;
  int ranking;
  List<Section> sections;
  int incompleteTaskCount;

  Project({
    this.id = 0,
    required this.slug,
    required this.name,
    required this.color,
    required this.ranking,
    this.sections = const [],
    this.incompleteTaskCount = 0,
  });

  factory Project.fromMap(Map<String, dynamic> json) {
    List<Section> sections = [];
    if (json['sections'] != null && json['sections'].runtimeType == List) {
      for (var item in json['sections']) {
        sections.add(Section.fromMap(item));
      }
    }
    return Project(
      id: json['id'],
      slug: json['slug'],
      name: json['name'],
      color: json['color'],
      ranking: json['ranking'],
      sections: sections,
      incompleteTaskCount: json['incomplete_task_count'] ?? 0,
    );
  }

  factory Project.blank() {
    return Project(
      id: 0,
      slug: '',
      name: '',
      color: 0,
      sections: [],
      incompleteTaskCount: 0,
      ranking: 0,
    );
  }

  Project copy({
    int? id,
    String? slug,
    String? name,
    int? color,
    int? ranking,
    List<Section>? sections,
    int? incompleteTaskCount,
  }) {
    return Project(
      id: id ?? this.id,
      slug: slug ?? this.slug,
      name: name ?? this.name,
      color: color ?? this.color,
      ranking: ranking ?? this.ranking,
      sections: sections ?? this.sections,
      incompleteTaskCount: incompleteTaskCount ?? this.incompleteTaskCount,
    );
  }

  Map<String, Object?> toMap() {
    var sectionInstances = sections.map((section) => section.toMap()).toList();
    return {
      'id': id,
      'slug': slug,
      'name': name,
      'color': color,
      'ranking': ranking,
      'sections': sectionInstances,
      'incomplete_task_count': incompleteTaskCount,
    };
  }
}

class ProjectWithTasks {
  final Project project;
  final List<Task> tasks;

  // Whether the view cache read failed.
  final bool missingData;

  // Whether or not a data refresh is pending
  bool pending;

  ProjectWithTasks({
    required this.project,
    required this.tasks,
    this.missingData = false,
    this.pending = false
  });

  factory ProjectWithTasks.fromMap(Map<String, dynamic> map) {
    List<Task> tasks = (map['tasks'] as List? ?? []).map((data) => Task.fromMap(data)).toList();
    Project project = Project.fromMap(map['project'] ?? {});

    return ProjectWithTasks(
      project: project,
      tasks: tasks,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'project': project.toMap(),
      'tasks': tasks.map((task) => task.toMap()).toList(),
    };
  }
}
