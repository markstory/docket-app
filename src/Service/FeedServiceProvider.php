<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\ContainerInterface;
use Cake\Core\ServiceProvider;
use Cake\Http\Client;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

class FeedServiceProvider extends ServiceProvider
{
    protected array $provides = [
        FeedService::class,
        HtmlSanitizerConfig::class,
        HtmlSanitizerInterface::class,
    ];

    public function services(ContainerInterface $container): void
    {
        $container->add(Client::class);

        $container->add(HtmlSanitizerConfig::class, function () {
            $config = new HtmlSanitizerConfig();

            return $config->allowSafeElements();
        });

        $container->add(HtmlSanitizerInterface::class, HtmlSanitizer::class)
            ->addArgument(HtmlSanitizerConfig::class);

        $container->add(FeedService::class)
            ->addArgument(Client::class)
            ->addArgument(HtmlSanitizerInterface::class);
    }
}
