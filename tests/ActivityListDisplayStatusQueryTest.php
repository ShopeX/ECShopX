<?php

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Support\ActivityListDisplayStatusQuery;
use PHPUnit\Framework\TestCase;

final class ActivityListDisplayStatusQueryTest extends TestCase
{
    public function testNullAndEmptyReturnNull(): void
    {
        $this->assertNull(ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull(null));
        $this->assertNull(ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull(''));
    }

    public function testCommaSeparated(): void
    {
        $this->assertSame(
            ['warm_up', 'ongoing'],
            ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull('warm_up,ongoing')
        );
    }

    public function testRepeatedArrayChunks(): void
    {
        $this->assertSame(
            ['warm_up', 'ongoing'],
            ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull(['warm_up', 'ongoing'])
        );
        $this->assertSame(
            ['warm_up', 'ongoing'],
            ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull(['warm_up,ongoing', 'ongoing'])
        );
    }

    public function testInvalidSlugThrows(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('status 参数不合法');
        ActivityListDisplayStatusQuery::statusSlugsForFilterOrNull('warm_up,invalid');
    }
}
