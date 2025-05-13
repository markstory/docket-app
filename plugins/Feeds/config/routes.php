<?php
declare(strict_types=1);
/**
 * @var \Cake\Routing\RouteBuilder $routes
 */

use Cake\Routing\RouteBuilder;

// Feeds routes
$routes->plugin('Feeds', ['path' => '/'], function (RouteBuilder $builder): void {
    $builder->scope('/feeds/', ['controller' => 'FeedSubscriptions'], function (RouteBuilder $builder) {
        $builder->get('/', ['action' => 'home'], 'feedsubscriptions:home');
        $builder->get('/list', ['action' => 'index'], 'feedsubscriptions:index');
        $builder->post('/items/mark-read', ['action' => 'itemsMarkRead'], 'feedsubscriptions:itemsmarkread');

        $builder->get('/{id}/view', ['action' => 'view'], 'feedsubscriptions:view')
            ->setPass(['id']);
        $builder->post('/{id}/sync', ['action' => 'sync'], 'feedsubscriptions:sync')
            ->setPass(['id']);
        $builder->connect('/add', ['action' => 'add'], ['_name' => 'feedsubscriptions:add']);
        $builder->connect('/discover', ['action' => 'discover'], ['_name' => 'feedsubscriptions:discover']);
        $builder->connect('/{id}/edit', ['action' => 'edit'], ['_name' => 'feedsubscriptions:edit'])
            ->setPass(['id']);

        $builder->post('/{id}/delete', ['action' => 'delete'], 'feedsubscriptions:delete')
            ->setPass(['id']);
        $builder->get('/{id}/delete/confirm', ['action' => 'deleteConfirm'], 'feedsubscriptions:deleteconfirm')
            ->setPass(['id']);

        $builder->get('{id}/items/{itemId}', ['action' => 'viewItem'], 'feedsubscriptions:viewitem')
            ->setPass(['id', 'itemId']);
        $builder->get('/{id}/read-visit/{itemId}', ['action' => 'readVisit'], 'feedsubscriptions:readvisit')
            ->setPass(['id', 'itemId']);
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
        $builder->post('/{id}/toggle-expanded', ['action' => 'toggleExpanded'], 'feedcategories:toggleexpanded')
            ->setPass(['id']);
    });
});
