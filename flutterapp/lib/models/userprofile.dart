import 'package:adaptive_theme/adaptive_theme.dart';

class UserProfile {
  String name;
  String email;
  String theme;
  String timezone;
  String avatarHash;

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

  get themeMode {
    if (theme == 'light') {
      return AdaptiveThemeMode.light;
    }
    if (theme == 'dark') {
      return AdaptiveThemeMode.dark;
    }
    return AdaptiveThemeMode.system;
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
