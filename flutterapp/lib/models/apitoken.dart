class ApiToken {
  final int? id;
  final String token;
  final String? lastUsed;

  ApiToken({this.id, required this.token, this.lastUsed});

  factory ApiToken.fromMap(Map<String, dynamic> json) {
    return ApiToken(
      token: json['token'],
      lastUsed: json['last_used'],
    );
  }
  factory ApiToken.fake() {
    return ApiToken(token: 'abc123');
  }

  ApiToken copy({
    String? token,
    String? lastUsed,
  }) {
    return ApiToken(
      token: token ?? this.token,
      lastUsed: lastUsed ?? this.lastUsed,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'token': token,
      'last_used': lastUsed,
    };
  }
}
