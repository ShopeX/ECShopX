<?php

declare(strict_types=1);

namespace Tests\AftersalesBundle;

use AftersalesBundle\Services\AftersalesRefundService;
use Dingo\Api\Exception\ResourceException;

/**
 * ECX-11406：异常退款实退 refundAbnormalTrade。
 */
class AftersalesRefundServiceRefundAbnormalTradeTest extends \TestCase
{
    private array $baseRefund = [
        'company_id' => '1',
        'refund_bn' => '2202511250001',
        'order_id' => '5412622000140002',
        'trade_id' => '5412622000130002',
        'refund_fee' => 8800,
        'refund_point' => 0,
        'pay_type' => 'wxpayh5',
        'distributor_id' => 0,
    ];

    private array $tradeInfo = [
        'trade_id' => '5412622000130002',
        'pay_fee' => 8800,
        'wxaAppid' => '',
    ];

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testSuccessAfterCasUpdatesRefundToSuccess(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'PAY_AFTER_CANCEL']);
        $repo = $this->mockRepo([
            ['getInfo', $refund],
            ['claimAbnormalRefundProcessing', 1],
            ['updateOneBy', [
                'refund_id' => 'RF123',
                'refund_status' => 'SUCCESS',
                'refunded_fee' => 8800,
                'refunded_point' => 0,
            ]],
        ]);

        $service = $this->makeService($repo, ['status' => 'SUCCESS', 'refund_id' => 'RF123']);
        $result = $service->refundAbnormalTrade('1', '2202511250001');

        $this->assertSame('SUCCESS', $result['refund_status']);
        $this->assertSame('RF123', $result['refund_id']);
    }

    public function testChannelFailRestoresPriorAbnormalStatus(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'DUP_PAY']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')->once()->andReturn($refund);
        $repo->shouldReceive('claimAbnormalRefundProcessing')
            ->once()
            ->with('1', '2202511250001', 'DUP_PAY')
            ->andReturn(1);
        $repo->shouldReceive('updateOneBy')
            ->once()
            ->with(
                ['company_id' => '1', 'refund_bn' => '2202511250001'],
                ['refund_status' => 'DUP_PAY']
            );

        $service = $this->makeService($repo, ['status' => 'FAIL', 'error_desc' => '渠道异常']);

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('渠道异常');
        $service->refundAbnormalTrade('1', '2202511250001');
    }

    public function testAlreadySuccessIsIdempotentWithoutClaim(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'SUCCESS', 'refund_id' => 'RF-done']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')->once()->andReturn($refund);
        $repo->shouldNotReceive('claimAbnormalRefundProcessing');
        $repo->shouldNotReceive('updateOneBy');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $result = $service->refundAbnormalTrade('1', '2202511250001');
        $this->assertSame('SUCCESS', $result['refund_status']);
        $this->assertSame('RF-done', $result['refund_id']);
    }

    public function testProcessingRejectsReentry(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'PROCESSING']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')->once()->andReturn($refund);
        $repo->shouldNotReceive('claimAbnormalRefundProcessing');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('退款处理中');
        $service->refundAbnormalTrade('1', '2202511250001');
    }

    public function testCasClaimZeroThenReReadProcessingRejectsConcurrent(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'PAY_AFTER_CANCEL']);
        $processing = array_merge($this->baseRefund, ['refund_status' => 'PROCESSING']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')->twice()->andReturn($refund, $processing);
        $repo->shouldReceive('claimAbnormalRefundProcessing')
            ->once()
            ->with('1', '2202511250001', 'PAY_AFTER_CANCEL')
            ->andReturn(0);
        $repo->shouldNotReceive('updateOneBy');

        $service = new AftersalesRefundService();
        $service->aftersalesRefundRepository = $repo;

        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('退款处理中');
        $service->refundAbnormalTrade('1', '2202511250001');
    }

    public function testReEnterAfterFailRestoresThenSucceeds(): void
    {
        $refund = array_merge($this->baseRefund, ['refund_status' => 'PAY_AFTER_CANCEL']);
        $repo = \Mockery::mock();
        $repo->shouldReceive('getInfo')->twice()->andReturn($refund, $refund);
        $repo->shouldReceive('claimAbnormalRefundProcessing')
            ->twice()
            ->with('1', '2202511250001', 'PAY_AFTER_CANCEL')
            ->andReturn(1, 1);
        $repo->shouldReceive('updateOneBy')
            ->once()
            ->with(
                ['company_id' => '1', 'refund_bn' => '2202511250001'],
                ['refund_status' => 'PAY_AFTER_CANCEL']
            );
        $repo->shouldReceive('updateOneBy')
            ->once()
            ->with(
                ['company_id' => '1', 'refund_bn' => '2202511250001'],
                \Mockery::on(function (array $data): bool {
                    $this->assertSame('SUCCESS', $data['refund_status']);
                    $this->assertSame(8800, $data['refunded_fee']);

                    return true;
                })
            )
            ->andReturn(array_merge($this->baseRefund, [
                'refund_id' => 'RF456',
                'refund_status' => 'SUCCESS',
                'refunded_fee' => 8800,
                'refunded_point' => 0,
            ]));

        $service = $this->makeService($repo, ['status' => 'FAIL', 'error_desc' => '渠道异常']);
        $service->channelResults = [
            ['status' => 'FAIL', 'error_desc' => '渠道异常'],
            ['status' => 'SUCCESS', 'refund_id' => 'RF456'],
        ];

        try {
            $service->refundAbnormalTrade('1', '2202511250001');
            $this->fail('Expected ResourceException on first call');
        } catch (ResourceException $e) {
            $this->assertSame('渠道异常', $e->getMessage());
        }

        $result = $service->refundAbnormalTrade('1', '2202511250001');
        $this->assertSame('SUCCESS', $result['refund_status']);
        $this->assertSame('RF456', $result['refund_id']);
    }

    private function mockRepo(array $steps): \Mockery\MockInterface
    {
        $repo = \Mockery::mock();
        foreach ($steps as [$method, $arg]) {
            if ($method === 'getInfo') {
                $repo->shouldReceive('getInfo')->andReturn($arg);
            } elseif ($method === 'claimAbnormalRefundProcessing') {
                $repo->shouldReceive('claimAbnormalRefundProcessing')->andReturn($arg);
            } elseif ($method === 'updateOneBy') {
                $repo->shouldReceive('updateOneBy')
                    ->once()
                    ->with(
                        ['company_id' => '1', 'refund_bn' => '2202511250001'],
                        \Mockery::on(function (array $data): bool {
                            $this->assertSame('SUCCESS', $data['refund_status']);
                            $this->assertSame(8800, $data['refunded_fee']);

                            return true;
                        })
                    )
                    ->andReturn(array_merge($this->baseRefund, $arg));
            }
        }

        return $repo;
    }

    private function makeService(\Mockery\MockInterface $repo, array $channelResult): AftersalesRefundServiceRefundAbnormalTradeTestable
    {
        $service = new AftersalesRefundServiceRefundAbnormalTradeTestable();
        $service->aftersalesRefundRepository = $repo;
        $service->channelResult = $channelResult;
        $service->tradeInfo = $this->tradeInfo;

        return $service;
    }
}

class AftersalesRefundServiceRefundAbnormalTradeTestable extends AftersalesRefundService
{
    public array $channelResult = [];

    /** @var list<array<string, mixed>> */
    public array $channelResults = [];

    public array $tradeInfo = [];

    protected function invokePaymentChannelRefund($companyId, array $tradeInfo, array $refundData, bool $resubmit = false): array
    {
        if ($this->channelResults !== []) {
            return array_shift($this->channelResults);
        }

        return $this->channelResult;
    }

    protected function getSuccessTradeForAbnormalRefund($companyId, string $tradeId): array
    {
        return $this->tradeInfo;
    }
}