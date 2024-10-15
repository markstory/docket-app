<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\ContainerInterface;
use Cake\Core\ServiceProvider;
use Cake\Http\Client;

class FeedServiceProvider extends ServiceProvider
{
    protected array $provides = [
        FeedService::class,
    ];

    public function services(ContainerInterface $container): void
    {
        $container->add(Client::class);

        $container->add(FeedService::class)
            ->addArgument(Client::class);
    }
}
