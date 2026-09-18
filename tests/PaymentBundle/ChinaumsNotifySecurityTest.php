<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use ChinaumsPayBundle\Services\ClientAPIs\UmsClient;
use Illuminate\Http\Request;
use Mockery;
use PaymentBundle\Http\Controllers\ChinaumsNotify;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Support\ChinaumsNotifyValidator;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * chinaums-notify-logic-vuln：银联商务回调安全测试（TC-CU-01 ~ TC-CU-13）
 */
class ChinaumsNotifySecurityTest extends \TestCase
{
    /**
     * TC-CU-01 / AC-01：Md5Key 未配置时 genSign 不得产出有效验签。
     * #given UMS_md5_KEY 为 null
     * #when 调用 UmsClient::genSign
     * #then 抛 InvalidArgumentException，fail-closed
     */
    public function testTcCu01GenSignRejectsNullMd5Key(): void
    {
        #given
        config(['ums.Md5Key' => null]);
        $client = new UmsClient();

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        $client->genSign(['status' => 'TRADE_SUCCESS', 'merOrderId' => '32C2test']);
    }

    /**
     * TC-CU-01 / AC-01：Md5Key 为空字符串时 genSign 不得产出有效验签。
     * #given UMS_md5_KEY 为 ''
     * #when 调用 UmsClient::genSign
     * #then 抛 InvalidArgumentException，fail-closed
     */
    public function testTcCu01GenSignRejectsEmptyMd5Key(): void
    {
        #given
        config(['ums.Md5Key' => '']);
        $client = new UmsClient();

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        $client->genSign(['status' => 'TRADE_SUCCESS', 'merOrderId' => '32C2test']);
    }

    /**
     * TC-CU-01 / AC-01：Md5Key 为纯空格时 genSign 不得产出有效验签。
     * #given UMS_md5_KEY 为 '   '
     * #when 调用 UmsClient::genSign
     * #then 抛 InvalidArgumentException，fail-closed
     */
    public function testTcCu01GenSignRejectsWhitespaceMd5Key(): void
    {
        #given
        config(['ums.Md5Key' => '   ']);
        $client = new UmsClient();

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        $client->genSign(['status' => 'TRADE_SUCCESS', 'merOrderId' => '32C2test']);
    }

    /**
     * TC-CU-02 / AC-02：verify 使用 hash_equals 常量时间比较。
     * #given ChinaumsPayService::verify 源码
     * #when 检查签名校验实现
     * #then 使用 hash_equals，不得仅用 != 或 == 验签
     */
    public function testTcCu02VerifyUsesHashEquals(): void
    {
        #given
        $body = $this->methodBody(ChinaumsPayService::class, 'verify');

        #when / #then
        $this->assertStringContainsString('hash_equals', $body);
        $this->assertDoesNotMatchRegularExpression(
            '/\$gensign\s*!=\s*\$sign/',
            $body,
            'verify must not use loose != for signature comparison'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\$gensign\s*==\s*\$sign/',
            $body,
            'verify must not use == for signature comparison'
        );
    }

    /**
     * TC-CU-03 / AC-02：错误 sign 被拒绝。
     * #given 合法回调参数与 Md5Key
     * #when 篡改 status 后保留原 sign 调用 verify
     * #then 抛 BadRequestHttpException
     */
    public function testTcCu03VerifyRejectsTamperedPayloadWithWrongSign(): void
    {
        #given
        config(['ums.Md5Key' => 'test-md5-key-for-unit-test']);
        $client = new UmsClient();
        $payload = [
            'status' => 'TRADE_SUCCESS',
            'merOrderId' => '32C2trade001',
            'totalAmount' => '100',
            'targetOrderId' => 'UMS123456',
        ];
        $payload['sign'] = $client->genSign($payload);
        $service = new ChinaumsPayService();

        #when tamper single field without updating sign
        $payload['status'] = 'TRADE_CLOSED';

        #then
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('验签失败');
        $service->verify($payload);
    }

    /**
     * TC-CU-04 / AC-03：回调金额比 trade.pay_fee 少 1 分。
     * #given trade.pay_fee=100，回调 totalAmount=99
     * #when 调用 assertNotifyAmountMatchesTrade
     * #then 抛异常，拒绝更新
     */
    public function testTcCu04RejectsAmountOneFenLessThanPayFee(): void
    {
        #given
        $payload = ['totalAmount' => '99'];
        $tradeInfo = ['pay_fee' => 100];

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        ChinaumsNotifyValidator::assertNotifyAmountMatchesTrade($payload, $tradeInfo);
    }

