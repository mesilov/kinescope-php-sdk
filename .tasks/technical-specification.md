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
- Обеспечить поддержку **всех** операций Kinescope API (v1 и v2)
- Использовать современные PHP 8.4+ возможности (readonly, enums, named arguments, property hooks)

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

### 3.1 Полный список API модулей

#### 3.1.1 Videos (v1) - Управление видео
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos` | Список видео с пагинацией и фильтрацией |
| GET | `/v1/videos/{video_id}` | Получение информации о видео |
| PATCH | `/v1/videos/{video_id}` | Обновление метаданных видео |
| DELETE | `/v1/videos/{video_id}` | Удаление видео |
| PUT | `/v1/videos/{video_id}/move` | Перемещение видео в другую папку/проект |
| PUT | `/v1/videos/{video_id}/chapters` | Обновление глав видео |
| POST | `/v1/videos/{video_id}/concat` | Склейка видео |
| POST | `/v1/videos/{video_id}/cut` | Обрезка видео |

#### 3.1.2 Posters - Постеры видео
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos/{video_id}/posters` | Список постеров видео |
| POST | `/v1/videos/{video_id}/posters` | Создание постера по времени |
| GET | `/v1/videos/{video_id}/posters/{poster_id}` | Получение постера |
| DELETE | `/v1/videos/{video_id}/posters/{poster_id}` | Удаление постера |
| POST | `/v1/videos/{video_id}/posters/{poster_id}/active` | Установка активного постера |

#### 3.1.3 Subtitles - Субтитры
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos/{video_id}/subtitles` | Список субтитров |
| POST | `/v1/videos/{video_id}/subtitles` | Добавление субтитров |
| GET | `/v1/videos/{video_id}/subtitles/{subtitle_id}` | Получение субтитров |
| PATCH | `/v1/videos/{video_id}/subtitles/{subtitle_id}` | Обновление субтитров |
| DELETE | `/v1/videos/{video_id}/subtitles/{subtitle_id}` | Удаление субтитров |
| PATCH | `/v1/videos/{video_id}/subtitles/reorder` | Изменение порядка субтитров |
| POST | `/v1/videos/{video_id}/subtitles/{subtitle_id}/copy` | Копирование субтитров |

#### 3.1.4 Annotations - Аннотации
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/videos/{video_id}/annotations` | Список аннотаций |
| POST | `/v1/videos/{video_id}/annotations` | Добавление аннотации |
| GET | `/v1/videos/{video_id}/annotations/{annotation_id}` | Получение аннотации |
| PUT | `/v1/videos/{video_id}/annotations/{annotation_id}` | Обновление аннотации |
| DELETE | `/v1/videos/{video_id}/annotations/{annotation_id}` | Удаление аннотации |

#### 3.1.5 Projects - Проекты
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/projects` | Список проектов |
| POST | `/v1/projects` | Создание проекта |
| GET | `/v1/projects/{project_id}` | Получение проекта |
| PUT | `/v1/projects/{project_id}` | Обновление проекта |
| DELETE | `/v1/projects/{project_id}` | Удаление проекта |

#### 3.1.6 Folders - Папки
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/projects/{project_id}/folders` | Список папок в проекте |
| POST | `/v1/projects/{project_id}/folders` | Создание папки |
| GET | `/v1/projects/{project_id}/folders/{folder_id}` | Получение папки |
| PUT | `/v1/projects/{project_id}/folders/{folder_id}` | Обновление папки |
| DELETE | `/v1/projects/{project_id}/folders/{folder_id}` | Удаление папки |

#### 3.1.7 Analytics - Аналитика
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/analytics/overview` | Обзор аналитики |
| GET | `/v1/analytics` | Кастомная аналитика с фильтрами |

#### 3.1.8 Billing - Биллинг
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/billing/usage` | Использование ресурсов |

#### 3.1.9 Additional Materials - Дополнительные материалы
| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/additional-material` | Загрузка материала |
| GET | `/v1/additional-materials/{material_id}/link` | Получение ссылки на материал |
| PUT | `/v1/additional-materials/{material_id}` | Обновление материала |
| DELETE | `/v1/additional-materials/{material_id}` | Удаление материала |
| PATCH | `/v1/additional-materials/reorder` | Изменение порядка материалов |

#### 3.1.10 Access Tokens - Токены доступа
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/access-tokens` | Список токенов |
| POST | `/v1/access-tokens` | Создание токена |
| GET | `/v1/access-tokens/{token_id}` | Получение токена |
| DELETE | `/v1/access-tokens/{token_id}` | Удаление токена |

