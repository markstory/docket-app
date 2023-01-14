import 'dart:convert';

import 'package:flutter_appauth/flutter_appauth.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show rootBundle;

import 'package:docket/actions.dart' as actions;
import 'package:docket/database.dart';
import 'package:docket/models/calendarprovider.dart';


class CalendarProviderListViewModel extends ChangeNotifier {
  late LocalDatabase _database;

  /// Whether data is being refreshed from the server or local cache.
  bool _loading = false;

  /// Calendar providers list
  List<CalendarProvider> _providers = [];

  CalendarProviderListViewModel(LocalDatabase database) {
    _database = database;
    _database.calendarList.addListener(listener);
    _providers = [];
  }

  @override
  void dispose() {
    _database.calendarList.removeListener(listener);
    super.dispose();
  }

  void listener() {
    refresh();
  }

  bool get loading => _loading;
  List<CalendarProvider> get providers => _providers;

  /// Load data. Should be called during initState()
  Future<void> loadData() async {
    await fetchData();

    if (!_loading && (_providers.isEmpty || !_database.calendarList.isFresh())) {
      return refresh();
    }
  }

  Future<void> fetchData() async {
    _loading = true;
    var result = await _database.calendarList.get();
    if (result != null && result.isNotEmpty) {
      _providers = result;
    }
    _loading = false;

    notifyListeners();
  }

  /// Refresh from the server and notify.
  Future<void> refresh() async {
    _loading = true;

    var result = await actions.fetchCalendarProviders(_database.apiToken.token);
    await _database.calendarList.set(result);
    _providers = result;
    _loading = false;

    notifyListeners();
  }

  /// Delete the provider from the server and notify.
  Future<void> delete(CalendarProvider provider) async {
    await actions.deleteCalendarProvider(_database.apiToken.token, provider);
    await _database.calendarList.remove(provider.id);
    await _database.calendarDetails.remove(provider.id);

    notifyListeners();
  }

  /// Expire the list view so that on the next rebuild
  void expire() {
    _database.calendarList.expire();
  }

  Future<ClientCredentials> clientConfig() async {
    var jsonData = await rootBundle.loadString('assets/google-auth.json');
    var data = jsonDecode(jsonData);

    return ClientCredentials(data['clientId'], data['redirectUri']);
  }

  Future<AuthorizationTokenResponse?> addGoogleAccount() async {
    var config = await clientConfig();
    const appAuth = FlutterAppAuth();
    var result = await appAuth.authorizeAndExchangeCode(
      AuthorizationTokenRequest(
        config.clientId,
        config.redirectUri,
        discoveryUrl: 'https://accounts.google.com/.well-known/openid-configuration',
        scopes: [
          'https://www.googleapis.com/auth/userinfo.email',
          'https://www.googleapis.com/auth/calendar.events.readonly',
          'https://www.googleapis.com/auth/calendar.readonly',
        ]
      )
    );
    if (result == null) {
      // TODO set an error?
      print('Authentication failed');
    }

    return result;
  }

  Future<void> createFromGoogle(AuthorizationTokenResponse token) async {
    print('result token=${token.accessToken} refresh=${token.refreshToken}');
    var provider = await actions.createCalendarProvider(
      _database.apiToken.token,
      accessToken: token.accessToken,
      refreshToken: token.refreshToken,
    );
    print("provider created ${provider.toMap()}");
    _database.calendarList.add(provider);

    notifyListeners();
  }

  /*
  Future<void> addGoogleAccountGoogleSignIn() async {
    var googleService = GoogleSignIn(
      serverClientId: await serverClientId(),
      forceCodeForRefreshToken: true,
      scopes: [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/calendar.events.readonly',
        'https://www.googleapis.com/auth/calendar.readonly',
      ],
    );
    // TODO find a way to stub this. Could use a global value?
    var account = await googleService.signIn();
    if (account == null) {
      print('login failed');
      return;
    }
    var auth = await account.authentication;
    print("account name=${account.displayName} authCode=${auth.serverAuthCode} id_len=${auth.idToken?.length ?? 0} accessToken=${auth.accessToken}");
    print("id=${auth.idToken}");

    var provider = await actions.createCalendarProvider(
      _database.apiToken.token,
      // idToken: auth.idToken,
      accessToken: auth.accessToken,
    );

    print("provider created ${provider.toMap()}");
  }
  */
}

class ClientCredentials {
  final String clientId;
  final String redirectUri;

  const ClientCredentials(this.clientId, this.redirectUri);
}
