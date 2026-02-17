# ADR: Система событий на Symfony Event Dispatcher

## Статус
Принято

## Дата
2026-02-17

## Контекст

В `kinescope-php-sdk` наблюдаемость сейчас реализована через PSR-3 логирование.  
`VideoDownloader` публикует прогресс только в `logger->debug(...)`, и потребителям приходится парсить `context` по строковым ключам (`percent`, `bytesWritten` и т.д.).

Это неудобно для runtime-реакций:
- Temporal heartbeat на порогах прогресса
- CLI ProgressBar
- webhook при завершении скачивания
- метрики (Prometheus, StatsD)

В предыдущем варианте рассматривался callable-based emitter через trait с `on()`/`emit()`.  
Для текущего кода это проблемно: `VideoDownloader` объявлен как `final readonly class`, а хранение/мутирование слушателей в trait (`$listeners[] = ...`) конфликтует с `readonly`-ограничениями.

Также требуется явный контракт размера файла до старта скачивания: размер должен быть известен заранее из `AssetDTO::fileSize`, а не вычисляться постфактум из потока.

## Решение

Использовать `symfony/event-dispatcher` как внутренний механизм событий SDK.

Основные принципы:
- События — отдельные typed-классы в `src/Event/...`
- Идентификатор события — FQCN класса события (без строковых `download.progress`)
- Диспетчер — `Symfony\Contracts\EventDispatcher\EventDispatcherInterface`
- В `VideoDownloader` оставить логирование и добавить dispatch событий в ключевых точках
- Для удобства добавить публичный метод `on(...)`, который проксирует `addListener(...)` на внутренний dispatcher

## Дизайн

### Event-классы (этап 1)

```
src/Event/
└── Download/
    ├── DownloadStartedEvent.php
    ├── DownloadProgressEvent.php
    ├── DownloadCompletedEvent.php
    └── DownloadFailedEvent.php
```

Поля событий должны быть строго типизированы и отражать доменные данные (videoId, bytesWritten, totalBytes, percent, filePath, duration, error).

### Контракт данных событий

Ниже зафиксирован минимальный payload для событий скачивания.  
Все события должны быть `final readonly class`.

#### Базовые правила

- `videoId` всегда UUID видео в строковом формате
- `sizeBytes`/`totalBytes` всегда `> 0` и известен до начала скачивания
- источник размера: `AssetDTO::fileSize` выбранного ассета
- если размер отсутствует (`null`) или некорректен (`<= 0`), скачивание не начинается
- `qualityPreference` всегда задаётся enum `Kinescope\Enum\QualityPreference`
- `occurredAt` хранится как `\Carbon\CarbonImmutable` (UTC)
- `durationMs` хранится в миллисекундах

#### `DownloadStartedEvent`