#### 3.1.11 Players - Плееры
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/players` | Список плееров |
| POST | `/v1/players` | Создание плеера |
| GET | `/v1/players/{player_id}` | Получение плеера |
| PUT | `/v1/players/{player_id}` | Обновление плеера |
| POST | `/v1/players/{player_id}/logo` | Установка логотипа плеера |
| DELETE | `/v1/players/{player_id}/logo` | Удаление логотипа плеера |

#### 3.1.12 File Requests - Запросы на файлы
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/file-requests` | Список запросов |
| POST | `/v1/file-requests` | Создание запроса |
| GET | `/v1/file-requests/{file_request_id}` | Получение запроса |
| PUT | `/v1/file-requests/{file_request_id}` | Обновление запроса |
| DELETE | `/v1/file-requests/{file_request_id}` | Удаление запроса |

#### 3.1.13 DRM Auth - DRM авторизация
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/drm/auth` | Получение глобальных настроек DRM |
| PUT | `/v1/drm/auth` | Обновление глобальных настроек DRM |
| DELETE | `/v1/drm/auth` | Удаление глобальных настроек DRM |
| GET | `/v1/drm/auth/{project_id}` | Получение настроек DRM для проекта |
| PUT | `/v1/drm/auth/{project_id}` | Обновление настроек DRM для проекта |
| DELETE | `/v1/drm/auth/{project_id}` | Удаление настроек DRM для проекта |

#### 3.1.14 Privacy Domains - Домены приватности
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/privacy-domains` | Список доменов |
| POST | `/v1/privacy-domains` | Создание домена |
| PUT | `/v1/privacy-domains/{domain_id}` | Обновление домена |
| DELETE | `/v1/privacy-domains/{domain_id}` | Удаление домена |

#### 3.1.15 Tags - Теги
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/tags` | Список тегов |
| POST | `/v1/tags` | Создание тега |
| PUT | `/v1/tags/{tag_id}` | Обновление тега |
| DELETE | `/v1/tags/{tag_id}` | Удаление тега |

#### 3.1.16 Playlists - Плейлисты
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/playlists` | Список плейлистов |
| POST | `/v1/playlists` | Создание плейлиста |
| GET | `/v1/playlists/{playlist_id}` | Получение плейлиста |
| PATCH | `/v1/playlists/{playlist_id}` | Обновление плейлиста |
| DELETE | `/v1/playlists/{playlist_id}` | Удаление плейлиста |
| GET | `/v1/playlists/{playlist_id}/entities` | Список медиа в плейлисте |
| POST | `/v1/playlists/{playlist_id}/entities` | Добавление медиа в плейлист |
| DELETE | `/v1/playlists/{playlist_id}/entities` | Удаление медиа из плейлиста |
| PUT | `/v1/playlists/{playlist_id}/entities/{media_id}/move` | Перемещение медиа в плейлисте |

#### 3.1.17 Moderators - Модераторы
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/moderators` | Список модераторов |
| POST | `/v1/moderators` | Добавление модератора |
| GET | `/v1/moderators/{moderator_id}` | Получение модератора |
| PUT | `/v1/moderators/{moderator_id}` | Обновление модератора |
| DELETE | `/v1/moderators/{moderator_id}` | Удаление модератора |

#### 3.1.18 Webhooks - Вебхуки
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/webhooks` | Список вебхуков |
| POST | `/v1/webhooks` | Создание вебхука |
| PUT | `/v1/webhooks/{webhook_id}` | Обновление вебхука |
| DELETE | `/v1/webhooks/{webhook_id}` | Удаление вебхука |

#### 3.1.19 CDN - Управление CDN
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/cdn/zones` | Список CDN зон |
| POST | `/v1/cdn/zones` | Создание CDN зоны |
| PUT | `/v1/cdn/zones/{zone_id}` | Обновление CDN зоны |
| DELETE | `/v1/cdn/zones/{zone_id}` | Удаление CDN зоны |

#### 3.1.20 Upload (v2) - Загрузка видео
| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/` | Загрузка видео (multipart, с заголовками X-Parent-ID, X-Video-Title и т.д.) |

