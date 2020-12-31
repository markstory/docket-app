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

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/*
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 */
/** @var \Cake\Routing\RouteBuilder $routes */
$routes->setRouteClass(DashedRoute::class);

$routes->scope('/', function (RouteBuilder $builder) {
    /*
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, templates/Pages/home.php)...
     */
    $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
    $builder->connect('/pages/*', 'Pages::display');

    $builder->connect('/login/', 'Users::login', ['_name' => 'users:login']);
    $builder->get('/logout/', 'Users::logout', 'users:logout');

    $builder->scope('/todos', ['controller' => 'Tasks'], function ($builder) {
        $builder->get('/', ['action' => 'index'], 'tasks:index');
        $builder->get('/today', ['action' => 'index', 'today'], 'tasks:today');
        $builder->get('/upcoming', ['action' => 'index', 'upcoming'], 'tasks:upcoming');

        $builder->post('/add', ['action' => 'add'], 'tasks:add');
        $builder->post('/{id}/complete', ['action' => 'complete'], 'tasks:complete')
            ->setPass(['id']);
        $builder->post('/{id}/incomplete', ['action' => 'incomplete'], 'tasks:incomplete')
            ->setPass(['id']);
        $builder->post('/{id}/delete', ['action' => 'delete'], 'tasks:delete')
            ->setPass(['id']);
        $builder->post('/{id}/edit', ['action' => 'edit'], 'tasks:edit')
            ->setPass(['id']);
        $builder->get('/{id}/view', ['action' => 'view'], 'tasks:view')
            ->setPass(['id']);
        $builder->post('/{id}/move', ['action' => 'move'], 'tasks:move')
            ->setPass(['id']);
    });

    $builder->scope('/projects', ['controller' => 'Projects'], function ($builder) {
        $builder->connect('/add', 'Projects::add', ['_name' => 'projects:add']);
        $builder->get('/archived', ['action' => 'archived'], 'projects:archived');
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
        $builder->connect('/{slug}/edit', ['action' => 'edit'], ['_name' => 'projects:edit'])
            ->setPass(['slug']);
    });

    $builder->scope('/todos/{taskId}/subtasks', ['controller' => 'Subtasks'], function ($builder) {
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
        $builder->get('/verifyEmail/{token}', ['action' => 'verifyEmail'], 'users:verifyEmail')
            ->setPass(['token']);
    });
});

/*
 * If you need a different set of middleware or none at all,
 * open new scope and define routes there.
 *
 * ```
 * $routes->scope('/api', function (RouteBuilder $builder) {
 *     // No $builder->applyMiddleware() here.
 *     
 *     // Parse specified extensions from URLs
 *     // $builder->setExtensions(['json', 'xml']);
 *     
 *     // Connect API actions here.
 * });
 * ```
 */