    /**
     * TC-CU-05 / AC-03：回调缺少金额字段。
     * #given payload 无 totalAmount/totalAmt
     * #when 调用 assertNotifyAmountMatchesTrade
     * #then 抛异常，拒绝更新
     */
    public function testTcCu05RejectsMissingAmountField(): void
    {
        #given
        $payload = ['status' => 'TRADE_SUCCESS'];
        $tradeInfo = ['pay_fee' => 100];

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        ChinaumsNotifyValidator::assertNotifyAmountMatchesTrade($payload, $tradeInfo);
    }

    /**
     * TC-CU-06 / AC-04：merOrderId 错误前缀（非 32C2）。
     * #given merOrderId 以 XXXX 开头
     * #when 调用 extractTradeIdFromMerOrderId
     * #then 抛异常，拒绝更新
     */
    public function testTcCu06RejectsWrongMerOrderIdPrefix(): void
    {
        #given
        config(['ums.pre' => '32C2']);

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        ChinaumsNotifyValidator::extractTradeIdFromMerOrderId('XXXXtrade001');
    }

    /**
     * TC-CU-07 / AC-05：merOrderId 合法前缀但 trade_id 不存在。
     * #given extract 得到 trade_id，但 tradeInfo 为空
     * #when 调用 assertTradeExists
     * #then 抛异常，拒绝更新
     */
    public function testTcCu07RejectsNonExistentTrade(): void
    {
        #given
        config(['ums.pre' => '32C2']);
        $tradeId = ChinaumsNotifyValidator::extractTradeIdFromMerOrderId('32C2nonexistent001');
        $this->assertSame('nonexistent001', $tradeId);

        #when / #then
        $this->expectException(\InvalidArgumentException::class);
        ChinaumsNotifyValidator::assertTradeExists([]);
    }

    /**
     * TC-CU-09 / AC-07：status=TRADE_CLOSED 不触发 SUCCESS 更新。
     * #given 网关 status=TRADE_CLOSED，trade 未支付
     * #when 调用 shouldUpdateTradeToSuccess
     * #then 返回 false，不触发 updateStatus SUCCESS
     */
    public function testTcCu09TradeClosedDoesNotTriggerSuccessUpdate(): void
    {
        #given
        $tradeInfo = ['trade_state' => 'NOTPAY', 'pay_fee' => 100];

        #when
        $shouldUpdate = ChinaumsNotifyValidator::shouldUpdateTradeToSuccess('TRADE_CLOSED', $tradeInfo);

        #then
        $this->assertFalse($shouldUpdate);
    }

    /**
     * TC-CU-11 / AC-09：trade_state 已 SUCCESS 时重复 notify 幂等。
     * #given 网关 status=TRADE_SUCCESS，trade 已为 SUCCESS
     * #when 调用 shouldUpdateTradeToSuccess
     * #then 返回 false，不重复 updateStatus
     */
    public function testTcCu11AlreadySuccessIsIdempotent(): void
    {
        #given
        $tradeInfo = ['trade_state' => 'SUCCESS', 'pay_fee' => 100];

        #when
        $shouldUpdate = ChinaumsNotifyValidator::shouldUpdateTradeToSuccess('TRADE_SUCCESS', $tradeInfo);

        #then
        $this->assertFalse($shouldUpdate);
    }