#### 3.1.21 Poster Upload (v2)
| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/v2/poster` | Загрузка постера |

#### 3.1.22 Live Events (v2) - Прямые эфиры
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v2/live/events` | Список событий |
| POST | `/v2/live/events` | Создание события |
| GET | `/v2/live/events/{event_id}` | Получение события |
| PUT | `/v2/live/events/{event_id}` | Обновление события |
| DELETE | `/v2/live/events/{event_id}` | Удаление события |
| GET | `/v2/live/events/{event_id}/videos` | Видео события |
| PUT | `/v2/live/events/{event_id}/enable` | Включение события |
| PUT | `/v2/live/events/{event_id}/complete` | Завершение события |
| PUT | `/v2/live/events/{event_id}/move` | Перемещение события |
| GET | `/v2/live/events/{event_id}/qos` | Качество обслуживания |
| POST | `/v2/live/events/{event_id}/stream` | Планирование стрима |
| PUT | `/v2/live/events/{event_id}/stream` | Обновление расписания стрима |
| GET | `/v2/live/events/{event_id}/stream/{stream_id}/chat` | Чат стрима |

#### 3.1.23 Restreams (v2) - Ретрансляции
| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/v2/live/events/{event_id}/restreams` | Создание ретрансляции |
| GET | `/v2/live/events/{event_id}/restreams/{restream_id}` | Получение ретрансляции |
| PUT | `/v2/live/events/{event_id}/restreams/{restream_id}` | Обновление ретрансляции |
| DELETE | `/v2/live/events/{event_id}/restreams/{restream_id}` | Удаление ретрансляции |

#### 3.1.24 Dictionaries - Справочники
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/v1/dictionaries/timezones` | Список часовых поясов |

#### 3.1.25 Avatars - Аватары
| Метод | Endpoint | Описание |
|-------|----------|----------|
| POST | `/v1/avatar` | Загрузка аватара |
| GET | `/v1/user/avatar/{avatar_id}` | Получение аватара пользователя |
| GET | `/v1/workspace/avatar/{avatar_id}` | Получение аватара воркспейса |

### 3.2 Обработка исключений

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

### 4.1 Структура проекта

