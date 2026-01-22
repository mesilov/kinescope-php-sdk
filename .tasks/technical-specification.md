# Техническое задание: PHP SDK для Kinescope API

## 1. Общее описание проекта

### 1.1 Назначение
Разработка PHP SDK для интеграции с Kinescope API - платформой для управления видеоконтентом, которая предоставляет возможности загрузки, транскодирования (до 4K), защиты и ускорения доставки видео.

### 1.2 Целевая аудитория
- PHP разработчики, интегрирующие видео-функциональность в веб-приложения
- Компании, использующие Kinescope для управления видеоконтентом
- Разработчики CMS и корпоративных систем

### 1.3 Цели проекта
- Предоставить удобный объектно-ориентированный интерфейс для работы с Kinescope API
- Обеспечить типобезопасность и автодополнение в IDE
- Упростить процесс аутентификации и обработки ошибок
- **Версия 1.0**: Поддержка read-only операций (GET методов) для базовых сервисов
- Использовать современные PHP 8.4+ возможности (readonly, enums, named arguments, property hooks)

### 1.4 Область действия версии 1.0
Первая версия SDK реализует **только операции чтения** (GET методы) для следующих сервисов:
- Videos - получение списка и информации о видео
- Subtitles - получение субтитров видео
- Annotations - получение аннотаций видео
- Projects - получение списка и информации о проектах
- Folders - получение списка и информации о папках
- Playlists - получение плейлистов и их содержимого

Операции записи (POST, PUT, PATCH, DELETE) и дополнительные сервисы будут добавлены в следующих версиях.

## 2. Техническая информация об API

### 2.1 Базовые параметры
- **Base URL**: `https://api.kinescope.io`
- **Upload URL**: `https://uploader.kinescope.io`
- **Аутентификация**: Bearer Token (Access Key)
- **Формат данных**: JSON
- **Протокол**: HTTPS

### 2.2 HTTP методы
- `GET` - получение данных
- `POST` - создание ресурсов
- `PUT/PATCH` - обновление ресурсов
- `DELETE` - удаление ресурсов

### 2.3 HTTP коды ответов
- `200` - Success
- `400` - Bad request parameters
- `401` - Unauthorized
- `402` - Payment required
- `403` - Access denied
- `404` - Object not found
- `422` - Validation error
- `429` - Rate limit exceeded

### 2.4 Формат аутентификации
```
Authorization: Bearer {api_key}
```

## 3. Требования к функциональности

### 3.1 API модули версии 1.0

> **Примечание**: Версия 1.0 SDK поддерживает только операции чтения (GET методы).
> Дополнительные сервисы и методы записи будут добавлены в следующих версиях.

#### 3.1.1 Videos (v1) - Получение видео
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos` | Список видео с пагинацией и фильтрацией |
| GET | `/v1/videos/{video_id}` | Получение информации о видео |

#### 3.1.2 Subtitles - Получение субтитров
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos/{video_id}/subtitles` | Список субтитров видео |
| GET | `/v1/videos/{video_id}/subtitles/{subtitle_id}` | Получение конкретных субтитров |

#### 3.1.3 Annotations - Получение аннотаций
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos/{video_id}/annotations` | Список аннотаций видео |
| GET | `/v1/videos/{video_id}/annotations/{annotation_id}` | Получение конкретной аннотации |

#### 3.1.4 Projects - Получение проектов
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/projects` | Список проектов |
| GET | `/v1/projects/{project_id}` | Получение информации о проекте |

#### 3.1.5 Folders - Получение папок
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/projects/{project_id}/folders` | Список папок в проекте |
| GET | `/v1/projects/{project_id}/folders/{folder_id}` | Получение информации о папке |

#### 3.1.6 Playlists - Получение плейлистов
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/playlists` | Список плейлистов |
| GET | `/v1/playlists/{playlist_id}` | Получение информации о плейлисте |
| GET | `/v1/playlists/{playlist_id}/entities` | Список медиа в плейлисте |

### 3.2 Сервисы, запланированные для будущих версий

Следующие сервисы **не включены** в версию 1.0 и будут добавлены в последующих релизах:

- **Posters** - управление постерами видео
- **Analytics** - аналитика просмотров
- **Billing** - информация о биллинге
- **Additional Materials** - дополнительные материалы
- **Access Tokens** - токены доступа
- **Players** - управление плеерами
- **File Requests** - запросы на файлы
- **DRM Auth** - DRM авторизация
- **Privacy Domains** - домены приватности
- **Tags** - управление тегами
- **Moderators** - управление модераторами
- **Webhooks** - вебхуки
- **CDN** - управление CDN зонами
- **Upload** - загрузка видео
- **Live Events** - прямые эфиры
- **Restreams** - ретрансляции
- **Dictionaries** - справочники
- **Avatars** - аватары

### 3.3 Обработка исключений

Иерархия исключений с маппингом HTTP кодов:
```
KinescopeException (базовое исключение)
├── AuthenticationException (401)
├── PaymentRequiredException (402)
├── ForbiddenException (403)
├── NotFoundException (404)
├── BadRequestException (400)
├── ValidationException (422)
├── RateLimitException (429)
└── NetworkException (сетевые ошибки)
```

## 4. Архитектура SDK

### 4.1 Структура проекта (версия 1.0)

