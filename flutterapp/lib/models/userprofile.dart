class UserProfile {
  final String name;
  final String email;
  final String theme;
  final String timezone;
  final String avatarHash;

  UserProfile({
    required this.name,
    required this.email,
    required this.theme,
    required this.timezone,
    required this.avatarHash
  });

  factory UserProfile.fromMap(Map<String, dynamic> json) {
    return UserProfile(
      name: json['name'],
      email: json['email'],
      theme: json['theme'],
      timezone: json['timezone'],
      avatarHash: json['avatar_hash'],
    );
  }

  factory UserProfile.blank() {
    return UserProfile(
      name: '',
      email: '',
      theme: 'system',
      timezone: '',
      avatarHash: '',
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