```
src/
├── Contracts/                          # Интерфейсы
│   ├── ApiClientInterface.php          # Интерфейс HTTP клиента
│   └── ServiceInterface.php            # Базовый интерфейс сервиса
├── Core/                               # Ядро SDK
│   ├── ApiClient.php                   # HTTP клиент с retry логикой
│   ├── ApiClientFactory.php            # Фабрика с fluent builder
│   ├── Credentials.php                 # Value object для API ключа
│   ├── JsonDecoder.php                 # Декодер JSON ответов
│   ├── Pagination.php                  # Value object пагинации
│   └── ResponseHandler.php             # Обработчик ответов и маппинг ошибок
├── Enum/                               # PHP 8.4 Enums
│   ├── VideoStatus.php                 # pending, uploading, processing, done, error
│   ├── PrivacyType.php                 # anywhere, custom, nowhere
│   ├── EventType.php                   # one-time, recurring
│   ├── CatalogType.php                 # vod, live
│   ├── SubtitleLanguage.php            # Языки субтитров
│   ├── WebhookEvent.php                # Типы webhook событий
│   ├── PlayerWatermarkPosition.php     # Позиции водяного знака
│   └── AnalyticsMetric.php             # Типы метрик аналитики
├── Exception/                          # Иерархия исключений
│   ├── KinescopeException.php          # Базовое исключение
│   ├── AuthenticationException.php     # 401
│   ├── PaymentRequiredException.php    # 402
│   ├── ForbiddenException.php          # 403
│   ├── NotFoundException.php           # 404
│   ├── BadRequestException.php         # 400
│   ├── ValidationException.php         # 422
│   ├── RateLimitException.php          # 429
│   └── NetworkException.php            # Сетевые ошибки
├── Infrastructure/                     # Инфраструктурные утилиты
│   └── Filesystem/
│       ├── FileUploader.php            # Загрузка файлов с chunked upload
│       └── FileValidator.php           # Валидация файлов (размер, тип)
├── Services/                           # Сервисный слой
│   ├── ServiceFactory.php              # Основная фабрика сервисов
│   ├── AbstractService.php             # Базовый класс сервиса
│   ├── Videos/
│   │   ├── VideosService.php           # CRUD видео
│   │   ├── PostersService.php          # Постеры видео
│   │   ├── SubtitlesService.php        # Субтитры
│   │   ├── AnnotationsService.php      # Аннотации
│   │   └── ChaptersService.php         # Главы видео
│   ├── Projects/
│   │   └── ProjectsService.php         # Управление проектами
│   ├── Folders/
│   │   └── FoldersService.php          # Управление папками
│   ├── Analytics/
│   │   └── AnalyticsService.php        # Аналитика
│   ├── Billing/
│   │   └── BillingService.php          # Биллинг
│   ├── Webhooks/
│   │   └── WebhooksService.php         # Вебхуки
│   ├── Players/
│   │   └── PlayersService.php          # Плееры
│   ├── Playlists/
│   │   └── PlaylistsService.php        # Плейлисты
│   ├── Live/
│   │   ├── LiveEventsService.php       # Прямые эфиры
│   │   └── RestreamsService.php        # Ретрансляции
│   ├── AccessTokens/
│   │   └── AccessTokensService.php     # Токены доступа
│   ├── Drm/
│   │   └── DrmAuthService.php          # DRM авторизация
│   ├── Tags/
│   │   └── TagsService.php             # Теги
│   ├── PrivacyDomains/
│   │   └── PrivacyDomainsService.php   # Домены приватности
│   ├── Moderators/
│   │   └── ModeratorsService.php       # Модераторы
│   ├── Cdn/
│   │   └── CdnService.php              # CDN зоны
│   ├── FileRequests/
│   │   └── FileRequestsService.php     # Запросы на файлы
│   ├── AdditionalMaterials/
│   │   └── AdditionalMaterialsService.php # Дополнительные материалы
│   ├── Upload/
│   │   └── UploadService.php           # Загрузка файлов (v2)
│   ├── Dictionaries/
│   │   └── DictionariesService.php     # Справочники (часовые пояса)
│   └── Avatars/
│       └── AvatarsService.php          # Управление аватарами
└── DTO/                                # Data Transfer Objects (readonly)
    ├── Video/
    │   ├── VideoDTO.php                # Основная модель видео
    │   ├── VideoListResult.php         # Результат списка видео
    │   ├── PosterDTO.php               # Постер
    │   ├── SubtitleDTO.php             # Субтитры
    │   ├── AnnotationDTO.php           # Аннотация
    │   ├── AssetDTO.php                # Ассет видео (качество)
    │   └── ChapterDTO.php              # Глава видео
    ├── Project/
    │   ├── ProjectDTO.php              # Проект
    │   └── ProjectListResult.php       # Результат списка проектов
    ├── Folder/
    │   ├── FolderDTO.php               # Папка
    │   └── FolderListResult.php        # Результат списка папок
    ├── Analytics/
    │   ├── OverviewResult.php          # Обзор аналитики
    │   └── CustomAnalyticsResult.php   # Кастомная аналитика
    ├── Billing/
    │   └── UsageResult.php             # Использование ресурсов
    ├── Webhook/
    │   ├── WebhookDTO.php              # Вебхук
    │   └── WebhookListResult.php       # Список вебхуков
    ├── Player/
    │   ├── PlayerDTO.php               # Плеер
    │   └── PlayerListResult.php        # Список плееров
    ├── Playlist/
    │   ├── PlaylistDTO.php             # Плейлист
    │   ├── PlaylistListResult.php      # Список плейлистов
    │   └── PlaylistEntityDTO.php       # Элемент плейлиста
    ├── Live/
    │   ├── EventDTO.php                # Событие трансляции
    │   ├── EventListResult.php         # Список событий
    │   ├── StreamDTO.php               # Стрим
    │   ├── RestreamDTO.php             # Ретрансляция
    │   └── QosDTO.php                  # Качество обслуживания
    ├── AccessToken/
    │   ├── AccessTokenDTO.php          # Токен доступа
    │   └── AccessTokenListResult.php   # Список токенов
    ├── Drm/
    │   └── DrmAuthDTO.php              # DRM настройки
    ├── Tag/
    │   ├── TagDTO.php                  # Тег
    │   └── TagListResult.php           # Список тегов
    ├── PrivacyDomain/
    │   ├── PrivacyDomainDTO.php        # Домен приватности
    │   └── PrivacyDomainListResult.php # Список доменов
    ├── Moderator/
    │   ├── ModeratorDTO.php            # Модератор
    │   └── ModeratorListResult.php     # Список модераторов
    ├── Cdn/
    │   ├── CdnZoneDTO.php              # CDN зона
    │   └── CdnZoneListResult.php       # Список CDN зон
    ├── FileRequest/
    │   ├── FileRequestDTO.php          # Запрос на файл
    │   └── FileRequestListResult.php   # Список запросов
    ├── AdditionalMaterial/
    │   ├── AdditionalMaterialDTO.php   # Дополнительный материал
    │   └── AdditionalMaterialListResult.php # Список материалов
    ├── Dictionary/
    │   └── TimezoneDTO.php             # Часовой пояс
    ├── Avatar/
    │   └── AvatarDTO.php               # Аватар
    └── Common/
        ├── PaginatedResponse.php       # Базовый пагинированный ответ
        ├── SuccessResult.php           # Результат успешной операции
        └── MetaDTO.php                 # Метаданные ответа
```

