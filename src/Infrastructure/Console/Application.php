<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console;

use Kinescope\Infrastructure\Console\Command\BrowseCommand;
use Kinescope\Infrastructure\Console\Command\VideoInfoCommand;
use Symfony\Component\Console\Application as BaseApplication;

final class Application extends BaseApplication
{
    public const string NAME = 'Kinescope CLI';
    public const string VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $container = ContainerFactory::build();

        $videoInfoCommand = $container->get(VideoInfoCommand::class);
        assert($videoInfoCommand instanceof VideoInfoCommand);
        $this->addCommand($videoInfoCommand);

        $browseCommand = $container->get(BrowseCommand::class);
        assert($browseCommand instanceof BrowseCommand);
        $this->addCommand($browseCommand);

        $this->setDefaultCommand('video:info');
    }
}
