<?php

declare(strict_types=1);

namespace Kinescope\Infrastructure\Console;

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

        $command = $container->get(VideoInfoCommand::class);
        assert($command instanceof VideoInfoCommand);
        $this->addCommand($command);
        $this->setDefaultCommand('video:info');
    }
}
