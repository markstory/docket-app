import 'package:english_words/english_words.dart';
import 'package:flutter/material.dart';

import 'screens/login.dart';
import 'screens/projectdetails.dart';
import 'screens/today.dart';
import 'screens/upcoming.dart';
import 'screens/unknown.dart';

void main() {
  runApp(const DocketApp());
}

class DocketApp extends StatelessWidget {
  const DocketApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      onGenerateRoute: (settings) {
        // The named route and the default application route go to Today.
        // Should the user not have a session they are directed to Login.
        if (settings.name == TodayScreen.routeName || settings.name == '/') {
          return MaterialPageRoute(builder: (context) => const LoginRequired(child: TodayScreen()));
        }
        // Upcoming tasks in the next 28 days.
        if (settings.name == UpcomingScreen.routeName) {
          return MaterialPageRoute(builder: (context) => const LoginRequired(child: UpcomingScreen()));
        }
        // Login
        if (settings.name == LoginScreen.routeName) {
          return MaterialPageRoute(builder: (context) => const LoginScreen());
        }
        // Project Detailed View.
        var uri = Uri.parse(settings.name.toString());
        if (uri.pathSegments.length == 2 && uri.pathSegments[0] == 'projects') {
          var slug = uri.pathSegments[1].toString();
          return MaterialPageRoute(builder: (context) => LoginRequired(child: ProjectDetailsScreen(slug)));
        }

        return MaterialPageRoute(builder: (context) => const UnknownScreen());
      },
    );
  }
}

class MyApp extends StatelessWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Welcome to Flutter',
      home: const RandomWords(),
      theme: ThemeData(
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
        ),
      ),
    );
  }
}

class RandomWords extends StatefulWidget {
  const RandomWords({Key? key}) : super(key: key);
  State<RandomWords> createState() => _RandomWordsState();
}

class _RandomWordsState extends State<RandomWords> {
  final _suggestions = <WordPair>[];
  final _biggerFont = const TextStyle(fontSize: 18);
  final _saved = <WordPair>[];

  Widget titleSection = Container(
    padding: const EdgeInsets.all(20),
    child: Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                children: const [
                    Text('Oeschinen Lake Campground!!',
                      style: TextStyle(fontWeight: FontWeight.bold),
                    )
                  ]
                ),
              ),
              Text(
                'Kandersteg, Switzerland',
                style: TextStyle(color: Colors.grey[500]),
              ),
            ]
          )
        ),
        Icon(Icons.today, color: Colors.blue[300]),
        const Text('41'),
        // ListView(children: divided),
      ],
    )
  );

  void _pushSaved() {
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (context) {
          Color color = Theme.of(context).primaryColor;

          Widget buttonSection = Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              _buildButtonColumn(color, Icons.call, 'CALL'),
              _buildButtonColumn(color, Icons.near_me, 'ROUTE'),
              _buildButtonColumn(color, Icons.share, 'SHARE'),
            ]
          );

          Widget textSection = const Padding(
            padding: EdgeInsets.all(32),
            child: Text(
              'Some longer text that goes on for quite some time. And then some more. '
              'Some longer text that goes on for quite some time. And then some more. '
              'Some longer text that goes on for quite some time. And then some more. '
              'Some longer text that goes on for quite some time. And then some more. ',
              softWrap: true,
            )
          );

          final tiles = _saved.map((pair) {
            return ListTile(
              title: Text(pair.asPascalCase, style: _biggerFont),
            );
          });

          final divided = tiles.isNotEmpty
              ? ListTile.divideTiles(
                  context: context,
                  tiles: tiles,
                ).toList()
              : <Widget>[];
          return Scaffold(
            appBar: AppBar(
              title: const Text('Saved Suggestions'),
            ),
            //body: ListView(children: divided),
            body: Column(
              children: [titleSection, buttonSection, textSection]
            )
          );
        }
      )
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Startup Name Generator'),
        actions: [
          IconButton(
            icon: const Icon(Icons.list),
            onPressed: _pushSaved,
            tooltip: 'Saved Suggestions',
          )
        ]
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(16.0),
        itemBuilder: (context, i) {
          if (i.isOdd) {
            return const Divider();
          }
          final index = i ~/ 2;
          if (index >= _suggestions.length) {
            _suggestions.addAll(generateWordPairs().take(10));
          }
          final alreadySaved = _saved.contains(_suggestions[index]);

          return ListTile(
            title: Text(
              _suggestions[index].asPascalCase,
              style: _biggerFont
            ),
            trailing: Icon(
              alreadySaved ? Icons.favorite : Icons.favorite_border,
              color: alreadySaved ? Colors.red : null,
              semanticLabel: alreadySaved ? 'Remove from saved' : 'Save',
            ),
            onTap: () {
              setState(() {
                if (alreadySaved) {
                  _saved.remove(_suggestions[index]);
                } else {
                  _saved.add(_suggestions[index]);
                }
              });
            }
          );
        }
      )
    );
  }

  Column _buildButtonColumn(Color color, IconData icon, String label) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Icon(icon, color: color),
        Container(
          margin: const EdgeInsets.only(top: 8),
          child: Text(
            label,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w400,
              color: color,
            )
          )
        )
      ],
    );
  }
}