```
kinescope-php-sdk/
├── docker/
│   └── php-cli/
│       ├── Dockerfile
│       └── conf.d/
│           └── php.ini
├── src/
│   ├── Contracts/                          # Интерфейсы
│   │   ├── ApiClientInterface.php          # Интерфейс HTTP клиента
│   │   └── ServiceInterface.php            # Базовый интерфейс сервиса
│   ├── Core/                               # Ядро SDK
│   │   ├── ApiClient.php                   # HTTP клиент с retry логикой
│   │   ├── ApiClientFactory.php            # Фабрика с fluent builder
│   │   ├── Credentials.php                 # Value object для API ключа
│   │   ├── JsonDecoder.php                 # Декодер JSON ответов
│   │   ├── Pagination.php                  # Value object пагинации
│   │   └── ResponseHandler.php             # Обработчик ответов и маппинг ошибок
│   ├── Enum/                               # PHP 8.4 Enums
│   │   ├── VideoStatus.php                 # pending, uploading, processing, done, error
│   │   ├── PrivacyType.php                 # anywhere, custom, nowhere
│   │   ├── SubtitleLanguage.php            # Языки субтитров
│   │   └── HttpMethod.php                  # HTTP методы
│   ├── Exception/                          # Иерархия исключений
│   │   ├── KinescopeException.php          # Базовое исключение
│   │   ├── AuthenticationException.php     # 401
│   │   ├── PaymentRequiredException.php    # 402
│   │   ├── ForbiddenException.php          # 403
│   │   ├── NotFoundException.php           # 404
│   │   ├── BadRequestException.php         # 400
│   │   ├── ValidationException.php         # 422
│   │   ├── RateLimitException.php          # 429
│   │   └── NetworkException.php            # Сетевые ошибки
│   ├── Services/                           # Сервисный слой
│   │   ├── ServiceFactory.php              # Основная фабрика сервисов
│   │   ├── AbstractService.php             # Базовый класс сервиса
│   │   ├── Videos/
│   │   │   ├── VideosService.php           # Получение видео (list, get)
│   │   │   ├── SubtitlesService.php        # Получение субтитров (list, get)
│   │   │   └── AnnotationsService.php      # Получение аннотаций (list, get)
│   │   ├── Projects/
│   │   │   └── ProjectsService.php         # Получение проектов (list, get)
│   │   ├── Folders/
│   │   │   └── FoldersService.php          # Получение папок (list, get)
│   │   └── Playlists/
│   │       └── PlaylistsService.php        # Получение плейлистов (list, get, entities)
│   └── DTO/                                # Data Transfer Objects (readonly)
│       ├── Video/
│       │   ├── VideoDTO.php                # Основная модель видео
│       │   ├── VideoListResult.php         # Результат списка видео
│       │   ├── SubtitleDTO.php             # Субтитры
│       │   ├── SubtitleListResult.php      # Результат списка субтитров
│       │   ├── AnnotationDTO.php           # Аннотация
│       │   ├── AnnotationListResult.php    # Результат списка аннотаций
│       │   └── AssetDTO.php                # Ассет видео (качество)
│       ├── Project/
│       │   ├── ProjectDTO.php              # Проект
│       │   └── ProjectListResult.php       # Результат списка проектов
│       ├── Folder/
│       │   ├── FolderDTO.php               # Папка
│       │   └── FolderListResult.php        # Результат списка папок
│       ├── Playlist/
│       │   ├── PlaylistDTO.php             # Плейлист
│       │   ├── PlaylistListResult.php      # Список плейлистов
│       │   └── PlaylistEntityDTO.php       # Элемент плейлиста
│       └── Common/
│           ├── PaginatedResponse.php       # Базовый пагинированный ответ
│           └── MetaDTO.php                 # Метаданные ответа
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Fixtures/
│   └── TestCase.php
├── .env
├── .env.local                              # (gitignored)
├── .gitignore
├── .php-cs-fixer.dist.php
├── composer.json
├── docker-compose.yaml
├── Makefile
├── phpstan.neon
├── phpunit.xml
├── rector.php
└── README.md
```

### 4.2 Пример использования SDK (версия 1.0)