```php
final readonly class DownloadStartedEvent
{
    public function __construct(
        public string $videoId,
        public string $downloadUrl,
        public int $sizeBytes,
        public \Kinescope\Enum\QualityPreference $qualityPreference,
        public int $selectedHeight,
        public \Carbon\CarbonImmutable $occurredAt,
    ) {}
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `videoId` | `string` | Идентификатор видео |
| `downloadUrl` | `string` | URL выбранного ассета для скачивания |
| `sizeBytes` | `int` | Обязательный размер файла в байтах |
| `qualityPreference` | `QualityPreference` | Стратегия выбора качества (`BEST`/`WORST`) |
| `selectedHeight` | `int` | Фактически выбранная высота ассета (например `1080`) |
| `occurredAt` | `\Carbon\CarbonImmutable` | Момент старта скачивания |

#### `DownloadProgressEvent`

```php
final readonly class DownloadProgressEvent
{
    public function __construct(
        public string $videoId,
        public string $filePath,
        public int $bytesWritten,
        public int $sizeBytes,
        public float $percent,
        public \Carbon\CarbonImmutable $occurredAt,
    ) {}
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `videoId` | `string` | Идентификатор видео |
| `filePath` | `string` | Локальный путь целевого файла |
| `bytesWritten` | `int` | Количество уже записанных байт |
| `sizeBytes` | `int` | Полный размер файла в байтах |
| `percent` | `float` | Прогресс в диапазоне `0..100` |
| `occurredAt` | `\Carbon\CarbonImmutable` | Момент эмита прогресса |

Инварианты:
- `bytesWritten >= 0`
- `bytesWritten <= sizeBytes`
- `0.0 <= percent <= 100.0`

#### `DownloadCompletedEvent`

```php
final readonly class DownloadCompletedEvent
{
    public function __construct(
        public string $videoId,
        public string $filePath,
        public int $fileSize,
        public int $durationMs,
        public \Carbon\CarbonImmutable $occurredAt,
    ) {}
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `videoId` | `string` | Идентификатор видео |
| `filePath` | `string` | Путь до итогового файла |
| `fileSize` | `int` | Финальный размер файла в байтах |
| `durationMs` | `int` | Длительность скачивания от старта до завершения |
| `occurredAt` | `\Carbon\CarbonImmutable` | Момент успешного завершения |

#### `DownloadFailedEvent`

```php
final readonly class DownloadFailedEvent
{
    public function __construct(
        public string $videoId,
        public ?string $filePath,
        public int $totalBytes,
        public int $bytesWritten,
        public \Throwable $exception,
        public \Carbon\CarbonImmutable $occurredAt,
    ) {}
}
```

| Поле | Тип | Описание |
|------|-----|----------|
| `videoId` | `string` | Идентификатор видео |
| `filePath` | `?string` | Путь, если файл уже был создан |
| `totalBytes` | `int` | Ожидаемый полный размер файла |
| `bytesWritten` | `int` | Сколько байт успели записать до ошибки |
| `exception` | `\Throwable` | Исходное исключение операции скачивания |
| `occurredAt` | `\Carbon\CarbonImmutable` | Момент падения операции |

Правило эмита:
- событие отправляется перед повторным пробросом исключения наверх

### Интеграция в VideoDownloader

- Конструктор получает опциональный dispatcher последним аргументом:
  `?EventDispatcherInterface $eventDispatcher = null`
- Если dispatcher не передан: использовать `new EventDispatcher()`
- Перед `sendRequest()` валидировать, что у выбранного ассета есть `fileSize > 0`; иначе бросать `KinescopeException` с явным текстом ошибки
- Точки dispatch:
  - перед началом скачивания (`DownloadStartedEvent`)
  - периодически в `writeStreamToFile()` (`DownloadProgressEvent`)
  - после успешного завершения (`DownloadCompletedEvent`)
  - при ошибке (`DownloadFailedEvent`)

### Пример использования

```php
$downloader->on(DownloadProgressEvent::class, function (DownloadProgressEvent $event): void {
    if ($event->percent >= 50.0) {
        Activity::heartbeat('Download 50%');
    }
});
```

## Изменения в SDK

| Файл | Действие |
|------|----------|
| `composer.json` | Добавить `symfony/event-dispatcher` в `require` |
| `src/Event/Download/*` | Создать event-классы |
| `src/Services/Videos/VideoDownloader.php` | Инжект dispatcher, dispatch событий, метод `on()` |
| `tests/Unit/...` | Тесты dispatch последовательности и payload |
| `tests/Integration/...` | Проверка событий в реальном download-сценарии |

## Обратная совместимость

- Логирование сохраняется без изменений
- Существующий код, который не использует события, продолжит работать
- Добавление опционального аргумента в конец конструктора `VideoDownloader` — BC-safe
- Поведение становится строже: при отсутствии `file_size` у выбранного ассета скачивание завершится ошибкой валидации
- Новая функциональность будет выпущена как минорная версия

## Альтернативы

1. **Callable trait emitter**
- Плюс: нулевые зависимости
- Минус: конфликт с `readonly` и более слабая экосистема

2. **PSR-14**
- Плюс: стандарт
- Минус: дополнительная сложность (dispatcher + provider) для небольшого числа событий

## План внедрения

1. Добавить зависимости и event-классы `Download*`
2. Интегрировать dispatch в `VideoDownloader`
3. Добавить unit/integration тесты
4. Обновить `README.md` с примерами подписок
