<?php
declare(strict_types=1);

/**
 * @var \Cake\Routing\RouteBuilder $routes
 */
use Cake\Routing\RouteBuilder;

// Routes in the top scope don't have CSRF protection.
$routes->scope('/', function (RouteBuilder $builder): void {
    $builder->post(
        '/google/calendar/notifications',
        'Calendar.GoogleNotifications::update',
        'googlenotification:update'
    );
});

$routes->plugin('Calendar', ['path' => '/'], function (RouteBuilder $builder): void {
    // Tests using loadPlugins don't load application routes
    // this makes it so middleware defined in the application cannot be used
    // in a plugin.
    try {
        $builder->applyMiddleware('csrf');
    } catch (Exception) {
    }

    $builder->scope('/calendars', ['controller' => 'CalendarProviders'], function (RouteBuilder $builder): void {
        $builder->connect(
            '/google/new',
            ['action' => 'createFromGoogle'],
            ['_name' => 'calendarproviders:createfromgoogle'],
        );
        $builder->connect('/', ['action' => 'index'], ['_name' => 'calendarproviders:index']);
        $builder->connect('/{id}/delete', ['action' => 'delete'], ['_name' => 'calendarproviders:delete'])
            ->setPass(['id']);
        $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarproviders:sync')
            ->setPass(['id']);
    });

    $builder->scope(
        '/calendars/{providerId}/sources',
        ['controller' => 'CalendarSources'],
        function (RouteBuilder $builder): void {
            $builder->connect('/add', ['action' => 'add'], ['_name' => 'calendarsources:add'])
                ->setPass(['providerId']);
            $builder->post('/{id}/delete', ['action' => 'delete'], 'calendarsources:delete');
            $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'calendarsources:deleteconfirm');
            $builder->post('/{id}/edit', ['action' => 'edit'], 'calendarsources:edit');
            $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarsources:sync');
            $builder->get('/{id}/view', ['action' => 'view'], 'calendarsources:view');
        },
    );

    $builder->scope('/auth/google', ['controller' => 'GoogleOauth'], function ($builder): void {
        $builder->connect('/authorize', ['action' => 'authorize'], ['_name' => 'googleauth:authorize']);
        $builder->connect('/callback', ['action' => 'callback'], ['_name' => 'googleauth:callback']);
    });
});

// API routes - no csrf, and have json
$routes->plugin('Calendar', ['path' => '/'], function (RouteBuilder $builder): void {
    $builder->setExtensions(['json']);

    $builder->prefix('Api', ['_namePrefix' => 'api:'], function (RouteBuilder $builder): void {
        $builder->scope('/calendars', ['controller' => 'CalendarProviders'], function (RouteBuilder $builder): void {
            $builder->connect(
                '/google/new',
                ['action' => 'createFromGoogle'],
                ['_name' => 'calendarproviders:createfromgoogle'],
            );
            $builder->connect('/', ['action' => 'index'], ['_name' => 'calendarproviders:index']);
            $builder->connect('/{id}/view', ['action' => 'view'], ['_name' => 'calendarproviders:view'])
                ->setPass(['id']);
            $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarproviders:sync')
                ->setPass(['id']);
            $builder->connect('/{id}/delete', ['action' => 'delete'], ['_name' => 'calendarproviders:delete'])
                ->setPass(['id']);
        });

        $builder->scope(
            '/calendars/{providerId}/sources',
            ['controller' => 'CalendarSources'],
            function (RouteBuilder $builder): void {
                $builder->connect('/add', ['action' => 'add'], ['_name' => 'calendarsources:add'])
                    ->setPass(['providerId']);
                $builder->post('/{id}/delete', ['action' => 'delete'], 'calendarsources:delete');
                $builder->post('/{id}/edit', ['action' => 'edit'], 'calendarsources:edit');
                $builder->post('/{id}/sync', ['action' => 'sync'], 'calendarsources:sync');
                $builder->get('/{id}/view', ['action' => 'view'], 'calendarsources:view');
            },
        );
    });
});