```php
<?php

use Kinescope\Services\ServiceFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Enum\VideoStatus;
use Kinescope\Exception\KinescopeException;
use Kinescope\Exception\NotFoundException;

// === Способ 1: Инициализация через Credentials ===
$credentials = Credentials::fromString('your-api-key');
$factory = new ServiceFactory($credentials);

// === Способ 2: Из переменных окружения (KINESCOPE_API_KEY) ===
$factory = ServiceFactory::fromEnvironment();

// === Способ 3: С кастомным HTTP клиентом через ApiClientFactory ===
$apiClient = ApiClientFactory::create()
    ->withCredentials(Credentials::fromString('your-api-key'))
    ->withBaseUrl('https://api.kinescope.io')
    ->withTimeout(30)
    ->withRetryAttempts(3)
    ->withLogger($psrLogger)  // PSR-3 Logger (опционально)
    ->build();

$factory = new ServiceFactory(apiClient: $apiClient);

try {
    // === Получение списка видео ===

    $videosList = $factory->videos()->list(
        page: 1,
        perPage: 20,
        projectId: 'project-uuid',
        folderId: 'folder-uuid',  // опционально
        order: 'created_at',
        direction: 'desc'
    );

    echo "Всего видео: " . $videosList->getMeta()->total . PHP_EOL;

    foreach ($videosList->getData() as $video) {
        if ($video->status === VideoStatus::DONE) {
            echo sprintf(
                "ID: %s, Название: %s, Длительность: %d сек\n",
                $video->id,
                $video->title,
                $video->duration
            );
        }
    }

    // === Получение конкретного видео ===

    $video = $factory->videos()->get('video-uuid');
    echo "Embed код: " . $video->embedCode . PHP_EOL;
    echo "HLS ссылка: " . $video->hlsLink . PHP_EOL;

    // === Получение субтитров видео ===

    $subtitlesList = $factory->subtitles()->list('video-uuid');

    foreach ($subtitlesList->getData() as $subtitle) {
        echo sprintf(
            "Субтитры: %s (%s)\n",
            $subtitle->title,
            $subtitle->language
        );
    }

    // Получение конкретных субтитров
    $subtitle = $factory->subtitles()->get('video-uuid', 'subtitle-uuid');
    echo "URL субтитров: " . $subtitle->url . PHP_EOL;

    // === Получение аннотаций видео ===

    $annotationsList = $factory->annotations()->list('video-uuid');

    foreach ($annotationsList->getData() as $annotation) {
        echo sprintf(
            "Аннотация: %s (время: %d сек)\n",
            $annotation->title,
            $annotation->time
        );
    }

    // === Получение списка проектов ===

    $projectsList = $factory->projects()->list(page: 1, perPage: 10);

    foreach ($projectsList->getData() as $project) {
        echo sprintf(
            "Проект: %s (ID: %s)\n",
            $project->name,
            $project->id
        );
    }

    // Получение конкретного проекта
    $project = $factory->projects()->get('project-uuid');
    echo "Проект: " . $project->name . PHP_EOL;

    // === Получение папок проекта ===

    $foldersList = $factory->folders()->list('project-uuid');

    foreach ($foldersList->getData() as $folder) {
        echo sprintf(
            "Папка: %s (ID: %s)\n",
            $folder->name,
            $folder->id
        );
    }

    // Получение конкретной папки
    $folder = $factory->folders()->get('project-uuid', 'folder-uuid');
    echo "Папка: " . $folder->name . PHP_EOL;

    // === Получение плейлистов ===

    $playlistsList = $factory->playlists()->list(page: 1, perPage: 10);

    foreach ($playlistsList->getData() as $playlist) {
        echo sprintf(
            "Плейлист: %s (ID: %s)\n",
            $playlist->title,
            $playlist->id
        );
    }

    // Получение конкретного плейлиста
    $playlist = $factory->playlists()->get('playlist-uuid');
    echo "Плейлист: " . $playlist->title . PHP_EOL;

    // Получение медиа в плейлисте
    $entities = $factory->playlists()->entities('playlist-uuid');

    foreach ($entities->getData() as $entity) {
        echo sprintf(
            "Медиа в плейлисте: %s (позиция: %d)\n",
            $entity->title,
            $entity->position
        );
    }

} catch (NotFoundException $e) {
    echo "Ресурс не найден: " . $e->getMessage() . PHP_EOL;
} catch (KinescopeException $e) {
    echo "Ошибка API: " . $e->getMessage() . PHP_EOL;
    echo "HTTP код: " . $e->getCode() . PHP_EOL;

    if ($e->hasResponse()) {
        echo "Ответ сервера: " . $e->getResponseBody() . PHP_EOL;
    }
}
```

### 4.3 Ключевые паттерны архитектуры

#### 4.3.1 ServiceFactory - Главная точка входа
```php
final class ServiceFactory
{
    private ?VideosService $videos = null;
    private ?SubtitlesService $subtitles = null;
    private ?AnnotationsService $annotations = null;
    private ?ProjectsService $projects = null;
    private ?FoldersService $folders = null;
    private ?PlaylistsService $playlists = null;

    public function __construct(
        private readonly Credentials|ApiClientInterface $credentialsOrClient
    ) {}

    public static function fromEnvironment(): self
    {
        $apiKey = getenv('KINESCOPE_API_KEY');
        if ($apiKey === false) {
            throw new \RuntimeException('KINESCOPE_API_KEY environment variable not set');
        }
        return new self(Credentials::fromString($apiKey));
    }

    public function videos(): VideosService
    {
        return $this->videos ??= new VideosService($this->getApiClient());
    }

    public function subtitles(): SubtitlesService
    {
        return $this->subtitles ??= new SubtitlesService($this->getApiClient());
    }

    public function annotations(): AnnotationsService
    {
        return $this->annotations ??= new AnnotationsService($this->getApiClient());
    }

    public function projects(): ProjectsService
    {
        return $this->projects ??= new ProjectsService($this->getApiClient());
    }

    public function folders(): FoldersService
    {
        return $this->folders ??= new FoldersService($this->getApiClient());
    }

    public function playlists(): PlaylistsService
    {
        return $this->playlists ??= new PlaylistsService($this->getApiClient());
    }
}
```

#### 4.3.2 Credentials - Value Object
```php
final readonly class Credentials
{
    private function __construct(
        public string $apiKey
    ) {}

    public static function fromString(string $apiKey): self
    {
        if (empty(trim($apiKey))) {
            throw new \InvalidArgumentException('API key cannot be empty');
        }
        return new self($apiKey);
    }

    public function getAuthorizationHeader(): string
    {
        return 'Bearer ' . $this->apiKey;
    }
}
```

#### 4.3.3 ApiClientFactory - Fluent Builder
```php
final class ApiClientFactory
{
    private ?Credentials $credentials = null;
    private string $baseUrl = 'https://api.kinescope.io';
    private int $timeout = 30;
    private int $retryAttempts = 3;
    private ?LoggerInterface $logger = null;

    public static function create(): self
    {
        return new self();
    }

    public function withCredentials(Credentials $credentials): self
    {
        $clone = clone $this;
        $clone->credentials = $credentials;
        return $clone;
    }

    public function withBaseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->baseUrl = $baseUrl;
        return $clone;
    }

    public function withTimeout(int $timeout): self
    {
        $clone = clone $this;
        $clone->timeout = $timeout;
        return $clone;
    }

    public function withRetryAttempts(int $retryAttempts): self
    {
        $clone = clone $this;
        $clone->retryAttempts = $retryAttempts;
        return $clone;
    }

    public function withLogger(LoggerInterface $logger): self
    {
        $clone = clone $this;
        $clone->logger = $logger;
        return $clone;
    }

    public function build(): ApiClientInterface
    {
        if ($this->credentials === null) {
            throw new \RuntimeException('Credentials are required');
        }

        return new ApiClient(
            credentials: $this->credentials,
            baseUrl: $this->baseUrl,
            timeout: $this->timeout,
            retryAttempts: $this->retryAttempts,
            logger: $this->logger
        );
    }
}
```

