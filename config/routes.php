<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use App\Middleware\ApiCsrfProtectionMiddleware;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/** @var \Cake\Routing\RouteBuilder $routes */
$routes->setRouteClass(DashedRoute::class);

// Cross Site Request Forgery (CSRF) Protection Middleware
$routes->registerMiddleware('csrf', new ApiCsrfProtectionMiddleware());

// API Routes
$routes->prefix('Api', ['_namePrefix' => 'api:'], function (RouteBuilder $builder) {
    $builder->setExtensions(['json']);

    $builder->scope('/tasks', ['controller' => 'Tasks'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'tasks:index');
        $builder->get('/today', ['action' => 'daily', 'today'], 'tasks:today');
        $builder->get('/upcoming', ['action' => 'index', 'upcoming'], 'tasks:upcoming');
        $builder->get('/deleted', ['action' => 'deleted'], 'tasks:deleted');

        $builder->get('/day/{date}', ['action' => 'daily'], 'tasks:daily')
            ->setPass(['date']);

        $builder->post('/add', ['action' => 'add'], 'tasks:add');

        $builder->post('/{id}/complete', ['action' => 'complete'], 'tasks:complete')
            ->setPass(['id']);
        $builder->delete('/{id}/complete', ['action' => 'complete'])->setPass(['id']);
        $builder->post('/{id}/incomplete', ['action' => 'incomplete'], 'tasks:incomplete')
            ->setPass(['id']);
        $builder->delete('/{id}/incomplete', ['action' => 'incomplete'])->setPass(['id']);

        $builder->post('/{id}/delete', ['action' => 'delete'], 'tasks:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'tasks:deleteconfirm')
            ->setPass(['id']);
        $builder->post('/{id}/undelete', ['action' => 'undelete'], 'tasks:undelete')
            ->setPass(['id']);

        $builder->post('/{id}/edit', ['action' => 'edit'], 'tasks:edit')
            ->setPass(['id']);
        $builder->put('/{id}/edit', ['action' => 'edit'])->setPass(['id']);

        $builder->get('/{id}/view', ['action' => 'view'], 'tasks:view')
            ->setPass(['id']);
        $builder->post('/{id}/move', ['action' => 'move'], 'tasks:move')
            ->setPass(['id']);
    });

    $builder->scope('/projects', ['controller' => 'Projects'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'projects:index');
        $builder->post('/add', ['action' => 'add'], 'projects:add');
        $builder->get('/archived', ['action' => 'archived'], 'projects:archived');
        $builder->post('/reorder', ['action' => 'reorder'], 'projects:reorder');

        $builder->get('/{slug}', ['action' => 'view'], 'projects:view')
            ->setPass(['slug']);
        $builder->post('/{slug}/delete', ['action' => 'delete'], 'projects:delete')
            ->setPass(['slug']);
        $builder->post('/{slug}/archive', ['action' => 'archive'], 'projects:archive')
            ->setPass(['slug']);
        $builder->post('/{slug}/unarchive', ['action' => 'unarchive'], 'projects:unarchive')
            ->setPass(['slug']);
        $builder->post('/{slug}/move', ['action' => 'move'], 'projects:move')
            ->setPass(['slug']);
        $builder->post('/{slug}/edit', ['action' => 'edit'], 'projects:edit')
            ->setPass(['slug']);
    });

    $builder->scope(
        '/projects/{projectSlug}/sections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder) {
            $builder->connect('/', ['action' => 'add'], ['_name' => 'projectsections:add'])
                ->setPass(['projectSlug']);
            $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'projectsections:edit'])
                ->setPass(['projectSlug', 'id']);
            $builder->get('/{id}/view', ['action' => 'view'], 'projectsections:view')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/move', ['action' => 'move'], 'projectsections:move')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/delete', ['action' => 'delete'], 'projectsections:delete')
                ->setPass(['projectSlug', 'id']);
        }
    );
    $builder->scope(
        '/projectsections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder) {
            $builder->get('/options', ['action' => 'options'], 'projectsections:options');
        }
    );

    $builder->scope('/tasks/{taskId}/subtasks', ['controller' => 'Subtasks'], function ($builder) {
        $builder->post('/', ['action' => 'add'], 'subtasks:add')
            ->setPass(['taskId']);
        $builder->post('/{id}/edit', ['action' => 'edit'], 'subtasks:edit')
            ->setPass(['taskId', 'id']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'subtasks:delete')
            ->setPass(['taskId', 'id']);
        $builder->post('/{id}/toggle', ['action' => 'toggle'], 'subtasks:toggle')
            ->setPass(['taskId', 'id']);
        $builder->post('/{id}/move', ['action' => 'move'], 'subtasks:move')
            ->setPass(['taskId', 'id']);
    });

    $builder->scope('/users', ['controller' => 'Users'], function ($builder) {
        $builder->connect('/profile/', ['action' => 'edit'], ['_name' => 'users:edit']);
    });

    $builder->scope('/tokens', ['controller' => 'ApiTokens'], function ($builder) {
        $builder->get('/', ['action' => 'index'], 'apitokens:index');
        $builder->post('/add', ['action' => 'add'], 'apitokens:add');
        $builder->delete('/{token}/delete', ['action' => 'delete'], 'apitokens:delete')
            ->setPass(['token']);
    });

    $builder->scope('/calendars', ['controller' => 'CalendarProviders'], function ($builder) {
        $builder->connect('/google/new', ['action' => 'createFromGoogle'], ['_name' => 'calendarproviders:createfromgoogle']);
        $builder->connect('/', ['action' => 'index'], ['_name' => 'calendarproviders:index']);
        $builder->connect('/{id}/view', ['action' => 'view'], ['_name' => 'calendarproviders:view'])
            ->setPass(['id']);
        $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarproviders:sync')
            ->setPass(['id']);
        $builder->connect('/{id}/delete', ['action' => 'delete'], ['_name' => 'calendarproviders:delete'])
            ->setPass(['id']);
    });

    $builder->scope('/calendars/{providerId}/sources', ['controller' => 'CalendarSources'], function ($builder) {
        $builder->connect('/add', ['action' => 'add'], ['_name' => 'calendarsources:add'])
            ->setPass(['providerId']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'calendarsources:delete');
        $builder->post('/{id}/edit', ['action' => 'edit'], 'calendarsources:edit');
        $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarsources:sync');
        $builder->get('/{id}/view', ['action' => 'view'], 'calendarsources:view');
    });
});

