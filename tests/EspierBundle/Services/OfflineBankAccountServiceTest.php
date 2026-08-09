<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace Tests\EspierBundle\Services;

use EspierBundle\Services\OfflineBankAccountService;
use PHPUnit\Framework\TestCase;

class OfflineBankAccountServiceTest extends TestCase
{
    private function makeServiceWithRepository($repository): OfflineBankAccountService
    {
        $service = (new \ReflectionClass(OfflineBankAccountService::class))->newInstanceWithoutConstructor();
        $service->offlineBankAccountRepository = $repository;

        return $service;
    }

    /**
     * TC-01 / A1：createData is_default=1，公司无默认 → 不抛错，create 被调用，清默认 updateBy 不调用
     * #given 公司下无任何 is_default=1 的账户
     * #when 调用 createData(['company_id' => 1, 'is_default' => 1, ...])
     * #then findBy 查询默认账户返回空；清默认 updateBy 不被调用；create 被调用一次
     */
    public function testCreateDataWithDefaultWhenNoExistingDefaultDoesNotClear(): void
    {
        $params = [
            'company_id' => 1,
            'is_default' => 1,
            'bank_name' => 'Test Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy', 'create'])
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['company_id' => 1, 'is_default' => 1])
            ->willReturn([]);

        $mockRepo->expects($this->never())
            ->method('updateBy');

        $mockRepo->expects($this->once())
            ->method('create')
            ->with($params)
            ->willReturn(['id' => 100]);

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->createData($params);

        $this->assertSame(['id' => 100], $result);
    }

    /**
     * TC-02 / A2：createData is_default=1，已有默认 → 清默认 updateBy 一次再 create
     * #given 公司下已有 is_default=1 的账户
     * #when 调用 createData(['company_id' => 1, 'is_default' => 1, ...])
     * #then 先 updateBy 清默认，再 create
     */
    public function testCreateDataWithDefaultWhenExistingDefaultClearsThenCreates(): void
    {
        $params = [
            'company_id' => 1,
            'is_default' => 1,
            'bank_name' => 'New Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy', 'create'])
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['company_id' => 1, 'is_default' => 1])
            ->willReturn([['id' => 10, 'is_default' => 1]]);

        $mockRepo->expects($this->once())
            ->method('updateBy')
            ->with(
                ['company_id' => 1, 'is_default' => 1],
                ['is_default' => 0]
            )
            ->willReturn([['id' => 10, 'is_default' => 0]]);

        $mockRepo->expects($this->once())
            ->method('create')
            ->with($params)
            ->willReturn(['id' => 101]);

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->createData($params);

        $this->assertSame(['id' => 101], $result);
    }

    /**
     * TC-03 / A3：createData is_default=0 → 不清默认，直接 create
     * #given 任意公司状态
     * #when 调用 createData(['company_id' => 1, 'is_default' => 0, ...])
     * #then findBy / 清默认 updateBy 均不被调用；直接 create
     */
    public function testCreateDataWithNonDefaultSkipsClear(): void
    {
        $params = [
            'company_id' => 1,
            'is_default' => 0,
            'bank_name' => 'Normal Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy', 'create'])
            ->getMock();

        $mockRepo->expects($this->never())
            ->method('findBy');

        $mockRepo->expects($this->never())
            ->method('updateBy');

        $mockRepo->expects($this->once())
            ->method('create')
            ->with($params)
            ->willReturn(['id' => 102]);

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->createData($params);

        $this->assertSame(['id' => 102], $result);
    }

    /**
     * TC-04 / A4：update is_default=1，无默认 → 不抛错，目标 updateBy 执行
     * #given 公司下无任何 is_default=1 的账户
     * #when 调用 update(['id' => 5], ['company_id' => 1, 'is_default' => 1, ...])
     * #then 清默认 updateBy 不被调用；目标 updateBy($filter, $params) 正常执行
     */
    public function testUpdateWithDefaultWhenNoExistingDefaultDoesNotClear(): void
    {
        $filter = ['id' => 5];
        $params = [
            'company_id' => 1,
            'is_default' => 1,
            'bank_name' => 'Updated Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy'])
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['company_id' => 1, 'is_default' => 1])
            ->willReturn([]);

        $mockRepo->expects($this->once())
            ->method('updateBy')
            ->with($filter, $params)
            ->willReturn([['id' => 5, 'is_default' => 1]]);

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->update($filter, $params);

        $this->assertSame([['id' => 5, 'is_default' => 1]], $result);
    }

    /**
     * TC-05 / A5：update is_default=1，另有默认 → 先清再更新目标
     * #given 公司下另有 is_default=1 的账户
     * #when 调用 update(['id' => 5], ['company_id' => 1, 'is_default' => 1, ...])
     * #then 先 updateBy 清默认，再 updateBy 更新目标记录
     */
    public function testUpdateWithDefaultWhenExistingDefaultClearsThenUpdates(): void
    {
        $filter = ['id' => 5];
        $params = [
            'company_id' => 1,
            'is_default' => 1,
            'bank_name' => 'Updated Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy'])
            ->getMock();

        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['company_id' => 1, 'is_default' => 1])
            ->willReturn([['id' => 10, 'is_default' => 1]]);

        $mockRepo->expects($this->exactly(2))
            ->method('updateBy')
            ->withConsecutive(
                [
                    ['company_id' => 1, 'is_default' => 1],
                    ['is_default' => 0],
                ],
                [$filter, $params]
            )
            ->willReturnOnConsecutiveCalls(
                [['id' => 10, 'is_default' => 0]],
                [['id' => 5, 'is_default' => 1]]
            );

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->update($filter, $params);

        $this->assertSame([['id' => 5, 'is_default' => 1]], $result);
    }

    /**
     * TC-06 / A6：update is_default=0 → 不清默认
     * #given 任意公司状态
     * #when 调用 update(['id' => 5], ['company_id' => 1, 'is_default' => 0, ...])
     * #then findBy / 清默认 updateBy 均不被调用；仅目标 updateBy 执行
     */
    public function testUpdateWithNonDefaultSkipsClear(): void
    {
        $filter = ['id' => 5];
        $params = [
            'company_id' => 1,
            'is_default' => 0,
            'bank_name' => 'Normal Bank',
        ];

        $mockRepo = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['findBy', 'updateBy'])
            ->getMock();

        $mockRepo->expects($this->never())
            ->method('findBy');

        $mockRepo->expects($this->once())
            ->method('updateBy')
            ->with($filter, $params)
            ->willReturn([['id' => 5, 'is_default' => 0]]);

        $service = $this->makeServiceWithRepository($mockRepo);
        $result = $service->update($filter, $params);

        $this->assertSame([['id' => 5, 'is_default' => 0]], $result);
    }
}