#### 4.3.4 Readonly DTO с fromArray()
```php
final readonly class VideoDTO
{
    public function __construct(
        public string $id,
        public string $title,
        public ?string $description,
        public VideoStatus $status,
        public int $duration,
        public ?string $embedCode,
        public ?string $hlsLink,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
        /** @var array<AssetDTO> */
        public array $assets,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            description: $data['description'] ?? null,
            status: VideoStatus::from($data['status']),
            duration: $data['duration'] ?? 0,
            embedCode: $data['embed_code'] ?? null,
            hlsLink: $data['hls_link'] ?? null,
            createdAt: new \DateTimeImmutable($data['created_at']),
            updatedAt: new \DateTimeImmutable($data['updated_at']),
            assets: array_map(
                fn(array $asset) => AssetDTO::fromArray($asset),
                $data['assets'] ?? []
            ),
        );
    }
}
```

#### 4.3.5 ResponseHandler - Маппинг HTTP ошибок
```php
final class ResponseHandler
{
    public function handle(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode >= 200 && $statusCode < 300) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        $this->throwException($statusCode, $body);
    }

    private function throwException(int $statusCode, string $body): never
    {
        $message = $this->extractErrorMessage($body);

        throw match ($statusCode) {
            400 => new BadRequestException($message, $statusCode),
            401 => new AuthenticationException($message, $statusCode),
            402 => new PaymentRequiredException($message, $statusCode),
            403 => new ForbiddenException($message, $statusCode),
            404 => new NotFoundException($message, $statusCode),
            422 => new ValidationException($message, $statusCode),
            429 => new RateLimitException($message, $statusCode),
            default => new KinescopeException($message, $statusCode),
        };
    }
}
```

## 5. PHP Enums

### 5.1 Перечисления версии 1.0

```php
// VideoStatus - Статус видео
enum VideoStatus: string
{
    case PENDING = 'pending';
    case UPLOADING = 'uploading';
    case PROCESSING = 'processing';
    case DONE = 'done';
    case ERROR = 'error';
}

// PrivacyType - Тип приватности проекта
enum PrivacyType: string
{
    case ANYWHERE = 'anywhere';      // Воспроизведение везде
    case CUSTOM = 'custom';          // Только на указанных доменах
    case NOWHERE = 'nowhere';        // Запрет воспроизведения
}

// SubtitleLanguage - Языки субтитров
enum SubtitleLanguage: string
{
    case RU = 'ru';
    case EN = 'en';
    case DE = 'de';
    case FR = 'fr';
    case ES = 'es';
    case IT = 'it';
    case PT = 'pt';
    case ZH = 'zh';
    case JA = 'ja';
    case KO = 'ko';
    // ... другие языки по ISO 639-1
}

// HttpMethod - HTTP методы (внутреннее использование)
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
```

> **Примечание**: Дополнительные Enum (EventType, CatalogType, WebhookEvent и др.)
> будут добавлены в следующих версиях SDK вместе с соответствующими сервисами.

## 6. Технические требования

### 6.1 Системные требования
- **PHP**: >= 8.4 (обязательно для readonly classes, enums, named arguments, property hooks)
- **Расширения**:
  - `ext-json` - для работы с JSON
  - `ext-curl` - для HTTP запросов (опционально, через PSR-18)
  - `ext-mbstring` - для работы со строками

### 6.2 Зависимости

```json
{
    "require": {
        "php": ">=8.4",
        "ext-json": "*",
        "psr/http-client": "^1.0",
        "psr/http-factory": "^1.0",
        "psr/http-message": "^1.0|^2.0",
        "psr/log": "^1.1|^2.0|^3.0",
        "php-http/discovery": "^1.14",
        "php-http/httplug": "^2.2",
        "symfony/uid": "^5.4|^6.0|^7.0",
        "symfony/mime": "^5.4|^6.0|^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "phpstan/phpstan": "^1.0",
        "phpstan/phpstan-strict-rules": "^1.0",
        "friendsofphp/php-cs-fixer": "^3.0",
        "php-http/mock-client": "^1.5",
        "nyholm/psr7": "^1.5",
        "fakerphp/faker": "^1.20",
        "monolog/monolog": "^3.0"
    },
    "suggest": {
        "guzzlehttp/guzzle": "For HTTP client implementation",
        "symfony/http-client": "For HTTP client implementation",
        "nyholm/psr7": "For PSR-7/PSR-17 implementation"
    }
}
```

### 6.3 Стандарты кодирования
- **PSR-1**: Basic Coding Standard
- **PSR-4**: Autoloading Standard
- **PSR-7**: HTTP Message Interface
- **PSR-12**: Extended Coding Style Guide
- **PSR-17**: HTTP Factories
- **PSR-18**: HTTP Client Interface

### 6.4 Инструменты разработки
- `phpunit/phpunit` (^10.0|^11.0) - тестирование
- `phpstan/phpstan` (^1.0) - статический анализ (level 8)
- `friendsofphp/php-cs-fixer` (^3.0) - форматирование кода
- `php-http/mock-client` (^1.5) - мокирование HTTP клиента в тестах

### 6.5 Docker-окружение разработки

Разработка и тестирование SDK выполняется в Docker-контейнерах для обеспечения консистентного окружения.

#### 6.5.1 Структура Docker

```
docker/
└── php-cli/
    ├── Dockerfile
    └── conf.d/
        └── php.ini
docker-compose.yaml
.env
.env.local (не включается в репозиторий)
```

#### 6.5.2 Dockerfile (PHP 8.4 CLI)

