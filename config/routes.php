<?php
declare(strict_types=1);

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
$routes->prefix('Api', ['_namePrefix' => 'api:'], function (RouteBuilder $builder): void {
    // See plugins for more api routes
    $builder->setExtensions(['json']);

    $builder->scope('/users', ['controller' => 'Users'], function ($builder): void {
        $builder->connect('/profile/', ['action' => 'edit'], ['_name' => 'users:edit']);
    });

    $builder->scope('/tokens', ['controller' => 'ApiTokens'], function ($builder): void {
        $builder->get('/', ['action' => 'index'], 'apitokens:index');
        $builder->post('/add', ['action' => 'add'], 'apitokens:add');
        $builder->delete('/{token}/delete', ['action' => 'delete'], 'apitokens:delete')
            ->setPass(['token']);
    });
});

// HTMX Application routes
$routes->scope('/', function (RouteBuilder $builder): void {
    $builder->applyMiddleware('csrf');

    $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);
    $builder->connect('/pages/*', 'Pages::display');
    $builder->connect('/manifest.json', ['controller' => 'Pages', 'action' => 'display', 'manifest']);

    $builder->connect('/login/', 'Users::login', ['_name' => 'users:login']);
    $builder->get('/logout/', 'Users::logout', 'users:logout');

    $builder->scope('/users', ['controller' => 'Users'], function (RouteBuilder $builder): void {
        $builder->connect('/add/', ['action' => 'add'], ['_name' => 'users:add']);
        $builder->connect('/profile/', ['action' => 'edit'], ['_name' => 'users:edit']);
        $builder->connect('/updatePassword/', ['action' => 'updatePassword'], ['_name' => 'users:updatePassword']);
        $builder->get('/verifyEmail/{token}', ['action' => 'verifyEmail'], 'users:verifyEmail')
            ->setPass(['token']);
        $builder->connect('/profileMenu/', ['action' => 'profileMenu'], ['_name' => 'users:profileMenu']);
    });

    $builder->scope('/password', ['controller' => 'Users'], function (RouteBuilder $builder): void {
        $builder->connect('/reset', ['action' => 'resetPassword'], ['_name' => 'users:passwordReset']);
        $builder->connect('/new/{token}', ['action' => 'newPassword'], ['_name' => 'users:newPassword'])
            ->setPass(['token']);
    });
});

$routes->loadPlugin('Tasks');
$routes->loadPlugin('Calendar');
$routes->loadPlugin('Feeds');
