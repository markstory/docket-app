# Docket App

[![Build Status](https://img.shields.io/github/workflow/status/cakephp/app/CakePHP%20App%20CI/master?style=flat-square)](https://github.com/cakephp/app/actions)

A todo list application. This project began as an exercise in learning to use
Inertia.js, React and CakePHP together,  and to try a new approach to CSS.
Heavily inspired in functionality and design by [Todoist](http://todoist.com)
and [Things](http://culturedcode.com/things).

This project is great if you want to host and operate your personal todo lists
in your personal cloud, where you know exactly what is being done with your
data.

## Installation

1. Clone this repository.
2. Download [Composer](https://getcomposer.org/doc/00-intro.md) or update `composer self-update`.
3. Ensure you have a nodejs environment with yarn.
4. Install dependencies:
   ```
   php composer.phar install
   yarn install
   ```

You're now ready to build the assets:

```
yarn build
```

next setup your database configuration in `config/app_local.php`. Docket is tested against
MySQL, Postgres and SQLite. Then run migrations:

```php
bin/cake migrations migrate
```

You can now serve Docket using either the built-in PHP webserver, or more robust
webserver like Apache or Nginx.

To use the built-in PHP server run:

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the login page.

## Configuration

Read and edit the environment specific `config/app_local.php` and setup the 
`'Datasources'` and any other configuration relevant for your application.
Other environment agnostic settings can be changed in `config/app.php`.