```dockerfile
# Multi-stage build для PHP 8.4
FROM mlocati/php-extension-installer:2.4 AS php-extension-installer
FROM composer:2.8 AS composer

FROM php:8.4-cli-bookworm AS dev-php

ARG UID=1000
ARG GID=1000

COPY --from=php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    bcmath \
    intl \
    pcntl \
    opcache \
    yaml \
    zip \
    curl \
    mbstring \
    xml \
    dom \
    fileinfo

COPY conf.d/php.ini /usr/local/etc/php/conf.d/php.ini

ENV COMPOSER_CACHE_DIR=/tmp/composer/cache

WORKDIR /var/www/html

RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data
RUN mkdir -p /tmp/composer/cache && chown -R www-data:www-data /tmp/composer/cache

USER www-data

CMD ["php", "-a"]
```

#### 6.5.3 docker-compose.yaml

```yaml
services:
  php-cli:
    build:
      context: ./docker/php-cli
      args:
        UID: ${UID:-1000}
        GID: ${GID:-1000}
    volumes:
      - ./:/var/www/html
    working_dir: /var/www/html
    env_file:
      - .env
      - .env.local
    environment:
      COMPOSER_CACHE_DIR: /tmp/composer/cache
    networks:
      - kinescope-network

networks:
  kinescope-network:
    driver: bridge
```

#### 6.5.4 Файлы окружения

`.env` (включается в репозиторий):
```env
# PHP container
UID=1000
GID=1000

# Kinescope API (пустые значения, переопределяются в .env.local)
KINESCOPE_API_KEY=
```

`.env.local` (не включается в репозиторий, добавить в .gitignore):
```env
KINESCOPE_API_KEY=your-api-key-here
```

### 6.6 Makefile

Все операции выполняются через Makefile для стандартизации команд разработки.

#### 6.6.1 Команды управления Docker

| Команда | Описание |
|---------|----------|
| `make docker-init` | Инициализация: сборка образов + установка зависимостей |
| `make docker-up` | Запуск контейнеров в фоновом режиме |
| `make docker-down` | Остановка контейнеров |
| `make docker-down-clear` | Остановка контейнеров и удаление volumes |
| `make docker-restart` | Перезапуск контейнеров |
| `make docker-rebuild` | Пересборка образов без кэша |

#### 6.6.2 Команды Composer

| Команда | Описание |
|---------|----------|
| `make composer-install` | Установка зависимостей |
| `make composer-update` | Обновление зависимостей |
| `make composer-dumpautoload` | Перегенерация autoload |
| `make composer args="..."` | Произвольная команда Composer |

#### 6.6.3 Команды проверки кода

| Команда | Описание |
|---------|----------|
| `make lint-all` | Запуск всех линтеров |
| `make lint-cs-fixer` | Проверка стиля кода (dry-run) |
| `make lint-cs-fixer-fix` | Автоматическое исправление стиля |
| `make lint-phpstan` | Статический анализ PHPStan |
| `make lint-rector` | Проверка Rector (dry-run) |
| `make lint-rector-fix` | Применение рефакторинга Rector |

#### 6.6.4 Команды тестирования

| Команда | Описание |
|---------|----------|
| `make test-unit` | Запуск unit-тестов |
| `make test-integration` | Запуск интеграционных тестов |

#### 6.6.5 Вспомогательные команды

| Команда | Описание |
|---------|----------|
| `make php-cli-bash` | Доступ к shell контейнера PHP |
| `make php-cli-root` | Root-доступ к контейнеру PHP |
| `make clear-cache` | Очистка кэша и временных файлов |
| `make show-env` | Показать переменные окружения |

#### 6.6.6 Пример Makefile

```makefile
.PHONY: docker-init docker-up docker-down docker-restart docker-rebuild \
        composer-install composer-update lint-all lint-cs-fixer lint-phpstan \
        test-unit test-integration php-cli-bash clear-cache

# Docker
docker-init: docker-up composer-install

docker-up:
	docker compose up -d

docker-down:
	docker compose down

docker-down-clear:
	docker compose down -v

docker-restart: docker-down docker-up

docker-rebuild:
	docker compose build --no-cache

# Composer
composer-install:
	docker compose exec php-cli composer install

composer-update:
	docker compose exec php-cli composer update

composer-dumpautoload:
	docker compose exec php-cli composer dumpautoload

composer:
	docker compose exec php-cli composer $(args)

# Linting
lint-all: lint-cs-fixer lint-phpstan lint-rector

lint-cs-fixer:
	docker compose exec php-cli vendor/bin/php-cs-fixer fix --dry-run --diff

lint-cs-fixer-fix:
	docker compose exec php-cli vendor/bin/php-cs-fixer fix

lint-phpstan:
	docker compose exec php-cli vendor/bin/phpstan analyse --memory-limit=1G

lint-rector:
	docker compose exec php-cli vendor/bin/rector process --dry-run

lint-rector-fix:
	docker compose exec php-cli vendor/bin/rector process

# Testing
test-unit:
	docker compose exec php-cli vendor/bin/phpunit --testsuite=unit

test-integration:
	docker compose exec php-cli vendor/bin/phpunit --testsuite=integration

# Utils
php-cli-bash:
	docker compose exec php-cli bash

php-cli-root:
	docker compose exec -u root php-cli bash

clear-cache:
	docker compose exec php-cli rm -rf var/cache/*
	docker compose exec php-cli rm -rf .phpunit.result.cache

show-env:
	docker compose exec php-cli env | sort
```

## 7. Тестирование

### 7.1 Модульные тесты (Unit Tests)
- Покрытие всех публичных методов сервисов
- Тестирование DTO через `fromArray()` с валидными и невалидными данными
- Тестирование Enums
- Тестирование обработки ошибок (ResponseHandler)
- Тестирование валидации Credentials
- Мокирование HTTP клиента через `php-http/mock-client`

### 7.2 Интеграционные тесты

Интеграционные тесты **обязательны** для каждого сервиса и запускаются с реальным API.

#### 7.2.1 Требования к интеграционным тестам
- Тесты требуют переменную окружения `KINESCOPE_API_KEY` с валидным API ключом
- Тесты помечаются группой `@group integration` для возможности отдельного запуска
- Тесты должны быть идемпотентными и не изменять состояние аккаунта
- При отсутствии API ключа тесты пропускаются (`markTestSkipped`)

