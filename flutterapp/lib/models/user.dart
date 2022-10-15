class User {
  final String name;
  final String email;
  final String theme;
  final String timezone;
  final String avatarHash;

  User({required this.name, required this.email, required this.theme, required this.timezone, required this.avatarHash});

  factory User.fromMap(Map<String, dynamic> json) {
    return User(
      name: json['name'] ?? '',
      email: json['email'] ?? '',
      theme: json['theme'] ?? '',
      timezone: json['timezone'] ?? '',
      avatarHash: json['avatar_hash'] ?? '',
    );
  }

  Map<String, Object?> toMap() {
    return {
      'name': name,
      'email': email,
      'theme': theme,
      'timezone': timezone,
      'avatar_hash': avatarHash,
    };
  }
}
