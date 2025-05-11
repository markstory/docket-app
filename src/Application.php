<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.3.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App;

use App\Service\CalendarServiceProvider;
use App\Service\FeedServiceProvider;
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Middleware\AuthenticationMiddleware;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\OrmResolver;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CspMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Http\ServerRequest;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use FeatureFlags\FeatureManagerInterface;
use FeatureFlags\Simple\FeatureManager;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication implements
    AuthenticationServiceProviderInterface,
    AuthorizationServiceProviderInterface
{
    /**
     * Load all the application configuration and bootstrap logic.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Call parent to load bootstrap from files.
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        }

        /*
         * Only try to load DebugKit in development mode
         * Debug Kit should not be installed on a production system
         */
        if (Configure::read('debug') && !Configure::read('App.inTest')) {
            $this->addPlugin('DebugKit');
        }
        $this->addPlugin('Authentication');
        $this->addPlugin('Authorization');
    }

    /**
     * Setup the middleware queue your application will use.
     *
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to setup.
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            // Catch any exceptions in the lower layers,
            // and make an error page/response
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))

            // Handle plugin/theme assets like CakePHP normally does.
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))

            // Add routing middleware.
            // If you have a large number of routes connected, turning on routes
            // caching in production could improve performance. For that when
            // creating the middleware instance specify the cache config name by
            // using it's second constructor argument:
            // `new RoutingMiddleware($this, '_cake_routes_')`
            ->add(new RoutingMiddleware($this))

            // Parse various types of encoded request bodies so that they are
            // available as array through $request->getData()
            // https://book.cakephp.org/4/en/controllers/middleware.html#body-parser-middleware
            ->add(new BodyParserMiddleware())

            ->add(new CspMiddleware($this->getContainer()->get(CSPBuilder::class)))
            ->add(new AuthenticationMiddleware($this))
            ->add(new AuthorizationMiddleware($this, [
                'identityDecorator' => function ($auth, $user) {
                    return $user->setAuthorization($auth);
                },
            ]));

        return $middlewareQueue;
    }

    /**
     * Bootstrapping for CLI application.
     *
     * That is when running commands.
     *
     * @return void
     */
    protected function bootstrapCli(): void
    {
        $this->addPlugin('Migrations');
        $this->addOptionalPlugin('Cake/Repl');
        $this->addOptionalPlugin('Bake');
    }

    public function services(ContainerInterface $container): void
    {
        $container->add(CSPBuilder::class, function () {
            $allow = [];
            if (Configure::read('debug')) {
                $allow = ['localhost:3000'];
            }
            $csp = new CSPBuilder([
                'font-src' => ['self' => true],
                'form-action' => ['self' => true],
                'img-src' => ['self' => true, 'data' => true, 'allow' => ['*']],
                'script-src' => ['self' => true, 'unsafe-inline' => true, 'allow' => $allow],
                'style-src' => ['self' => true, 'unsafe-inline' => true, 'allow' => $allow],
                'object-src' => [],
                'plugin-types' => [],
            ]);

            return $csp;
        });

        $container->addServiceProvider(new FeedServiceProvider());
        $container->addShared(FeatureManagerInterface::class, function () {
            return new FeatureManager(Configure::read('Features'));
        });
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $config = [
            'unauthenticatedRedirect' => '/login',
            'queryParam' => 'redirect',
        ];
        // API token request. We don't want redirects.
        if ($request->hasHeader('Authorization')) {
            $config = [];
        }
        $service = new AuthenticationService($config);

        $fields = [
            AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
            AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
        ];
        // Load identifiers
        $service->loadIdentifier('Authentication.Password', [
            'resolver' => [
                'className' => 'Authentication.Orm',
                'userModel' => 'Users',
            ],
            'fields' => $fields,
        ]);
        $service->loadIdentifier('ApiToken');

        // Load the authenticators, you want session first
        $service->loadAuthenticator('Authentication.Session', [
            'identify' => true,
            'fields' => [
                AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
            ],
        ]);
        $service->loadAuthenticator('Authentication.Token', [
            'queryParam' => 'token',
            'header' => 'Authorization',
            'tokenPrefix' => 'Bearer',
        ]);
        // There are two possible login URLs. The default one is for HTML views.
        // And the other is for the in-progress mobile app.
        $loginUrl = '/login';
        if (($request instanceof ServerRequest) && $request->getParam('prefix') == 'Api') {
            $loginUrl = '/api/tokens/add';
        }
        $service->loadAuthenticator('Authentication.Form', [
            'loginUrl' => $loginUrl,
            'fields' => [
                AbstractIdentifier::CREDENTIAL_USERNAME => 'email',
                AbstractIdentifier::CREDENTIAL_PASSWORD => 'password',
            ],
        ]);
        $service->loadAuthenticator('Authentication.Cookie', [
            'fields' => $fields,
            'loginUrl' => '/login',
        ]);

        return $service;
    }

    public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
    {
        $resolver = new OrmResolver();

        return new AuthorizationService($resolver);
    }
}