#### 7.2.2 Обязательные интеграционные тесты

| Сервис | Тестовый класс | Проверяемые методы |
|--------|----------------|-------------------|
| Videos | `VideosIntegrationTest` | `list()`, `get()` |
| Subtitles | `SubtitlesIntegrationTest` | `list()`, `get()` |
| Annotations | `AnnotationsIntegrationTest` | `list()`, `get()` |
| Projects | `ProjectsIntegrationTest` | `list()`, `get()` |
| Folders | `FoldersIntegrationTest` | `list()`, `get()` |
| Playlists | `PlaylistsIntegrationTest` | `list()`, `get()`, `entities()` |

#### 7.2.3 Проверки интеграционных тестов
- Корректность маппинга JSON ответа в DTO
- Правильность работы пагинации (page, perPage, total)
- Обработка ошибок 401 (неверный ключ), 404 (не найдено)
- Соответствие типов полей DTO ожидаемым значениям

#### 7.2.4 Пример интеграционного теста
```php
<?php

namespace Kinescope\Tests\Integration;

use Kinescope\Services\ServiceFactory;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class VideosIntegrationTest extends TestCase
{
    private ?ServiceFactory $factory = null;

    protected function setUp(): void
    {
        $apiKey = getenv('KINESCOPE_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }
        $this->factory = ServiceFactory::fromEnvironment();
    }

    public function testListVideosReturnsVideoListResult(): void
    {
        $result = $this->factory->videos()->list(page: 1, perPage: 5);

        $this->assertInstanceOf(VideoListResult::class, $result);
        $this->assertIsArray($result->getData());
        $this->assertNotNull($result->getMeta());
        $this->assertGreaterThanOrEqual(0, $result->getMeta()->total);
    }

    public function testGetVideoReturnsVideoDTO(): void
    {
        // Сначала получаем список, чтобы взять реальный ID
        $list = $this->factory->videos()->list(page: 1, perPage: 1);

        if (empty($list->getData())) {
            $this->markTestSkipped('No videos available in account');
        }

        $videoId = $list->getData()[0]->id;
        $video = $this->factory->videos()->get($videoId);

        $this->assertInstanceOf(VideoDTO::class, $video);
        $this->assertEquals($videoId, $video->id);
        $this->assertNotEmpty($video->title);
    }

    public function testGetNonExistentVideoThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $this->factory->videos()->get('non-existent-video-id');
    }
}
```

### 7.3 Требования к покрытию
- Минимальное покрытие кода: **80%**
- Сервисы и Core компоненты: **90%+**
- DTO классы: **100%** (простые, но важные)

### 7.4 Структура тестов (версия 1.0)
```
tests/
├── Unit/
│   ├── Core/
│   │   ├── CredentialsTest.php
│   │   ├── ApiClientFactoryTest.php
│   │   ├── ResponseHandlerTest.php
│   │   └── PaginationTest.php
│   ├── Services/
│   │   ├── ServiceFactoryTest.php
│   │   ├── Videos/
│   │   │   ├── VideosServiceTest.php
│   │   │   ├── SubtitlesServiceTest.php
│   │   │   └── AnnotationsServiceTest.php
│   │   ├── Projects/
│   │   │   └── ProjectsServiceTest.php
│   │   ├── Folders/
│   │   │   └── FoldersServiceTest.php
│   │   └── Playlists/
│   │       └── PlaylistsServiceTest.php
│   ├── DTO/
│   │   ├── Video/
│   │   │   ├── VideoDTOTest.php
│   │   │   ├── VideoListResultTest.php
│   │   │   ├── SubtitleDTOTest.php
│   │   │   └── AnnotationDTOTest.php
│   │   ├── Project/
│   │   │   └── ProjectDTOTest.php
│   │   ├── Folder/
│   │   │   └── FolderDTOTest.php
│   │   ├── Playlist/
│   │   │   └── PlaylistDTOTest.php
│   │   └── Common/
│   │       ├── PaginatedResponseTest.php
│   │       └── MetaDTOTest.php
│   ├── Enum/
│   │   ├── VideoStatusTest.php
│   │   ├── PrivacyTypeTest.php
│   │   └── SubtitleLanguageTest.php
│   └── Exception/
│       └── ExceptionsTest.php
├── Integration/
│   ├── VideosIntegrationTest.php
│   ├── SubtitlesIntegrationTest.php
│   ├── AnnotationsIntegrationTest.php
│   ├── ProjectsIntegrationTest.php
│   ├── FoldersIntegrationTest.php
│   └── PlaylistsIntegrationTest.php
├── Fixtures/
│   ├── video_response.json
│   ├── video_list_response.json
│   ├── project_response.json
│   ├── folder_response.json
│   ├── playlist_response.json
│   └── error_responses/
│       ├── 401_unauthorized.json
│       ├── 404_not_found.json
│       └── 422_validation_error.json
└── TestCase.php
```

