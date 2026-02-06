<?php

declare(strict_types=1);

namespace Kinescope\Tests\Unit\DTO\Video;

use Kinescope\DTO\Video\AnnotationDTO;
use PHPUnit\Framework\TestCase;

class AnnotationDTOTest extends TestCase
{
    public function testFromArrayCreatesValidAnnotationDTO(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'time' => 120,
            'title' => 'Chapter 1',
            'description' => 'Introduction',
            'url' => 'https://example.com/link',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-02T00:00:00Z',
        ];

        $annotation = AnnotationDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $annotation->id);
        $this->assertEquals('video-uuid', $annotation->videoId);
        $this->assertEquals(120, $annotation->time);
        $this->assertEquals('Chapter 1', $annotation->title);
        $this->assertEquals('Introduction', $annotation->description);
        $this->assertEquals('https://example.com/link', $annotation->url);
    }

    public function testFromArrayWithMinimalData(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'time' => 0,
            'title' => 'Start',
        ];

        $annotation = AnnotationDTO::fromArray($data);

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $annotation->id);
        $this->assertEquals('video-uuid', $annotation->videoId);
        $this->assertEquals(0, $annotation->time);
        $this->assertEquals('Start', $annotation->title);
        $this->assertNull($annotation->description);
    }

    public function testToArrayReturnsCorrectStructure(): void
    {
        $data = [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'video_id' => 'video-uuid',
            'time' => 120,
            'title' => 'Chapter 1',
            'description' => 'Introduction',
            'url' => 'https://example.com/link',
            'created_at' => '2024-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
        ];

        $annotation = AnnotationDTO::fromArray($data);
        $array = $annotation->toArray();

        $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $array['id']);
        $this->assertEquals('video-uuid', $array['video_id']);
        $this->assertEquals(120, $array['time']);
        $this->assertEquals('Chapter 1', $array['title']);
        $this->assertEquals('Introduction', $array['description']);
        $this->assertEquals('https://example.com/link', $array['url']);
    }

    public function testGetFormattedTimeReturnsMMSS(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 125,
            'title' => 'Test',
        ]);

        $this->assertEquals('2:05', $annotation->getFormattedTime());
    }

    public function testGetFormattedTimeReturnsHHMMSS(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 3665,
            'title' => 'Test',
        ]);

        $this->assertEquals('1:01:05', $annotation->getFormattedTime());
    }

    public function testGetFormattedTimeWithZero(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 0,
            'title' => 'Start',
        ]);

        $this->assertEquals('0:00', $annotation->getFormattedTime());
    }

    public function testGetEndTimeReturnsCorrectValue(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'duration' => 60,
            'title' => 'Test',
        ]);

        $this->assertEquals(180, $annotation->getEndTime());
    }

    public function testGetEndTimeReturnsNullForPointAnnotation(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'title' => 'Test',
        ]);

        $this->assertNull($annotation->getEndTime());
    }

    public function testIsRangeReturnsTrueWhenDurationSet(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'duration' => 60,
            'title' => 'Test',
        ]);

        $this->assertTrue($annotation->isRange());
    }

    public function testIsRangeReturnsFalseWhenNoDuration(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'title' => 'Test',
        ]);

        $this->assertFalse($annotation->isRange());
    }

    public function testIsPointReturnsTrueWhenNoDuration(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'title' => 'Test',
        ]);

        $this->assertTrue($annotation->isPoint());
    }

    public function testContainsTimeReturnsTrueWhenPointMatch(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'title' => 'Test',
        ]);

        $this->assertTrue($annotation->containsTime(120));
    }

    public function testContainsTimeReturnsTrueWhenInsideRange(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'duration' => 60,
            'title' => 'Test',
        ]);

        $this->assertTrue($annotation->containsTime(130));
        $this->assertTrue($annotation->containsTime(180));
    }

    public function testContainsTimeReturnsFalseWhenOutsideRange(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'duration' => 60,
            'title' => 'Test',
        ]);

        $this->assertFalse($annotation->containsTime(181));
        $this->assertFalse($annotation->containsTime(119));
    }

    public function testHasUrlReturnsTrueWhenUrlSet(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'url' => 'https://example.com',
            'title' => 'Test',
        ]);

        $this->assertTrue($annotation->hasUrl());
    }

    public function testHasUrlReturnsFalseWhenNull(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'title' => 'Test',
        ]);

        $this->assertFalse($annotation->hasUrl());
    }

    public function testGetMetadataReturnsValue(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'metadata' => [
                'chapter' => 1,
                'custom' => 'value',
            ],
            'title' => 'Test',
        ]);

        $this->assertEquals('value', $annotation->getMetadata('custom'));
        $this->assertEquals(1, $annotation->getMetadata('chapter'));
    }

    public function testGetMetadataReturnsDefaultWhenMissing(): void
    {
        $annotation = AnnotationDTO::fromArray([
            'id' => '1',
            'video_id' => 'v1',
            'time' => 120,
            'metadata' => [],
            'title' => 'Test',
        ]);

        $this->assertNull($annotation->getMetadata('missing'));
    }
}