### 4.2 Пример использования SDK

```php
<?php

use Kinescope\Services\ServiceFactory;
use Kinescope\Core\Credentials;
use Kinescope\Core\ApiClientFactory;
use Kinescope\Enum\VideoStatus;
use Kinescope\Enum\PrivacyType;
use Kinescope\Enum\EventType;
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
    // === Работа с видео ===

    // Получение списка видео с пагинацией
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

    // Получение конкретного видео
    $video = $factory->videos()->get('video-uuid');
    echo "Embed код: " . $video->embedCode . PHP_EOL;

    // Обновление метаданных видео
    $factory->videos()->update('video-uuid', [
        'title' => 'Обновленное название',
        'description' => 'Новое описание',
    ]);

    // Перемещение видео
    $factory->videos()->move('video-uuid', parentId: 'new-folder-uuid');

    // === Работа с постерами ===

    // Создание постера из кадра видео
    $poster = $factory->posters()->createFromTime('video-uuid', time: 10.5);

    // Установка активного постера
    $factory->posters()->setActive('video-uuid', 'poster-uuid');

    // === Работа с субтитрами ===

    // Добавление субтитров
    $subtitle = $factory->subtitles()->upload(
        videoId: 'video-uuid',
        filePath: '/path/to/subtitles.vtt',
        language: 'ru',
        title: 'Русские субтитры'
    );

    // Изменение порядка субтитров
    $factory->subtitles()->reorder('video-uuid', [
        'subtitle-uuid-1',
        'subtitle-uuid-2',
    ]);

    // === Работа с проектами ===

    // Создание проекта
    $project = $factory->projects()->create(
        name: 'Новый проект',
        privacyType: PrivacyType::CUSTOM,
        privacyDomains: ['example.com', 'mysite.ru']
    );

    echo "Создан проект: " . $project->id . PHP_EOL;

    // Список проектов
    $projects = $factory->projects()->list(page: 1, perPage: 10);

    // === Работа с папками ===

    // Создание папки в проекте
    $folder = $factory->folders()->create(
        projectId: $project->id,
        name: 'Видео уроки'
    );

    // === Загрузка видео (v2 API) ===

    // Простая загрузка
    $uploadResult = $factory->upload()->uploadFile(
        filePath: '/path/to/video.mp4',
        title: 'Моё видео',
        parentId: $folder->id
    );

    echo "Загружено видео: " . $uploadResult->videoId . PHP_EOL;

    // Загрузка с прогрессом (chunked upload)
    $factory->upload()->uploadFile(
        filePath: '/path/to/large-video.mp4',
        title: 'Большое видео',
        parentId: $folder->id,
        onProgress: function (int $uploaded, int $total) {
            $percent = round($uploaded / $total * 100);
            echo "Загружено: {$percent}%\r";
        }
    );

    // === Live события ===

    // Создание прямой трансляции
    $event = $factory->liveEvents()->create(
        name: 'Прямой эфир',
        type: EventType::ONE_TIME,
        parentId: $project->id,
        recordEnabled: true
    );

    echo "RTMP URL: " . $event->rtmpUrl . PHP_EOL;
    echo "Stream Key: " . $event->streamKey . PHP_EOL;

    // Включение трансляции
    $factory->liveEvents()->enable($event->id);

    // Добавление ретрансляции на YouTube
    $restream = $factory->restreams()->create(
        eventId: $event->id,
        name: 'YouTube',
        rtmpUrl: 'rtmp://a.rtmp.youtube.com/live2',
        streamKey: 'your-youtube-stream-key'
    );

    // Завершение трансляции
    $factory->liveEvents()->complete($event->id);

    // === Плейлисты ===

    // Создание плейлиста
    $playlist = $factory->playlists()->create(
        title: 'Курс по PHP',
        projectId: $project->id
    );

    // Добавление видео в плейлист
    $factory->playlists()->addEntities($playlist->id, [
        'video-uuid-1',
        'video-uuid-2',
    ]);

    // === Вебхуки ===

    // Создание вебхука
    $webhook = $factory->webhooks()->create(
        url: 'https://mysite.com/webhooks/kinescope',
        events: ['video.created', 'video.transcoded', 'video.deleted']
    );

    // === Аналитика ===

    // Обзор аналитики
    $overview = $factory->analytics()->overview(
        from: new \DateTimeImmutable('-30 days'),
        to: new \DateTimeImmutable('now'),
        projectId: $project->id
    );

    echo "Всего просмотров: " . $overview->views . PHP_EOL;
    echo "Уникальных зрителей: " . $overview->uniqueViewers . PHP_EOL;

    // === Биллинг ===

    $usage = $factory->billing()->usage(
        from: new \DateTimeImmutable('-30 days'),
        to: new \DateTimeImmutable('now')
    );

    echo "Использовано хранилища: " . $usage->storageBytes . " байт" . PHP_EOL;
    echo "Трафик: " . $usage->bandwidthBytes . " байт" . PHP_EOL;

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
    private ?ProjectsService $projects = null;
    // ... другие сервисы

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

    public function projects(): ProjectsService
    {
        return $this->projects ??= new ProjectsService($this->getApiClient());
    }

    // ... lazy инициализация других сервисов
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

### 5.1 Полный список перечислений

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

// PrivacyType - Тип приватности
enum PrivacyType: string
{
    case ANYWHERE = 'anywhere';      // Воспроизведение везде
    case CUSTOM = 'custom';          // Только на указанных доменах
    case NOWHERE = 'nowhere';        // Запрет воспроизведения
}

// EventType - Тип события трансляции
enum EventType: string
{
    case ONE_TIME = 'one-time';      // Разовая трансляция
    case RECURRING = 'recurring';    // Регулярная трансляция
}

// CatalogType - Тип каталога
enum CatalogType: string
{
    case VOD = 'vod';                // Video on Demand
    case LIVE = 'live';              // Live streaming
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

// WebhookEvent - Типы webhook событий
enum WebhookEvent: string
{
    case VIDEO_CREATED = 'video.created';
    case VIDEO_UPDATED = 'video.updated';
    case VIDEO_DELETED = 'video.deleted';
    case VIDEO_TRANSCODED = 'video.transcoded';
    case VIDEO_FAILED = 'video.failed';
    case LIVE_STARTED = 'live.started';
    case LIVE_STOPPED = 'live.stopped';
    case LIVE_RECORDING_READY = 'live.recording_ready';
}

// PlayerWatermarkPosition - Позиции водяного знака
enum PlayerWatermarkPosition: string
{
    case TOP_LEFT = 'top-left';
    case TOP_RIGHT = 'top-right';
    case BOTTOM_LEFT = 'bottom-left';
    case BOTTOM_RIGHT = 'bottom-right';
    case CENTER = 'center';
}

// AnalyticsMetric - Типы метрик аналитики
enum AnalyticsMetric: string
{
    case VIEWS = 'views';
    case UNIQUE_VIEWERS = 'unique_viewers';
    case WATCH_TIME = 'watch_time';
    case AVERAGE_WATCH_TIME = 'avg_watch_time';
    case ENGAGEMENT = 'engagement';
}

// HttpMethod - HTTP методы
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
```

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

