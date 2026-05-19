<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console;

use Kinescope\Infrastructure\Console\Command\FolderListCommand;
use Kinescope\Infrastructure\Console\Command\FolderShowCommand;
use Kinescope\Infrastructure\Console\Command\ProjectListCommand;
use Kinescope\Infrastructure\Console\Command\ProjectShowCommand;
use Kinescope\Infrastructure\Console\Command\StatisticsShowCommand;
use Kinescope\Infrastructure\Console\Command\VideoAssetListCommand;
use Kinescope\Infrastructure\Console\Command\VideoListCommand;
use Kinescope\Infrastructure\Console\Command\VideoShowCommand;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;

final class Application extends BaseApplication
{
    public const string NAME = 'Kinescope CLI';
    public const string VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $container = ContainerFactory::build();

        foreach ([
            ProjectListCommand::class,
            ProjectShowCommand::class,
            FolderListCommand::class,
            FolderShowCommand::class,
            VideoListCommand::class,
            VideoShowCommand::class,
            VideoAssetListCommand::class,
            StatisticsShowCommand::class,
        ] as $commandClass) {
            $command = $container->get($commandClass);
            assert($command instanceof Command);
            $this->addCommand($command);
        }
    }
}
