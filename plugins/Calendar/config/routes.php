<?php
declare(strict_types=1);
/**
 * @var \Cake\Routing\RouteBuilder $routes
 */

use Cake\Routing\RouteBuilder;

$routes->plugin('Calendar', ['path' => '/'], function (RouteBuilder $builder) {
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
});
