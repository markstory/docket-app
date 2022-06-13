class ApiToken {
  final int? id;
  final String token;
  final String? lastUsed;

  ApiToken({this.id, required this.token, required this.lastUsed});

  factory ApiToken.fromMap(Map<String, dynamic> json) {
    return ApiToken(
      id: json['id'],
      token: json['token'],
      lastUsed: json['last_used'],
    );
  }

  ApiToken copy({
    int? id,
    String? token,
    String? lastUsed,
  }) {
    return ApiToken(
      id: id ?? this.id,
      token: token ?? this.token,
      lastUsed: lastUsed ?? this.lastUsed,
    );
  }

  Map<String, Object?> toMap() {
    return {
      'id': id,
      'token': token,
      'last_used': lastUsed,
    };
  }
}
