<?php
declare(strict_types=1);

namespace Calendar\Service;

use Cake\Core\ContainerInterface;
use Cake\Core\ServiceProvider;
use Cake\Routing\Router;
use Calendar\Command\CalendarSourceSyncCommand;
use Calendar\Command\CalendarSubscriptionRenewCommand;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Oauth2;

class CalendarServiceProvider extends ServiceProvider
{
    /**
     * @var list<string> $provides
     */
    protected array $provides = [
        CalendarSourceSyncCommand::class,
        CalendarSubscriptionRenewCommand::class,
        CalendarService::class,
        GoogleClient::class,
    ];

    public function services(ContainerInterface $container): void
    {
        $container->add(GoogleClient::class, function () {
            $file = file_get_contents(ROOT . '/config/google-auth.json');
            assert($file !== false, 'Could not read config/google-auth.json');
            $config = json_decode($file, true);

            $client = new GoogleClient();
            $client->setAuthConfig($config);
            $client->setApplicationName('Docket Calendar Sync');
            $client->addScope(Calendar::CALENDAR_EVENTS_READONLY);
            $client->addScope(Calendar::CALENDAR_READONLY);
            $client->addScope(Oauth2::USERINFO_EMAIL);
            $client->setRedirectUri(Router::url(
                ['_name' => 'googleauth:callback', '_full' => true]
            ));
            $client->setAccessType('offline');
            $client->setIncludeGrantedScopes(true);

            return $client;
        });
        $container->add(CalendarService::class)
            ->addArgument(GoogleClient::class);

        $container->add(CalendarSourceSyncCommand::class)
            ->addArgument(CalendarService::class);

        $container->add(CalendarSubscriptionRenewCommand::class)
            ->addArgument(CalendarService::class);
    }
}
