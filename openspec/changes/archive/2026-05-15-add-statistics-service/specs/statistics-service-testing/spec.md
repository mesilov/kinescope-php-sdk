## ADDED Requirements

### Requirement: Provide a reusable unit fake API client
The test suite SHALL provide `Kinescope\Tests\Unit\FakeApiClient` under `tests/Unit/FakeApiClient.php` for unit tests that need deterministic service-level API responses without network access.

#### Scenario: Fake client returns queued responses
- **WHEN** a test queues decoded response arrays and a service makes matching API calls
- **THEN** `FakeApiClient` records each request and returns the queued responses in order

#### Scenario: Fake client throws queued SDK exceptions
- **WHEN** a test queues a `Kinescope\Exception\KinescopeException` instance for a call
- **THEN** `FakeApiClient` records the request and throws that exception

#### Scenario: Fake client rejects unexpected calls
- **WHEN** a service makes a request after all queued outcomes are consumed
- **THEN** `FakeApiClient` throws `RuntimeException`

#### Scenario: Recorded requests preserve query shape
- **WHEN** a service sends query parameters to `FakeApiClient`
- **THEN** recorded requests preserve the exact query keys and scalar values passed by the service

### Requirement: Unit tests exercise Statistics through real Videos
Statistics unit tests SHALL instantiate `Statistics` with a real `Kinescope\Services\Videos\Videos` service backed by `FakeApiClient`, rather than mocking `Videos`.

#### Scenario: Statistics tests observe SDK query serialization
- **WHEN** a statistics unit test calls any public aggregation method
- **THEN** assertions can inspect the fake client's recorded `/v1/videos` requests, including pagination, scope filters, and the single scalar `status[] = done` query value

#### Scenario: Statistics tests observe DTO parsing
- **WHEN** `FakeApiClient` returns video rows
- **THEN** statistics unit tests aggregate the resulting `VideoDTO` instances produced by the real `Videos::list()` path

### Requirement: Unit tests cover ServiceFactory statistics wiring
The unit test suite SHALL cover `ServiceFactory::statistics()` lazy wiring.

#### Scenario: Factory reuses the Statistics service
- **WHEN** `ServiceFactory::statistics()` is called twice on the same factory
- **THEN** both calls return the identical `Statistics` instance

#### Scenario: Statistics uses the factory Videos service
- **WHEN** `ServiceFactory::statistics()` creates the `Statistics` service
- **THEN** it is wired to the same `Videos` instance returned by `ServiceFactory::videos()`

### Requirement: Unit tests verify generatedAt bounds
Statistics unit tests SHALL verify that `StatisticsDTO::$generatedAt` is captured during the public aggregation call.

#### Scenario: generatedAt falls inside the call window
- **WHEN** a test records `$before` immediately before a statistics call and `$after` immediately after it returns
- **THEN** the returned DTO satisfies `$before <= $dto->generatedAt <= $after`

### Requirement: Integration tests use statistics-specific fixtures
Statistics integration tests SHALL use `TESTS_STATISTICS_PROJECT_ID` and `TESTS_STATISTICS_FOLDER_ID` for scoped live checks and SHALL skip scoped tests when the matching value is empty.

#### Scenario: Account integration matches direct DONE list total
- **WHEN** integration credentials are configured
- **THEN** `Statistics::forAccount()->videosCount` matches `Videos::list(status: VideoStatus::DONE, pagination: new Pagination(1, 1))->getTotal()`

#### Scenario: Project integration matches direct DONE project list total
- **WHEN** integration credentials and `TESTS_STATISTICS_PROJECT_ID` are configured
- **THEN** `Statistics::forProject($projectId)->videosCount` matches `Videos::list(status: VideoStatus::DONE, projectId: $projectId, pagination: new Pagination(1, 1))->getTotal()`

#### Scenario: Folder integration matches direct DONE folder list total
- **WHEN** integration credentials and `TESTS_STATISTICS_FOLDER_ID` are configured
- **THEN** `Statistics::forFolder($folderId)->videosCount` matches `Videos::list(status: VideoStatus::DONE, folderId: $folderId, pagination: new Pagination(1, 1))->getTotal()`
