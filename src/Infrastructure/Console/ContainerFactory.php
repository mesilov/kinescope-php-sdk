<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console;

use Kinescope\Core\ApiClientFactory;
use Kinescope\Infrastructure\Console\Command\BrowseCommand;
use Kinescope\Infrastructure\Console\Command\VideoInfoCommand;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ContainerFactory
{
    public static function build(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->register(LoggerInterface::class, NullLogger::class);

        // ApiClientFactory is immutable (each with*() returns a clone),
        // so DI cannot chain fluent calls directly — use a static factory method.
        $container->register(ApiClientFactory::class)
            ->setFactory([self::class, 'buildApiClientFactory'])
            ->addArgument(new Reference(LoggerInterface::class));

        $container->register(VideoInfoCommand::class)
            ->setPublic(true)
            ->addArgument(new Reference(ApiClientFactory::class))
            ->addArgument(new Reference(LoggerInterface::class));

        $container->register(BrowseCommand::class)
            ->setPublic(true)
            ->addArgument(new Reference(ApiClientFactory::class))
            ->addArgument(new Reference(LoggerInterface::class));

        $container->compile();

        return $container;
    }

    public static function buildApiClientFactory(LoggerInterface $logger): ApiClientFactory
    {
        return ApiClientFactory::create()->withLogger($logger);
    }
}
