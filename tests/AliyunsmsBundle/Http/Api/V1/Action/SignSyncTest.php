<?php
/**
 * Copyright 2019-2026 ShopeX
 */

declare(strict_types=1);

namespace Tests\AliyunsmsBundle\Http\Api\V1\Action;

use AliyunsmsBundle\Http\Api\V1\Action\Sign;
use AliyunsmsBundle\Jobs\SyncSmsSigns;
use Dingo\Api\Http\Response\Factory;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Mockery;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-20 */
class SignSyncTest extends \TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function bindAuthWithCompanyId(int $companyId): void
    {
        $user = Mockery::mock();
        $user->shouldReceive('get')->with('company_id')->andReturn($companyId);
        $authGuard = Mockery::mock();
        $authGuard->shouldReceive('user')->once()->andReturn($user);
        app()->instance('auth', $authGuard);
    }

    /**
     * TC-20: POST sync 受理应返回「已提交」并 dispatch SyncSmsSigns。
     */
    public function testTc20PostSyncAcceptanceDispatchesJobAndReturnsSubmittedMessage(): void
    {
        // #given
        $companyId = 100;
        $this->bindAuthWithCompanyId($companyId);

        $dispatchedJob = null;
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function ($job) use (&$dispatchedJob) {
                $dispatchedJob = $job;

                return $job;
            });
        $this->app->instance(Dispatcher::class, $dispatcher);

        $factory = Mockery::mock(Factory::class);
        $factory->shouldReceive('array')->once()->with([
            'status' => true,
            'message' => '同步任务已提交',
        ])->andReturnUsing(static fn (array $payload): array => $payload);
        app()->instance(Factory::class, $factory);

        // #when
        $controller = new Sign();
        $request = Request::create('/aliyunsms/sign/sync', 'POST');
        $result = $controller->syncSign($request);

        // #then
        $this->assertSame(true, $result['status']);
        $this->assertSame('同步任务已提交', $result['message']);
        $this->assertArrayNotHasKey('created', $result);
        $this->assertArrayNotHasKey('updated', $result);
        $this->assertArrayNotHasKey('deleted', $result);
        $this->assertInstanceOf(SyncSmsSigns::class, $dispatchedJob);
        $this->assertSame('sms', $dispatchedJob->queue);

        $reflection = new \ReflectionClass($dispatchedJob);
        $property = $reflection->getProperty('companyId');
        $property->setAccessible(true);
        $this->assertSame($companyId, $property->getValue($dispatchedJob));
    }
}
