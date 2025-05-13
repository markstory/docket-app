<?php
declare(strict_types=1);

namespace Feeds;

use App\Service\FeedServiceProvider;
use Cake\Core\BasePlugin;
use Cake\Core\ContainerInterface;

/**
 * Plugin for Feeds
 */
class FeedsPlugin extends BasePlugin
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
        $container->addServiceProvider(new FeedServiceProvider());
    }
}
