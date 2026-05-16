# video-duration-parsing Specification

## Purpose
Defines how video durations from API payloads are normalized to whole seconds and consumed by statistics aggregation.

## Requirements

### Requirement: Normalize video duration to whole seconds
The SDK SHALL expose `VideoDTO::$duration` as whole seconds (`int`) and SHALL round numeric fractional API `duration` values to the nearest whole second when constructing `VideoDTO`.

#### Scenario: Fractional duration rounds to nearest second
- **WHEN** `VideoDTO::fromArray()` receives `duration = 59.96`
- **THEN** `VideoDTO::$duration === 60`

#### Scenario: Fraction below half rounds down
- **WHEN** `VideoDTO::fromArray()` receives `duration = 179.305`
- **THEN** `VideoDTO::$duration === 179`

#### Scenario: Half second rounds away from zero
- **WHEN** `VideoDTO::fromArray()` receives `duration = 179.5`
- **THEN** `VideoDTO::$duration === 180`

#### Scenario: Missing duration remains zero
- **WHEN** `VideoDTO::fromArray()` receives no `duration` key
- **THEN** `VideoDTO::$duration === 0`

### Requirement: Statistics sums normalized video durations
The SDK SHALL compute statistics total duration by summing normalized `VideoDTO::$duration` whole-second values and SHALL NOT perform separate raw fractional-duration aggregation.

#### Scenario: Statistics uses normalized seconds
- **WHEN** `Statistics` aggregates videos whose `VideoDTO::$duration` values are `60` and `179`
- **THEN** the resulting `StatisticsDTO::getTotalSeconds()` returns `239`