### 7.5 Пример Unit теста с Mock клиентом
```php
<?php

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Services\Videos\VideosService;
use Kinescope\DTO\Video\VideoDTO;
use Kinescope\DTO\Video\VideoListResult;
use Kinescope\Enum\VideoStatus;
use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class VideosServiceTest extends TestCase
{
    private MockClient $mockClient;
    private VideosService $service;

    protected function setUp(): void
    {
        $this->mockClient = new MockClient();
        $this->service = new VideosService($this->createApiClient($this->mockClient));
    }

    public function testListVideosReturnsVideoListResult(): void
    {
        $responseBody = json_encode([
            'data' => [
                [
                    'id' => '550e8400-e29b-41d4-a716-446655440000',
                    'title' => 'Test Video',
                    'status' => 'done',
                    'duration' => 120,
                    'created_at' => '2024-01-01T00:00:00Z',
                    'updated_at' => '2024-01-01T00:00:00Z',
                    'assets' => [],
                ]
            ],
            'meta' => [
                'total' => 1,
                'page' => 1,
                'per_page' => 20
            ]
        ]);

        $this->mockClient->addResponse(new Response(200, [], $responseBody));

        $result = $this->service->list(page: 1, perPage: 20);

        $this->assertInstanceOf(VideoListResult::class, $result);
        $this->assertCount(1, $result->getData());
        $this->assertEquals(1, $result->getMeta()->total);
    }

    public function testGetVideoReturnsVideoDTO(): void
    {
        $responseBody = json_encode([
            'data' => [
                'id' => '550e8400-e29b-41d4-a716-446655440000',
                'title' => 'Test Video',
                'description' => 'Test description',
                'status' => 'done',
                'duration' => 120,
                'created_at' => '2024-01-01T00:00:00Z',
                'updated_at' => '2024-01-01T00:00:00Z',
                'assets' => [],
            ]
        ]);

        $this->mockClient->addResponse(new Response(200, [], $responseBody));

        $video = $this->service->get('550e8400-e29b-41d4-a716-446655440000');

        $this->assertInstanceOf(VideoDTO::class, $video);
        $this->assertEquals('Test Video', $video->title);
        $this->assertEquals(VideoStatus::DONE, $video->status);
        $this->assertEquals(120, $video->duration);
    }

    public function testGetVideoThrowsNotFoundExceptionFor404(): void
    {
        $this->mockClient->addResponse(new Response(404, [], '{"error": "Not found"}'));

        $this->expectException(\Kinescope\Exception\NotFoundException::class);

        $this->service->get('non-existent-id');
    }
}
```

## 8. Документация

### 8.1 README.md
- Описание библиотеки
- Установка через Composer
- Быстрый старт
- Примеры использования
- Ссылки на полную документацию

### 8.2 Код документация
- PHPDoc комментарии для всех публичных методов и классов
- Примеры использования в docblocks
- Описание параметров и возвращаемых значений
- Описание возможных исключений (`@throws`)

### 8.3 Дополнительная документация
- `CHANGELOG.md` - история изменений
- `CONTRIBUTING.md` - руководство для контрибьюторов
- `LICENSE` - лицензия (MIT)
- `docs/` - расширенная документация
  - `installation.md`
  - `authentication.md`
  - `videos.md`
  - `projects.md`
  - `live-streaming.md`
  - `webhooks.md`
  - `error-handling.md`
  - `examples.md`

## 9. Безопасность

### 9.1 Обработка API ключей
- Никогда не логировать API ключи
- Поддержка загрузки из переменных окружения
- Credentials как immutable value object
- Предупреждения о безопасном хранении ключей в документации

### 9.2 Валидация данных
- Валидация входящих параметров в сервисах
- Санитизация данных перед отправкой
- Типизация через PHP 8.4 типы и readonly классы

### 9.3 HTTPS
- Все запросы только через HTTPS
- Проверка SSL сертификатов по умолчанию

## 10. Производительность

### 10.1 Lazy Loading сервисов
- Сервисы создаются только при первом обращении
- Минимальный overhead при инициализации SDK

### 10.2 Пагинация
- Поддержка эффективной пагинации через `Pagination` value object
- Итераторы для удобного обхода больших списков (опционально)

### 10.3 Chunked Upload
- Поддержка загрузки больших файлов частями
- Callback для отслеживания прогресса

### 10.4 Retry механизм
- Автоматический retry для временных ошибок (5xx, network errors)
- Настраиваемое количество попыток
- Exponential backoff

## 11. Composer пакет

### 11.1 composer.json
```json
{
    "name": "kinescope/php-sdk",
    "description": "Official PHP SDK for Kinescope API - video management platform",
    "keywords": ["kinescope", "video", "api", "sdk", "streaming", "live", "vod"],
    "type": "library",
    "license": "MIT",
    "authors": [
        {
            "name": "Kinescope Team",
            "email": "dev@kinescope.io"
        }
    ],
    "require": {
        "php": ">=8.4",
        "ext-json": "*",
        "psr/http-client": "^1.0",
        "psr/http-factory": "^1.0",
        "psr/http-message": "^1.0|^2.0",
        "psr/log": "^1.1|^2.0|^3.0",
        "php-http/discovery": "^1.14",
        "php-http/httplug": "^2.2",
        "symfony/uid": "^5.4|^6.0|^7.0",
        "symfony/mime": "^5.4|^6.0|^7.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0|^11.0",
        "phpstan/phpstan": "^1.0",
        "phpstan/phpstan-strict-rules": "^1.0",
        "friendsofphp/php-cs-fixer": "^3.0",
        "php-http/mock-client": "^1.5",
        "nyholm/psr7": "^1.5",
        "fakerphp/faker": "^1.20",
        "monolog/monolog": "^3.0"
    },
    "suggest": {
        "guzzlehttp/guzzle": "For HTTP client implementation",
        "symfony/http-client": "For HTTP client implementation",
        "nyholm/psr7": "For PSR-7/PSR-17 implementation"
    },
    "autoload": {
        "psr-4": {
            "Kinescope\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Kinescope\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "vendor/bin/phpunit",
        "test-coverage": "vendor/bin/phpunit --coverage-html coverage",
        "phpstan": "vendor/bin/phpstan analyse src/ --level=8",
        "fix-style": "vendor/bin/php-cs-fixer fix src/",
        "check-style": "vendor/bin/php-cs-fixer fix src/ --dry-run --diff"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable"
}
```

## 12. CI/CD

### 12.1 GitHub Actions
- Автоматический запуск тестов при каждом push/PR
- Матрица PHP версий: 8.4
- Проверка стиля кода (PHP CS Fixer)
- Статический анализ (PHPStan level 8)
- Генерация отчетов о покрытии кода
- Автоматическая публикация на Packagist при создании тега

