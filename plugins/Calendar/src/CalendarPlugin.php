<?php
declare(strict_types=1);

namespace Calendar;

use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;
use Calendar\Service\CalendarServiceProvider;

/**
 * Plugin for Calendar
 */
class CalendarPlugin extends BasePlugin
{
    /**
     * Register application container services.
     *
     * @param \Cake\Core\ContainerInterface $container The Container to update.
     * @return void
     * @link https://book.cakephp.org/4/en/development/dependency-injection.html#dependency-injection
     */
    public function services(ContainerInterface $container): void
    {
        $container->addServiceProvider(new CalendarServiceProvider());
    }
}
