<?php

declare(strict_types=1);

namespace Kinescope\Tests\Integration\Services\Projects;

use Kinescope\Core\Pagination;
use Kinescope\DTO\Common\MetaDTO;
use Kinescope\DTO\Project\ProjectDTO;
use Kinescope\DTO\Project\ProjectListResult;
use Kinescope\Exception\NotFoundException;
use Kinescope\Services\Projects\Projects;
use Kinescope\Services\ServiceFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ProjectsService.
 *
 * These tests verify that ProjectsService works correctly
 * against the real Kinescope API using read-only operations.
 *
 * Assumes the account has at least one project (including a default project).
 *
 * @group integration
 */
class ProjectsTest extends TestCase
{
    private Projects $service;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('KINESCOPE_API_KEY');

        if ($apiKey === false || $apiKey === '') {
            $this->markTestSkipped('KINESCOPE_API_KEY environment variable not set');
        }

        $factory = ServiceFactory::fromEnvironment();
        $this->service = $factory->projects();
    }

    public function testListReturnsProjectListResult(): void
    {
        $result = $this->service->list();

        $this->assertInstanceOf(ProjectListResult::class, $result);

        $data = $result->getData();
        $this->assertNotEmpty($data);
        $this->assertContainsOnlyInstancesOf(ProjectDTO::class, $data);

        $meta = $result->getMeta();
        $this->assertInstanceOf(MetaDTO::class, $meta);
    }

    public function testListWithPagination(): void
    {
        $result = $this->service->list(pagination: new Pagination(page: 1, perPage: 1));

        $data = $result->getData();
        $this->assertLessThanOrEqual(1, count($data));

        $meta = $result->getMeta();
        $this->assertSame(1, $meta->pagination->page);
        $this->assertGreaterThanOrEqual(0, $meta->total);
    }

    public function testGetReturnsProjectDTO(): void
    {
        $list = $this->service->list();
        $firstProject = $list->getData()[0];

        $project = $this->service->get($firstProject->id);

        $this->assertInstanceOf(ProjectDTO::class, $project);
        $this->assertSame($firstProject->id, $project->id);
        $this->assertNotEmpty($project->name);
    }

    public function testGetThrowsNotFoundForInvalidId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service->get('00000000-0000-0000-0000-000000000000');
    }
}