    /**
     * TC-CU-08 / AC-06：合法 happy path → updateStatus SUCCESS，返回 SUCCESS。
     * #given Md5Key 已配置、sign 正确、TRADE_SUCCESS、金额一致
     * #when 调用 ChinaumsNotify::handle
     * #then updateStatus(SUCCESS) 且返回 SUCCESS
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    public function testTcCu08HappyPathUpdatesTradeToSuccess(): void
    {
        #given
        config(['ums.Md5Key' => 'test-md5-key-for-unit-test', 'ums.pre' => '32C2']);
        $tradeId = 'trade001';
        $merOrderId = '32C2' . $tradeId;
        $payload = [
            'status' => 'TRADE_SUCCESS',
            'merOrderId' => $merOrderId,
            'totalAmount' => '100',
            'targetOrderId' => 'UMS123456',
        ];
        $client = new UmsClient();
        $payload['sign'] = $client->genSign($payload);
        $tradeInfo = [
            'trade_id' => $tradeId,
            'pay_fee' => 100,
            'trade_state' => 'NOTPAY',
            'company_id' => 1,
        ];

        $tradeMock = Mockery::mock('overload:OrdersBundle\Services\TradeService');
        $tradeMock->shouldReceive('getInfo')
            ->once()
            ->with(['trade_id' => $tradeId])
            ->andReturn($tradeInfo);
        $tradeMock->shouldReceive('updateStatus')
            ->once()
            ->with(
                $tradeId,
                'SUCCESS',
                Mockery::on(function (array $options): bool {
                    return ($options['pay_type'] ?? '') === 'chinaums'
                        && ($options['transaction_id'] ?? '') === 'UMS123456';
                })
            );

        $request = Request::create('/api/chinaums/notify', 'POST', $payload);

        #when
        $controller = new ChinaumsNotify();
        $result = $controller->handle($request);

        #then
        $this->assertSame('SUCCESS', $result);
    }

    /**
     * TC-CU-10 / AC-08：TRADE_REFUND early return，不更新支付状态。
     * #given status=TRADE_REFUND，合法 sign 与 trade
     * #when 调用 ChinaumsNotify::handle
     * #then 返回 SUCCESS，不调用 updateStatus
     *
     * @runTestsInSeparateProcesses
     * @preserveGlobalState disabled
     */
    public function testTcCu10TradeRefundEarlyReturnWithoutUpdate(): void
    {
        #given
        config(['ums.Md5Key' => 'test-md5-key-for-unit-test', 'ums.pre' => '32C2']);
        $tradeId = 'trade002';
        $merOrderId = '32C2' . $tradeId;
        $payload = [
            'status' => 'TRADE_REFUND',
            'merOrderId' => $merOrderId,
            'totalAmount' => '100',
            'targetOrderId' => 'UMS654321',
        ];
        $client = new UmsClient();
        $payload['sign'] = $client->genSign($payload);
        $tradeInfo = [
            'trade_id' => $tradeId,
            'pay_fee' => 100,
            'trade_state' => 'SUCCESS',
            'company_id' => 1,
        ];

        $tradeMock = Mockery::mock('overload:OrdersBundle\Services\TradeService');
        $tradeMock->shouldReceive('getInfo')
            ->once()
            ->with(['trade_id' => $tradeId])
            ->andReturn($tradeInfo);
        $tradeMock->shouldReceive('updateStatus')->never();

        $request = Request::create('/api/chinaums/notify', 'POST', $payload);

        #when
        $controller = new ChinaumsNotify();
        $result = $controller->handle($request);

        #then
        $this->assertSame('SUCCESS', $result);
    }

    /**
     * TC-CU-12 / AC-10：handle 先 load trade 再 verify，最后 updateStatus。
     * #given ChinaumsNotify::handle 源码
     * #when 检查 getInfo、verify、updateStatus 出现顺序
     * #then getInfo 在 verify 与 updateStatus 之前
     */
    public function testTcCu12LoadsTradeBeforeVerifyAndUpdateStatus(): void
    {
        #given
        $body = $this->methodBody(ChinaumsNotify::class, 'handle');

        #when
        $getInfoPos = strpos($body, 'getInfo([');
        $verifyPos = strpos($body, '->verify(');
        $updateStatusPos = strpos($body, 'updateStatus(');

        #then
        $this->assertNotFalse($getInfoPos, 'handle must load trade via getInfo');
        $this->assertNotFalse($verifyPos, 'handle must verify signature');
        $this->assertNotFalse($updateStatusPos, 'handle must call updateStatus on success path');
        $this->assertLessThan($verifyPos, $getInfoPos, 'getInfo must appear before verify');
        $this->assertLessThan($updateStatusPos, $verifyPos, 'verify must appear before updateStatus');
    }

    /**
     * TC-CU-13 / AC-11：.env.full 含 UMS_md5_KEY 占位与说明。
     * #given 项目根目录 .env.full
     * #when 检查银联回调验签密钥配置项
     * #then 可见 UMS_md5_KEY 占位及必填说明
     */
    public function testTcCu13EnvFullDocumentsUmsMd5Key(): void
    {
        #given
        $envFullPath = dirname(__DIR__, 2) . '/.env.full';
        $this->assertFileExists($envFullPath);
        $contents = file_get_contents($envFullPath);
        $this->assertIsString($contents);

        #when / #then
        $this->assertStringContainsString(
            '# 银联商务回调验签密钥（必填，否则回调拒绝）',
            $contents,
            '.env.full must document that UMS_md5_KEY is required for notify verification'
        );
        $this->assertMatchesRegularExpression(
            '/^UMS_md5_KEY=/m',
            $contents,
            '.env.full must include UMS_md5_KEY placeholder'
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }
}
