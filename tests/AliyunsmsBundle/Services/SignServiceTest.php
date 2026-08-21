<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\AliyunsmsBundle\Services;

use AliyunsmsBundle\Jobs\DeleteSmsSign;
use AliyunsmsBundle\Jobs\ModifySmsSign;
use AliyunsmsBundle\Repositories\SignRepository;
use AliyunsmsBundle\Services\SignService;
use Dingo\Api\Exception\ResourceException;

/** @see .tasks/plans/aliyun-sms-sign-sync.md TC-06..TC-14, TC-10..TC-12 */
class SignServiceTest extends \TestCase
{
    private function makeService(
        SignRepository $signRepository,
        ?callable $dispatchJob = null,
        ?callable $hasSceneAssociation = null,
        ?callable $hasActiveTask = null
    ): SignService {
        return new SignService(
            $signRepository,
            $dispatchJob ?? static function ($job): void {
            },
            $hasSceneAssociation ?? static fn (int $companyId, int $signId): bool => false,
            $hasActiveTask ?? static fn (int $companyId, int $signId): bool => false
        );
    }

    private function getJobParams(object $job): array
    {
        $ref = new \ReflectionClass($job);
        $prop = $ref->getProperty('params');
        $prop->setAccessible(true);

        return $prop->getValue($job);
    }