// HTMX Application routes
$routes->scope('/', function (RouteBuilder $builder) {
    $builder->applyMiddleware('csrf');

    $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
    $builder->connect('/pages/*', 'Pages::display');
    $builder->connect('/manifest.json', ['controller' => 'Pages', 'action' => 'display', 'manifest']);

    $builder->connect('/login/', 'Users::login', ['_name' => 'users:login']);
    $builder->get('/logout/', 'Users::logout', 'users:logout');

    $builder->scope('/tasks', ['controller' => 'Tasks'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'tasks:index');
        $builder->get('/today', ['action' => 'daily', 'today'], 'tasks:today');
        $builder->get('/upcoming', ['action' => 'index', 'upcoming'], 'tasks:upcoming');
        $builder->get('/deleted', ['action' => 'deleted'], 'tasks:deleted');

        $builder->get('/day/{date}', ['action' => 'daily'], 'tasks:daily')
            ->setPass(['date']);

        $builder->connect('/add', ['action' => 'add'], ['_name' => 'tasks:add']);

        // HTMX uses delete to change completion status as the success
        // means the element needs to be removed from the client state.
        $builder->post('/{id}/complete', ['action' => 'complete'], 'tasks:complete')
            ->setPass(['id']);
        $builder->delete('/{id}/complete', ['action' => 'complete'])->setPass(['id']);
        $builder->post('/{id}/incomplete', ['action' => 'incomplete'], 'tasks:incomplete')
            ->setPass(['id']);
        $builder->delete('/{id}/incomplete', ['action' => 'incomplete'])->setPass(['id']);

        $builder->post('/{id}/delete', ['action' => 'delete'], 'tasks:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'tasks:deleteconfirm')
            ->setPass(['id']);
        $builder->post('/{id}/undelete', ['action' => 'undelete'], 'tasks:undelete')
            ->setPass(['id']);

        $builder->post('/{id}/edit', ['action' => 'edit'], 'tasks:edit')
            ->setPass(['id']);
        $builder->put('/{id}/edit', ['action' => 'edit'])->setPass(['id']);

        $builder->get('/{id}/view', ['action' => 'view'], 'tasks:view')
            ->setPass(['id']);
        $builder->get('/{id}/view/{mode}', ['action' => 'view'], 'tasks:viewmode')
            ->setPass(['id', 'mode']);
        $builder->post('/{id}/move', ['action' => 'move'], 'tasks:move')
            ->setPass(['id']);
    });

    $builder->scope('/projects', ['controller' => 'Projects'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'projects:index');
        $builder->connect('/add', 'Projects::add', ['_name' => 'projects:add']);
        $builder->get('/archived', ['action' => 'archived'], 'projects:archived');
        $builder->post('/reorder', ['action' => 'reorder'], 'projects:reorder');

        $builder->get('/{slug}', ['action' => 'view'], 'projects:view')
            ->setPass(['slug']);
        $builder->post('/{slug}/delete', ['action' => 'delete'], 'projects:delete')
            ->setPass(['slug']);
        $builder->get('/{slug}/delete/confirm', ['action' => 'deleteConfirm'], 'projects:deleteConfirm')
            ->setPass(['slug']);
        $builder->post('/{slug}/archive', ['action' => 'archive'], 'projects:archive')
            ->setPass(['slug']);
        $builder->post('/{slug}/unarchive', ['action' => 'unarchive'], 'projects:unarchive')
            ->setPass(['slug']);
        $builder->post('/{slug}/move', ['action' => 'move'], 'projects:move')
            ->setPass(['slug']);
        $builder->connect('/{slug}/edit', ['action' => 'edit'], ['_name' => 'projects:edit'])
            ->setPass(['slug']);
    });

    $builder->scope(
        '/projects/{projectSlug}/sections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder) {
            $builder->connect('/', ['action' => 'add'], ['_name' => 'projectsections:add'])
                ->setPass(['projectSlug']);
            $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'projectsections:edit'])
                ->setPass(['projectSlug', 'id']);
            $builder->get('/{id}/view', ['action' => 'view'], 'projectsections:view')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/move', ['action' => 'move'], 'projectsections:move')
                ->setPass(['projectSlug', 'id']);
            $builder->post('/{id}/delete', ['action' => 'delete'], 'projectsections:delete')
                ->setPass(['projectSlug', 'id']);
            $builder->connect(
                '/{id}/delete/confirm',
                ['action' => 'deleteConfirm'],
                ['_name' => 'projectsections:deleteconfirm']
            )
            ->setPass(['projectSlug', 'id']);
        }
    );

    $builder->scope(
        '/projectsections',
        ['controller' => 'ProjectSections'],
        function (RouteBuilder $builder) {
            $builder->get('/options', ['action' => 'options'], 'projectsections:options');
        }
    );

    $builder->scope('/users', ['controller' => 'Users'], function (RouteBuilder $builder) {
        $builder->connect('/add/', ['action' => 'add'], ['_name' => 'users:add']);
        $builder->connect('/profile/', ['action' => 'edit'], ['_name' => 'users:edit']);
        $builder->connect('/updatePassword/', ['action' => 'updatePassword'], ['_name' => 'users:updatePassword']);
        $builder->get('/verifyEmail/{token}', ['action' => 'verifyEmail'], 'users:verifyEmail')
            ->setPass(['token']);
        $builder->connect('/profileMenu/', ['action' => 'profileMenu'], ['_name' => 'users:profileMenu']);
    });

    $builder->scope('/password', ['controller' => 'Users'], function (RouteBuilder $builder) {
        $builder->connect('/reset', ['action' => 'resetPassword'], ['_name' => 'users:passwordReset']);
        $builder->connect('/new/{token}', ['action' => 'newPassword'], ['_name' => 'users:newPassword'])
            ->setPass(['token']);
    });

    $builder->scope('/calendars', ['controller' => 'CalendarProviders'], function (RouteBuilder $builder) {
        $builder->connect('/google/new', ['action' => 'createFromGoogle'], ['_name' => 'calendarproviders:createfromgoogle']);
        $builder->connect('/', ['action' => 'index'], ['_name' => 'calendarproviders:index']);
        $builder->connect('/{id}/delete', ['action' => 'delete'], ['_name' => 'calendarproviders:delete'])
            ->setPass(['id']);
        $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarproviders:sync')
            ->setPass(['id']);
    });

    $builder->scope('/calendars/{providerId}/sources', ['controller' => 'CalendarSources'], function (RouteBuilder $builder) {
        $builder->connect('/add', ['action' => 'add'], ['_name' => 'calendarsources:add'])
            ->setPass(['providerId']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'calendarsources:delete');
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'calendarsources:deleteconfirm');
        $builder->post('/{id}/edit', ['action' => 'edit'], 'calendarsources:edit');
        $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarsources:sync');
        $builder->get('/{id}/view', ['action' => 'view'], 'calendarsources:view');
    });

    $builder->scope('/auth/google', ['controller' => 'GoogleOauth'], function ($builder) {
        $builder->connect('/authorize', ['action' => 'authorize'], ['_name' => 'googleauth:authorize']);
        $builder->connect('/callback', ['action' => 'callback'], ['_name' => 'googleauth:callback']);
    });

    // Feeds routes
    $builder->scope('/feeds/', ['controller' => 'FeedSubscriptions'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'feedsubscriptions:index');
        $builder->connect('/add', ['action' => 'add'], ['_name' => 'feedsubscriptions:add']);
        $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'feedsubscriptions:edit'])
            ->setPass(['id']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'feedsubscriptions:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'feedsubscriptions:deleteconfirm')
            ->setPass(['id']);
    });
    $builder->scope('/feeds/categories', ['controller' => 'FeedCategories'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'index'], 'feedcategories:index');
        $builder->connect('/add', ['action' => 'add'], ['_name' => 'feedcategories:add']);
        $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'feedcategories:edit'])
            ->setPass(['id']);
        // TODO use slug here.
        $builder->connect('/{id}/view', ['action' => 'view'], ['_name' => 'feedcategories:view'])
            ->setPass(['id']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'feedcategories:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'feedcategories:deleteconfirm')
            ->setPass(['id']);
        $builder->post('/reorder', ['action' => 'reorder'], 'feedcategories:reorder');
    });
});

// Routes in this scope don't have CSRF protection.
$routes->scope('/', function (RouteBuilder $builder) {
    $builder->post('/google/calendar/notifications', 'GoogleNotifications::update', 'googlenotification:update');
});
