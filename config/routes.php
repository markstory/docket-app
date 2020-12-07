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
    $builder->get('/todos/', 'TodoItems::index', 'todoitems:index');
    $builder->get('/todos/today', ['controller' => 'TodoItems', 'action' => 'index', 'today'], 'todoitems:today');
    $builder->get('/todos/upcoming', ['controller' => 'TodoItems', 'action' => 'index', 'upcoming'], 'todoitems:upcoming');
    $builder->post('/todos/add', 'TodoItems::add', 'todoitems:add');
    $builder->post('/todos/reorder', 'TodoItems::reorder', 'todoitems:reorder');
    $builder->post('/todos/{id}/complete', 'TodoItems::complete', 'todoitems:complete')
        ->setPass(['id']);
    $builder->post('/todos/{id}/incomplete', 'TodoItems::incomplete', 'todoitems:incomplete')
        ->setPass(['id']);
    $builder->post('/todos/{id}/edit', 'TodoItems::edit', 'todoitems:edit')
        ->setPass(['id']);
    $builder->get('/todos/{id}/view', 'TodoItems::view', 'todoitems:view')
        ->setPass(['id']);

    $builder->post('/todos/{id}/subtasks', 'TodoSubtasks::add', 'todosubtasks:add')
        ->setPass(['id']);
    $builder->post('/todos/{id}/subtasks/reorder', 'TodoSubtasks::reorder', 'todosubtasks:reorder')
        ->setPass(['id']);
    $builder->post('/todos/{todoItemId}/subtasks/{id}/toggle', 'TodoSubtasks::toggle', 'todosubtasks:toggle')
        ->setPass(['todoItemId', 'id']);

    $builder->post('/projects/add', 'Projects::add', 'projects:add');
    $builder->post('/projects/reorder', 'Projects::reorder', 'projects:reorder');
    $builder->get('/projects/archived', 'Projects::archived', 'projects:archived');

    $builder->post('/projects/{slug}/archive', 'Projects::archive', 'projects:archive');
    $builder->post('/projects/{slug}/unarchive', 'Projects::unarchive', 'projects:unarchive');
    $builder->connect('/projects/{slug}/edit', 'Projects::edit', ['_name' => 'projects:edit']);
    $builder->get('/projects/{slug}', 'Projects::view', 'projects:view');

    /*
     * Connect catchall routes for all controllers.
     *
     * The `fallbacks` method is a shortcut for
     *
     * ```
     * $builder->connect('/:controller', ['action' => 'index']);
     * $builder->connect('/:controller/:action/*', []);
     * ```
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $builder->fallbacks();
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
