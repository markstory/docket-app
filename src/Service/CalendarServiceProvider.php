<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\CalendarService;
use Cake\Core\ContainerInterface;
use Cake\Core\ServiceProvider;
use Cake\Routing\Router;
use Google\Client as GoogleClient;
use Google\Service\Calendar;

class CalendarServiceProvider extends ServiceProvider
{
    protected $provides = [
        CalendarService::class,
        GoogleClient::class,
    ];

    public function services(ContainerInterface $container): void
    {
        $container->add(GoogleClient::class, function () {
            $file = file_get_contents(ROOT . '/config/google-auth.json');
            $config = json_decode($file, true);

            $client = new GoogleClient();
            $client->setAuthConfig($config);
            $client->setApplicationName('Docket Calendar Sync');
            $client->addScope(Calendar::CALENDAR_EVENTS_READONLY);
            $client->addScope(Calendar::CALENDAR_READONLY);
            $client->setRedirectUri(Router::url(
                ['_name' => 'googleauth:callback', '_full' => true]
            ));
            $client->setAccessType('offline');
            $client->setIncludeGrantedScopes(true);

            return $client;
        });
        $container->add(CalendarService::class)
            ->addArgument(GoogleClient::class);
    }
}