# Plan: CLI Video Info Tool

## Context

Add a `bin/console` CLI script to the SDK that fetches a video by ID and outputs its data as
JSON to STDOUT. Follows the Symfony Console application pattern:
тонкий `bin/console` entrypoint → `Application` собирает команды из DI-контейнера →
`ContainerFactory` регистрирует сервисы → `VideoInfoCommand` получает зависимости через constructor DI.

API key берётся из env-переменной `KINESCOPE_API_KEY` или из CLI-опции `--api-key` / `-k`.

**Ключевые решения:**
- CLI-код живёт в `src/Infrastructure/Console/` (namespace `Kinescope\Infrastructure\Console\`)
- Symfony DI `ContainerBuilder` в standalone-режиме (без full-stack framework)
- `ApiClientFactory` инжектируется в команду как pre-configured builder (с logger, http-клиентом);
  credentials разрешаются в `__invoke` из CLI-опции или env — это runtime-параметр
- `LoggerInterface` (PSR-3) инжектируется в команду и в `ApiClientFactory`
- Immutable-паттерн `ApiClientFactory` решается через статическую фабрику в `ContainerFactory`
- Symfony 8 command style: `__invoke()` + `#[Argument]` / `#[Option]`
- **НЕ используем** `setDefaultCommand('video:info', true)` — single-command режим отключает
  встроенные команды (`list`, `help`), что ломает `make console-list`

## Files to Create / Modify

| Path | Action |
|------|--------|
| `bin/console` | Create — shebang + autoload + `(new Application())->run()` |
| `src/Infrastructure/Console/Application.php` | Create — строит DI-контейнер, регистрирует команды |
| `src/Infrastructure/Console/ContainerFactory.php` | Create — собирает и компилирует `ContainerBuilder` |
| `src/Infrastructure/Console/Command/VideoInfoCommand.php` | Create — command с constructor DI |
| `tests/Unit/Infrastructure/Console/Command/VideoInfoCommandTest.php` | Create — unit-тест команды |
| `composer.json` | Modify — добавить `symfony/console` и `symfony/dependency-injection` `^8.0` + секцию `"bin"` |
| `Makefile` | Modify — добавить таргет `console-list` |

## Implementation

### `composer.json`

Добавить в `require`:
```json
"symfony/console": "^8.0",
"symfony/dependency-injection": "^8.0"
```

Добавить секцию `"bin"` (после `"config"`):
```json
"bin": ["bin/console"]
```

---

### `bin/console`

Тонкий entrypoint — только bootstrap, никакой логики:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Kinescope\Infrastructure\Console\Application;

exit((new Application())->run());
```

---

### `src/Infrastructure/Console/ContainerFactory.php`

Собирает и компилирует DI-контейнер. Поскольку `ApiClientFactory` использует immutable-паттерн
(каждый `with*()` возвращает clone), DI не может вызвать fluent-цепочку напрямую.
Решение: статический метод `buildApiClientFactory` — принимает `LoggerInterface`,
возвращает сконфигурированный `ApiClientFactory`. DI вызывает его как factory.

```php
<?php
declare(strict_types=1);

namespace Kinescope\Infrastructure\Console;

use Kinescope\Infrastructure\Console\Command\VideoInfoCommand;
use Kinescope\Core\ApiClientFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class ContainerFactory
{
    public static function build(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        // PSR-3 logger — NullLogger по умолчанию
        $container->register(LoggerInterface::class, NullLogger::class);

        // ApiClientFactory, pre-configured с logger.
        // Используем статическую фабрику, т.к. ApiClientFactory immutable (fluent clones).
        $container->register(ApiClientFactory::class)
            ->setFactory([self::class, 'buildApiClientFactory'])
            ->addArgument(new Reference(LoggerInterface::class));

        // Command как сервис — зависимости инжектируются через constructor
        $container->register(VideoInfoCommand::class)
            ->addArgument(new Reference(ApiClientFactory::class))
            ->addArgument(new Reference(LoggerInterface::class));

        $container->compile();

        return $container;
    }

    /**
     * Статическая фабрика для ApiClientFactory.
     * Нужна, т.к. DI-контейнер не понимает immutable fluent-builder напрямую.
     */
    public static function buildApiClientFactory(LoggerInterface $logger): ApiClientFactory
    {
        return ApiClientFactory::create()->withLogger($logger);
    }
}
```

---

### `src/Infrastructure/Console/Application.php`

Получает команду из DI-контейнера. `setDefaultCommand` используется БЕЗ второго аргумента `true`
(single-command режим не нужен — он отключает `list` и `help`):

```php
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

        $this->addCommand($container->get(VideoInfoCommand::class));
        $this->setDefaultCommand('video:info');
    }
}
```

---

### `src/Infrastructure/Console/Command/VideoInfoCommand.php`

Constructor DI: `ApiClientFactory` (pre-configured с logger, http-клиентом) + `LoggerInterface`.
В `__invoke` credentials разрешаются из `--api-key` или env — только в момент выполнения,
т.к. API key является runtime-параметром. `Videos` создаётся здесь же — зависит от runtime-клиента.

```php
<?php
declare(strict_types=1);

