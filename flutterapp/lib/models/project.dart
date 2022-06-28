import 'package:docket/models/task.dart';

class Section {
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

class Project {
  String slug;
  String name;
  int color;
  int ranking;
  List<Section> sections;
  int incompleteTaskCount;

  Project({
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
      slug: json['slug'],
      name: json['name'],
      color: json['color'],
      ranking: json['ranking'],
      sections: sections,
      incompleteTaskCount: json['incomplete_task_count'],
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

  const ProjectWithTasks({required this.project, required this.tasks});
}