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
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Migrations\TestSuite\Migrator;
use VCR\VCR;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';

Configure::write('App.fullBaseUrl', 'http://localhost');
Configure::write('App.inTest', true);

// Set security salt
Configure::write('Security.salt', 'a-random-value-that-you-cannot-guess');
Configure::write('Security.emailSalt', 'a-random-value-used-for-emails');

// Set test database connection
ConnectionManager::setConfig('test', [
    'url' => env('DATABASE_TEST_URL', 'sqlite://./tmp/tests.sqlite'),
]);

// DebugKit skips settings these connection config if PHP SAPI is CLI / PHPDBG.
// But since PagesControllerTest is run with debug enabled and DebugKit is loaded
// in application, without setting up these config DebugKit errors out.
ConnectionManager::setConfig('test_debug_kit', [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\Sqlite',
    'database' => TMP . 'debug_kit.sqlite',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
]);

ConnectionManager::alias('test_debug_kit', 'debug_kit');

// Simple setup for with no plugins
(new Migrator())->run();

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Configure PHP-VCR
VCR::configure()
    ->setCassettePath(__DIR__ . '/Fixture/vcr')
    ->setStorage('yaml')
    ->setWhitelist(['vendor/guzzlehttp', 'vendor/google', 'vendor/cakephp/cakephp/src/Http/Client'])
    ->addRequestMatcher('sloppy_body', function (\VCR\Request $first, \VCR\Request $second) {
        $bodies = [$first->getBody(), $second->getBody()];
        foreach ($bodies as $i => $body) {
            if ($body === null) {
                unset($bodies[$i]);
                continue;
            }
            $bodies[$i] = json_decode($body, true);
        }
        // Only be sloppy with json requests.
        if (count($bodies) !== 2) {
            return $first->getBody() == $second->getBody();
        }

        $special = [
            '/calendar/v3/calendars/calendar-1/events/watch' => ['id', 'token'],
        ];
        foreach ($special as $url => $fields) {
            if (strpos($first->getUrl(), $url) !== false) {
                foreach ($fields as $field) {
                    unset($bodies[0][$field]);
                    unset($bodies[1][$field]);
                }
            }
        }

        return json_encode($bodies[0]) == json_encode($bodies[1]);
    })
    ->enableRequestMatchers([
        'post_fields',
        'method',
        'url',
        'host',
        'query_string',
        'sloppy_body',
    ])
    ->enableLibraryHooks(['curl'])
    ->setMode('once');
VCR::turnOn();

// Setup server for panther tests.
// Panther doesn't make the router script absolute.
$_SERVER['PANTHER_WEB_SERVER_ROUTER'] = realpath('./webroot/index.php');
$_SERVER['PANTHER_WEB_SERVER_DIR'] = './webroot';
$_SERVER['PANTHER_APP_ENV'] = 'acceptance';

// Uncomment to have a browser attached for panther tests.
// $_SERVER['PANTHER_NO_HEADLESS'] = true;