### 12.2 Версионирование
- Semantic Versioning (MAJOR.MINOR.PATCH)
- Автоматическая генерация CHANGELOG
- Теги для релизов

## 13. Этапы разработки версии 1.0

> **Примечание**: Версия 1.0 реализует только read-only операции для базовых сервисов.
> Следующие этапы упрощены для достижения MVP.

### Этап 1: Foundation (Основа)
- [ ] Создание структуры проекта
- [ ] Настройка Composer с зависимостями
- [ ] Создание Docker-окружения:
  - [ ] Dockerfile (PHP 8.4 CLI)
  - [ ] docker-compose.yaml
  - [ ] Файлы .env и .env.local.example
- [ ] Создание Makefile со всеми командами разработки
- [ ] Реализация Core компонентов:
  - [ ] `Credentials` - Value object для API ключа
  - [ ] `ApiClient` - HTTP клиент с retry логикой
  - [ ] `ApiClientFactory` - Fluent builder для клиента
  - [ ] `ResponseHandler` - Обработка ответов и маппинг ошибок
  - [ ] `JsonDecoder` - Декодирование JSON
  - [ ] `Pagination` - Value object пагинации
- [ ] Реализация иерархии исключений:
  - [ ] `KinescopeException` (базовое)
  - [ ] `AuthenticationException` (401)
  - [ ] `PaymentRequiredException` (402)
  - [ ] `ForbiddenException` (403)
  - [ ] `NotFoundException` (404)
  - [ ] `BadRequestException` (400)
  - [ ] `ValidationException` (422)
  - [ ] `RateLimitException` (429)
  - [ ] `NetworkException`
- [ ] Создание Enums: `VideoStatus`, `PrivacyType`, `SubtitleLanguage`, `HttpMethod`
- [ ] Настройка PHPUnit с mock-client
- [ ] Настройка PHPStan level 8
- [ ] Настройка PHP CS Fixer
- [ ] Unit тесты для Core компонентов

### Этап 2: Core Services (Основные сервисы)
- [ ] Реализация `ServiceFactory`
- [ ] Реализация сервисов (только read-only операции):
  - [ ] `VideosService` - `list()`, `get()`
  - [ ] `SubtitlesService` - `list()`, `get()`
  - [ ] `AnnotationsService` - `list()`, `get()`
  - [ ] `ProjectsService` - `list()`, `get()`
  - [ ] `FoldersService` - `list()`, `get()`
  - [ ] `PlaylistsService` - `list()`, `get()`, `entities()`
- [ ] Реализация DTO для каждого сервиса:
  - [ ] `VideoDTO`, `VideoListResult`
  - [ ] `SubtitleDTO`, `SubtitleListResult`
  - [ ] `AnnotationDTO`, `AnnotationListResult`
  - [ ] `ProjectDTO`, `ProjectListResult`
  - [ ] `FolderDTO`, `FolderListResult`
  - [ ] `PlaylistDTO`, `PlaylistListResult`, `PlaylistEntityDTO`
  - [ ] `AssetDTO`, `MetaDTO`, `PaginatedResponse`
- [ ] Unit тесты для всех сервисов и DTO
- [ ] Достижение 80%+ покрытия кода

### Этап 3: Testing & Documentation (Тестирование и документация)
- [ ] Интеграционные тесты для каждого сервиса:
  - [ ] `VideosIntegrationTest`
  - [ ] `SubtitlesIntegrationTest`
  - [ ] `AnnotationsIntegrationTest`
  - [ ] `ProjectsIntegrationTest`
  - [ ] `FoldersIntegrationTest`
  - [ ] `PlaylistsIntegrationTest`
- [ ] Настройка GitHub Actions CI/CD:
  - [ ] Запуск unit тестов при каждом push/PR
  - [ ] Запуск PHPStan level 8
  - [ ] Запуск PHP CS Fixer (check)
  - [ ] Генерация отчетов о покрытии
- [ ] Документация:
  - [ ] README.md с примерами использования
  - [ ] PHPDoc для всех публичных методов
  - [ ] CHANGELOG.md
- [ ] Финализация:
  - [ ] Прохождение всех проверок CI
  - [ ] Code style фиксы
  - [ ] Подготовка к релизу v1.0.0
  - [ ] Публикация на Packagist

### Планы на следующие версии

После выпуска v1.0 планируется добавление:

**v1.1** - Операции записи для базовых сервисов:
- Videos: update, delete, move
- Projects: create, update, delete
- Folders: create, update, delete
- Playlists: create, update, delete, addEntities

**v1.2** - Дополнительные сервисы:
- Posters, Webhooks, Players, Tags

**v2.0** - Расширенные возможности:
- Upload, Live Events, Analytics, Billing

## 14. Поддержка и обновления

### 14.1 Политика поддержки версий
- Активная поддержка: текущая мажорная версия
- Исправления безопасности: предыдущая мажорная версия
- EOL: через 12 месяцев после релиза новой мажорной версии

### 14.2 Обновление документации API
- Регулярная проверка изменений в Kinescope API
- Обновление SDK при добавлении новых endpoints
- Поддержка обратной совместимости

## 15. Источники информации

Основная документация:
- OpenAPI спецификация: `.tasks/openapi.yaml`
- [Kinescope API Documentation](https://documenter.getpostman.com/view/10589901/TVCcXpNM)
- [Kinescope GitHub](https://github.com/kinescope)
- [Kinescope Official Website](https://www.kinescope.com/)

## 16. Контакты и поддержка

- GitHub Issues: для багов и feature requests
- Email: для приватных вопросов
- Документация: подробные гайды и примеры

---

**Дата создания**: 2026-01-19
**Дата обновления**: 2026-01-22
**Версия документа**: 3.0
**Статус**: Updated - Simplified for v1.0 (read-only operations only)