## 7. Тестирование

### 7.1 Модульные тесты (Unit Tests)
- Покрытие всех публичных методов сервисов
- Тестирование DTO через `fromArray()` с валидными и невалидными данными
- Тестирование Enums
- Тестирование обработки ошибок (ResponseHandler)
- Тестирование валидации Credentials
- Мокирование HTTP клиента через `php-http/mock-client`

### 7.2 Интеграционные тесты
- Тесты с реальным API (через API ключ в env, опционально)
- Тесты загрузки файлов
- Тесты пагинации
- E2E сценарии: создание проекта -> загрузка видео -> добавление субтитров

### 7.3 Требования к покрытию
- Минимальное покрытие кода: **80%**
- Сервисы и Core компоненты: **90%+**
- DTO классы: **100%** (простые, но важные)

### 7.4 Структура тестов
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
│   │   │   ├── PostersServiceTest.php
│   │   │   └── SubtitlesServiceTest.php
│   │   ├── Projects/
│   │   │   └── ProjectsServiceTest.php
│   │   └── ... (все сервисы)
│   ├── DTO/
│   │   ├── Video/
│   │   │   ├── VideoDTOTest.php
│   │   │   └── VideoListResultTest.php
│   │   └── ... (все DTO)
│   ├── Enum/
│   │   ├── VideoStatusTest.php
│   │   └── ... (все Enums)
│   └── Exception/
│       └── ExceptionsTest.php
├── Integration/
│   ├── VideosIntegrationTest.php
│   ├── ProjectsIntegrationTest.php
│   ├── UploadIntegrationTest.php
│   └── LiveEventsIntegrationTest.php
├── Fixtures/
│   ├── video_response.json
│   ├── project_response.json
│   └── ... (JSON фикстуры)
└── TestCase.php
```

### 7.5 Пример Unit теста с Mock клиентом
```php
<?php

