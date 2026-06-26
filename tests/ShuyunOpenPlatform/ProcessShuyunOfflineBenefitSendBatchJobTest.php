<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use ShuyunOpenPlatformBundle\Entities\ShuyunOfflineBenefitSendBatch;
use ShuyunOpenPlatformBundle\Jobs\ProcessShuyunOfflineBenefitSendBatchJob;
use ShuyunOpenPlatformBundle\Repositories\ShuyunOfflineBenefitSendBatchRepository;
use ShuyunOpenPlatformBundle\Services\ShuyunOfflineBenefitSendBatchProcessor;
use TestCase;

class ProcessShuyunOfflineBenefitSendBatchJobTest extends TestCase
{
    public function testHandleInvokesProcessorWithBatchPrimaryKey(): void
    {
        $batch = $this->createMock(ShuyunOfflineBenefitSendBatch::class);
        $batch->method('getId')->willReturn(99);

        $repo = $this->createMock(ShuyunOfflineBenefitSendBatchRepository::class);
        $repo->expects($this->once())->method('findOneByCompanyAndRequestId')->with(100, 'req-x')->willReturn($batch);
        $this->app->instance(ShuyunOfflineBenefitSendBatchRepository::class, $repo);

        $processor = $this->createMock(ShuyunOfflineBenefitSendBatchProcessor::class);
        $processor->expects($this->once())->method('process')->with(99);
        $this->app->instance(ShuyunOfflineBenefitSendBatchProcessor::class, $processor);

        $job = new ProcessShuyunOfflineBenefitSendBatchJob(100, 'req-x');
        $this->assertTrue($job->handle());
    }
}