namespace Kinescope\Infrastructure\Console\Command;

use InvalidArgumentException;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Videos\Videos;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'video:info', description: 'Fetch video info by ID and output as JSON')]
final class VideoInfoCommand extends Command
{
    public function __construct(
        private readonly ApiClientFactory $apiClientFactory,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function __invoke(
        #[Argument('Video UUID')] string $videoId,
        #[Option('Kinescope API key (fallback: KINESCOPE_API_KEY env var)', 'k')] ?string $apiKey,
        OutputInterface $output,
    ): int {
        $this->logger->info('Fetching video info', ['videoId' => $videoId]);

        try {
            $apiClient = $apiKey !== null
                ? $this->apiClientFactory->withApiKey($apiKey)->build()
                : $this->apiClientFactory->buildFromEnvironment();
        } catch (InvalidArgumentException) {
            $output->getErrorOutput()->writeln(
                '<error>API key not provided. Use --api-key or set KINESCOPE_API_KEY.</error>'
            );
            return Command::FAILURE;
        }

        $videos = new Videos($apiClient);

        try {
            $video = $videos->get($videoId);
        } catch (NotFoundException) {
            $output->getErrorOutput()->writeln(
                sprintf('<error>Video "%s" not found.</error>', $videoId)
            );
            return Command::FAILURE;
        } catch (KinescopeException $e) {
            $output->getErrorOutput()->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln(
            json_encode(
                $video->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            )
        );

        return Command::SUCCESS;
    }
}
```

---

### `Makefile`

Добавить в секцию `# Utility commands` и в строку `.PHONY`:

```makefile
## Показать список CLI-команд SDK
console-list:
	docker compose exec php-cli php bin/console list
```

Добавить `console-list` в строку `.PHONY` и в секцию `Utilities:` в таргете `help`.

---

### Unit-тест (`tests/Unit/Infrastructure/Console/Command/VideoInfoCommandTest.php`)

Покрыть сценарии:
- успешный вывод JSON (мок `ApiClientFactory` + `Videos`)
- ошибка: API key не передан и env не установлен → `Command::FAILURE`
- ошибка: видео не найдено (`NotFoundException`) → `Command::FAILURE`
- ошибка: другая `KinescopeException` → `Command::FAILURE`

## Key Reused Existing Code

- `Kinescope\Core\ApiClientFactory::create()` — `src/Core/ApiClientFactory.php:63`
- `Kinescope\Core\ApiClientFactory::withLogger()` — `src/Core/ApiClientFactory.php:182`
- `Kinescope\Core\ApiClientFactory::withApiKey()` — `src/Core/ApiClientFactory.php:90`
- `Kinescope\Core\ApiClientFactory::buildFromEnvironment()` — `src/Core/ApiClientFactory.php:223`
- `Kinescope\Services\Videos\Videos` — `src/Services/Videos/Videos.php`
- `Kinescope\DTO\Video\VideoDTO::toArray()` — `src/DTO/Video/VideoDTO.php:225`
- `Kinescope\Exception\NotFoundException` — `src/Exception/NotFoundException.php`
- `Kinescope\Exception\KinescopeException` — `src/Exception/KinescopeException.php`

## Verification

```bash
# Установить зависимости
make composer args="require symfony/console:^8.0 symfony/dependency-injection:^8.0"

# Список всех команд SDK (проверяет что list работает корректно)
make console-list

# Запуск через env
export KINESCOPE_API_KEY=your-key
php bin/console video:info <videoId>

# Запуск через опцию
php bin/console video:info --api-key=your-key <videoId>
php bin/console video:info -k your-key <videoId>

# Проверка ошибок
php bin/console video:info                           # → missing argument, exit 1
php bin/console video:info nonexistent-id            # → not found в STDERR, exit 1
php bin/console video:info --api-key="" <videoId>    # → empty key error в STDERR, exit 1

# Качество
make lint-all
make test-unit
```