    /**
     * TC-06: status=1 modify 合法应通过；status→0；Job SignName 来自 DB。
     */
    public function testTc06StatusOneModifySucceedsAndDispatchesJobWithDbSignName(): void
    {
        // #given
        $companyId = 100;
        $signId = 1;
        $dbSignName = 'DbSignName';
        $dispatchedJob = null;

        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->with(['id' => $signId, 'company_id' => $companyId])
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => $dbSignName,
                'status' => 1,
            ]);
        $signRepository->expects($this->once())
            ->method('updateOneBy')
            ->with(
                ['company_id' => $companyId, 'id' => $signId],
                $this->callback(static function (array $payload): bool {
                    return $payload['status'] === 0
                        && $payload['qualification_id'] === 'q1';
                })
            );

        $service = $this->makeService(
            $signRepository,
            static function ($job) use (&$dispatchedJob): void {
                $dispatchedJob = $job;
            }
        );

        $params = [
            'id' => $signId,
            'company_id' => $companyId,
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => true,
            'qualification_id' => 'q1',
        ];

        // #when
        $result = $service->modifySign($params);

        // #then
        $this->assertTrue($result);
        $this->assertInstanceOf(ModifySmsSign::class, $dispatchedJob);
        $jobParams = $this->getJobParams($dispatchedJob);
        $this->assertSame($dbSignName, $jobParams['sign_name']);
        $this->assertSame('q1', $jobParams['qualification_id']);
    }

    /**
     * TC-07: status=2 modify 合法应通过。
     */
    public function testTc07StatusTwoModifySucceeds(): void
    {
        // #given
        $companyId = 100;
        $signId = 2;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->with(['id' => $signId, 'company_id' => $companyId])
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'FailedSign',
                'status' => 2,
            ]);
        $signRepository->expects($this->once())->method('updateOneBy');

        $service = $this->makeService($signRepository);

        // #when
        $result = $service->modifySign([
            'id' => $signId,
            'company_id' => $companyId,
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => false,
            'qualification_id' => 'q2',
        ]);

        // #then
        $this->assertTrue($result);
    }

    /**
     * TC-08: status=0 modify 应拒绝。
     */
    public function testTc08StatusZeroModifyThrowsResourceException(): void
    {
        // #given
        $companyId = 100;
        $signId = 3;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'PendingSign',
                'status' => 0,
            ]);
        $signRepository->expects($this->never())->method('updateOneBy');
        $service = $this->makeService($signRepository);

        // #when #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('审核中的签名不可修改');
        $service->modifySign([
            'id' => $signId,
            'company_id' => $companyId,
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => true,
            'qualification_id' => 'q1',
        ]);
    }

    /**
     * TC-09: modify 改 sign_name 应拒绝。
     */
    public function testTc09ModifyWithDifferentSignNameThrowsResourceException(): void
    {
        // #given
        $companyId = 100;
        $signId = 4;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'OriginalSign',
                'status' => 1,
            ]);
        $signRepository->expects($this->never())->method('updateOneBy');
        $service = $this->makeService($signRepository);

        // #when #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('请在阿里云控制台改名');
        $service->modifySign([
            'id' => $signId,
            'company_id' => $companyId,
            'sign_name' => 'RenamedSign',
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => true,
            'qualification_id' => 'q1',
        ]);
    }

    /**
     * TC-10: status=0 手动 delete 应拒绝。
     */
    public function testTc10StatusZeroDeleteThrowsResourceException(): void
    {
        // #given
        $companyId = 100;
        $signId = 5;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->with(['company_id' => $companyId, 'id' => $signId])
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'PendingSign',
                'status' => 0,
            ]);
        $signRepository->expects($this->never())->method('deleteById');
        $service = $this->makeService($signRepository);

        // #when #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('不支持删除正在审核中的签名');
        $service->deleteSign(['company_id' => $companyId, 'id' => $signId]);
    }

    /**
     * TC-11: status=1 delete 无关联应成功。
     */
    public function testTc11StatusOneDeleteWithoutAssociationSucceeds(): void
    {
        // #given
        $companyId = 100;
        $signId = 6;
        $dispatchedJob = null;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'PassSign',
                'status' => 1,
            ]);
        $signRepository->expects($this->once())->method('deleteById')->with($signId);
        $service = $this->makeService(
            $signRepository,
            static function ($job) use (&$dispatchedJob): void {
                $dispatchedJob = $job;
            }
        );

        // #when
        $result = $service->deleteSign(['company_id' => $companyId, 'id' => $signId]);

        // #then
        $this->assertTrue($result);
        $this->assertInstanceOf(DeleteSmsSign::class, $dispatchedJob);
    }

    /**
     * TC-12: delete 有关联 scene 应拒绝且 message 含「签名」。
     */
    public function testTc12DeleteWithSceneAssociationRejectsWithSignMessage(): void
    {
        // #given
        $companyId = 100;
        $signId = 7;
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')
            ->willReturn([
                'id' => $signId,
                'company_id' => $companyId,
                'sign_name' => 'LinkedSign',
                'status' => 1,
            ]);
        $signRepository->expects($this->never())->method('deleteById');
        $service = $this->makeService(
            $signRepository,
            null,
            static fn (int $cid, int $sid): bool => true
        );

        // #when #then
        try {
            $service->deleteSign(['company_id' => $companyId, 'id' => $signId]);
            $this->fail('Expected ResourceException was not thrown');
        } catch (ResourceException $e) {
            $this->assertStringContainsString('签名', $e->getMessage());
            $this->assertStringNotContainsString('模板', $e->getMessage());
        }
    }

    /**
     * TC-13: getInfo 必须按 id + company_id 过滤，跨租户查不到。
     */
    public function testTc13GetInfoWithWrongCompanyIdReturnsNull(): void
    {
        // #given
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->expects($this->once())
            ->method('getInfo')
            ->with(['id' => 1, 'company_id' => 100])
            ->willReturn(null);
        $service = $this->makeService($signRepository);

        // #when
        $result = $service->getInfo(['id' => 1, 'company_id' => 100]);

        // #then
        $this->assertNull($result);
    }

    /**
     * TC-14: modify/delete 跨租户 id 应失败。
     */
    public function testTc14CrossTenantModifyAndDeleteFail(): void
    {
        // #given
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')->willReturn([]);
        $signRepository->expects($this->never())->method('updateOneBy');
        $signRepository->expects($this->never())->method('deleteById');
        $service = $this->makeService($signRepository);

        // #when #then modify
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('签名不存在');
        $service->modifySign([
            'id' => 99,
            'company_id' => 100,
            'sign_source' => 1,
            'remark' => 'remark',
            'third_party' => true,
            'qualification_id' => 'q1',
        ]);
    }

    public function testTc14CrossTenantDeleteFails(): void
    {
        // #given
        $signRepository = $this->createMock(SignRepository::class);
        $signRepository->method('getInfo')->willReturn([]);
        $signRepository->expects($this->never())->method('deleteById');
        $service = $this->makeService($signRepository);

        // #when #then
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('签名不存在');
        $service->deleteSign(['company_id' => 100, 'id' => 99]);
    }
}
