<h1>
    <img src="https://raw.githubusercontent.com/markstory/docket-app/master/webroot/img/docket-logo.svg" width="75" height="75" style="margin-right: 40px" />
    &nbsp;
    Docket App
</h1>

[![Build Status](https://img.shields.io/github/actions/workflow/status/markstory/docket-app/ci.yml?branch=master)](https://github.com/markstory/docket-app/actions)
[![codecov](https://codecov.io/gh/markstory/docket-app/branch/master/graph/badge.svg?token=0dPEbBPsQ3)](https://codecov.io/gh/markstory/docket-app)

A personal task list & feed reader application. This project is built with [cakephp](https://cakephp.org), [htmx](https://htmx.org), and webcomponents in an effort to learn about building interactive applications with simpler frontend tools. The task list functionality is inspired by [Todoist](http://todoist.com) and [Things](http://culturedcode.com/things).

This project is great if you want to host and operate your personal todo lists on your own servers, where you know exactly what is being done with your data.

## Installation & Getting Started

1. Clone this repository.
2. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
3. Ensure you have a nodejs environment with yarn.
4. Install dependencies:
   ```
   php composer.phar install
   yarn install
   ```
5. Build static assets to generate a `manifest.json` file used to generate URLs
   to generated UI assets.
   ```
   yarn build
   ```
6. Start up the development server:
   ```
   node bin/server.js
   ```
7. Visit `localhost:8765` to get started.

Docket is tested against MySQL, Postgres and SQLite, and will work equally well
on all of them.  Create your database, and ensure your database is encoded as
`UTF8` or `utf8mb4` if you are using MySQL. Next setup your database
configuration in `config/app_local.php`; then run migrations:

```php
bin/cake migrations migrate
```

You can now serve Docket using either the built-in PHP webserver, or more robust
webserver like Apache or Nginx.

To use the built-in PHP server run:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the landing page, and create your account.

## Configuration

While you shouldn't need to configure much, if you do want to tweak settings,
the `config/app_local.php` file is where you should make any changes specific to
your setup. During application start, this file is loaded and merged onto the
defaults in `config/app.php`.

## Local Development

Using `node bin/server.js` you can run a PHP webserver and vite in watch mode.
This is the recommended development environment for docket.

## Testing

Server tests can be run via `phpunit`. By default only the functional and
integration tests are run. Docket also uses
[panther](https://github.com/symfony/panther) to do automated browser testing.
Running acceptance tests requires you to have a browser driver available. Follow
the installation guide in panther to get started.  Once you have a driver
installed acceptance tests can be run using `phpunit`:

```sh
phpunit --testsuite=acceptance
```

Javascript tests can be run via `jest`:

```sh
yarn test
```

Flutter tests can be run via `flutter`:

```sh
cd flutterapp
flutter test
```

## Google Calendar Integration

Docket offers a google calendar integration that will automatically sync calendar events into your 'today' and 'upcoming' views. This is a great way to have your meetings and appointments alongside your tasks.

## Configuring Google Calendar

The google calendar integration has a fairly involved setup, as you will need to create a google OAuth consumer.

1. Ensure your Docket install is running under an HTTPs connection.
2. Get your site 'verified' in google search console.
3. Create an [API Application in Google](https://cloud.google.com/docs/authentication/end-user). Your OAuth Client application and its accompanying 'Consent Screen' will need to:
    1. Use the `$your_domain/auth/google/callback` as its redirect URI.
    2. Request the `calendar.readonly` , `calendar.events.readonly`, and `userinfo.email` scopes.
    3. Add your verified site to your API application.
4. Next, download the credentials file for your application and save it into `config/google-auth.json`. You should take care to make sure this file is encrypted if it is added to any repositories.

If you've managed to do all of that you *should* have a working google calendar integration, and you should be able to go to *Profile Menu > Calendars* and add your google account.

If not, I'm sorry but google has made this really hard and you'll have to keep trying until you get it working. There isn't much I can do to help either.

## Automatic Syncing

Adding a calendar to docket will also create a 'watch' subscription that will receive updates from google. Subscriptions only last around a week after which they need to be renewed. You can add the following command to run as a daily cron job:

```bash
bin/cake calendar_subscription_renew
```

This command will iterate all the subscriptions that will expire in the next day and create a new subscription for those calendar sources.

## Flutter + Google Auth

When building the flutter application you need another OAuth Client ID for
the flutter application.

1. Setup a keystore file. I [followed this tutorial](https://docs.flutter.dev/deployment/android#create-an-upload-keystore).
2. Fill out `key.properties`. It should look like
   ```
   keyAlias=upload
   keyPassword=androidpassword
   storeFile=/Users/markstory/code/docket-app/flutterapp/upload-keystore.jks
   storePassword=androidpassword
   ```
3. Populate `assets/google-auth.json`. It should look like
   ```
   {
    "clientId": "a-big-number.apps.googleusercontent.com",
    "redirectUrl": "com.docket.flutterapp"
   }
   ```

You should now be able to generate an android build.

This [video](https://www.youtube.com/watch?app=desktop&v=E5WgU6ERZzA) was fantastic and helped me
get through the setup pain of this integration.