namespace Kinescope\Tests\Unit\Services\Videos;

use Kinescope\Services\Videos\VideosService;
use Kinescope\DTO\Video\VideoDTO;
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

## 13. Этапы разработки

### Этап 1: Основа (Foundation)
- [ ] Создание структуры проекта
- [ ] Настройка Composer с зависимостями
- [ ] Реализация Core компонентов:
  - [ ] Credentials
  - [ ] ApiClient
  - [ ] ApiClientFactory
  - [ ] ResponseHandler
  - [ ] JsonDecoder
  - [ ] Pagination
- [ ] Реализация иерархии исключений
- [ ] Настройка PHPUnit с mock-client
- [ ] Настройка PHPStan level 8
- [ ] Настройка PHP CS Fixer

### Этап 2: Основные сервисы (Core Services)
- [ ] Реализация ServiceFactory
- [ ] Реализация VideosService с DTO
- [ ] Реализация ProjectsService с DTO
- [ ] Реализация FoldersService с DTO
- [ ] Создание всех Enums
- [ ] Unit тесты для сервисов

### Этап 3: Дополнительные сервисы (Additional Services)
- [ ] PostersService
- [ ] SubtitlesService
- [ ] AnnotationsService
- [ ] PlayersService
- [ ] PlaylistsService
- [ ] WebhooksService
- [ ] TagsService
- [ ] PrivacyDomainsService
- [ ] ModeratorsService
- [ ] CdnService
- [ ] FileRequestsService
- [ ] AdditionalMaterialsService
- [ ] Unit тесты

### Этап 4: Live и специальные функции
- [ ] LiveEventsService
- [ ] RestreamsService
- [ ] UploadService (chunked upload)
- [ ] AnalyticsService
- [ ] BillingService
- [ ] AccessTokensService
- [ ] DrmAuthService
- [ ] Unit тесты

### Этап 5: Тестирование и документация
- [ ] Интеграционные тесты
- [ ] Достижение 80%+ покрытия кода
- [ ] Написание документации
- [ ] Примеры использования
- [ ] README.md

### Этап 6: Финализация
- [ ] Настройка GitHub Actions CI/CD
- [ ] Прохождение PHPStan level 8
- [ ] Code style фиксы
- [ ] Подготовка к релизу
- [ ] Публикация на Packagist

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
**Версия документа**: 2.0
**Статус**: Updated
