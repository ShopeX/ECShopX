<?php

declare(strict_types=1);

namespace Tests\PaymentBundle;

use PaymentBundle\Services\PaymentDoumenIntlMutualExclusionService;

class PaymentDoumenIntlMutualExclusionServiceTest extends \TestCase
{
    private const COMPANY_ID = 1001;

    /** @var array<string, string> */
    private array $bucket = [];

    /** @var list<array{0: string, 1: string}> */
    private array $setCalls = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bucket = [];
        $this->setCalls = [];

        $redis = \Mockery::mock();
        $redis->shouldReceive('get')->andReturnUsing(function (string $key) {
            return $this->bucket[$key] ?? false;
        });
        $redis->shouldReceive('set')->andReturnUsing(function (string $key, string $value) {
            $this->setCalls[] = [$key, $value];
            $this->bucket[$key] = $value;

            return true;
        });
        $this->app->instance('redis', $redis);
        $this->app->instance('request', \Illuminate\Http\Request::create('/', 'GET', ['country_code' => 'zh-CN']));
    }

    private function companyHash(): string
    {
        return sha1((string) self::COMPANY_ID);
    }

    private function wxKey(): string
    {
        return 'wxPaymentSetting:'.$this->companyHash();
    }

    private function hfKey(): string
    {
        return 'hfPaymentSetting:'.$this->companyHash();
    }

    private function offlineKey(): string
    {
        return 'offline_paySetting:'.$this->companyHash().':zh-CN';
    }

    /**
     * TC-1：微信已开 + cert/cert_key 字符串 → 关闭后 is_open==='false'，cert 原样。
     */
    public function testTc1WechatClosePreservesCertFields(): void
    {
        #given
        $cert = '-----BEGIN CERTIFICATE-----\nMIIB\n-----END CERTIFICATE-----';
        $certKey = '-----BEGIN PRIVATE KEY-----\nMIIE\n-----END PRIVATE KEY-----';
        $this->bucket[$this->wxKey()] = json_encode([
            'is_open' => 'true',
            'cert' => $cert,
            'cert_key' => $certKey,
        ], JSON_THROW_ON_ERROR);
        $svc = new PaymentDoumenIntlMutualExclusionService();

        #when
        $svc->closeAllOtherPaymentMethods(self::COMPANY_ID);

        #then
        $stored = json_decode($this->bucket[$this->wxKey()], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('false', $stored['is_open']);
        $this->assertSame($cert, $stored['cert']);
        $this->assertSame($certKey, $stored['cert_key']);
    }

    /**
     * TC-2：HfPay 含 pfx_file → 关闭后 is_open==='false'，pfx_file 原样。
     */
    public function testTc2HfPayClosePreservesPfxFile(): void
    {
        #given
        $pfxFile = '/uploads/hf/test.pfx';
        $this->bucket[$this->hfKey()] = json_encode([
            'is_open' => 'true',
            'pfx_file' => $pfxFile,
        ], JSON_THROW_ON_ERROR);
        $svc = new PaymentDoumenIntlMutualExclusionService();

        #when
        $svc->closeAllOtherPaymentMethods(self::COMPANY_ID);

        #then
        $stored = json_decode($this->bucket[$this->hfKey()], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('false', $stored['is_open']);
        $this->assertSame($pfxFile, $stored['pfx_file']);
    }

    /**
     * TC-3：无 key / 空配置 → redis set 不被调用。
     */
    public function testTc3NoConfigDoesNotCallSet(): void
    {
        #given
        $svc = new PaymentDoumenIntlMutualExclusionService();

        #when
        $svc->closeAllOtherPaymentMethods(self::COMPANY_ID);

        #then
        $this->assertSame([], $this->setCalls);
    }

    /**
     * TC-4：9 渠道各自已开 → 关值类型与字面量符合契约（strict）。
     *
     * @dataProvider tc4ChannelProvider
     *
     * @param  bool|int|string  $closedValue
     */
    public function testTc4EachChannelClosedValueMatchesContract(
        string $redisKey,
        $closedValue,
        mixed $openValue
    ): void {
        #given
        $this->bucket[$redisKey] = json_encode(['is_open' => $openValue], JSON_THROW_ON_ERROR);
        $svc = new PaymentDoumenIntlMutualExclusionService();

        #when
        $svc->closeAllOtherPaymentMethods(self::COMPANY_ID);

        #then
        $stored = json_decode($this->bucket[$redisKey], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($closedValue, $stored['is_open']);
    }

    /**
     * @return array<string, array{0: string, 1: bool|int|string, 2: mixed}>
     */
    public static function tc4ChannelProvider(): array
    {
        $hash = sha1((string) self::COMPANY_ID);

        return [
            'wx' => ['wxPaymentSetting:'.$hash, 'false', 'true'],
            'alipay' => ['alipayPaymentSetting:'.$hash, false, true],
            'paypal' => ['paypalPaymentSetting:'.$hash, false, true],
            'chinaums' => ['chinaumsPaymentSetting:'.$hash, false, true],
            'offline' => ['offline_paySetting:'.$hash.':zh-CN', 'false', 'true'],
            'bspay' => ['bspaySetting:'.$hash, false, true],
            'hf' => ['hfPaymentSetting:'.$hash, 'false', 'true'],
            'adapay' => ['adaPaySetting:'.$hash, false, true],
            'icbc' => ['icbcPaymentSetting:'.$hash, 0, 1],
        ];
    }

    /**
     * TC-5：微信关闭后 decode 无 cert_url/cert_name 派生字段。
     */
    public function testTc5WechatCloseDoesNotAddDerivedCertFields(): void
    {
        #given
        $this->bucket[$this->wxKey()] = json_encode([
            'is_open' => 'true',
            'cert' => '-----BEGIN CERTIFICATE-----\nMIIB\n-----END CERTIFICATE-----',
            'cert_key' => '-----BEGIN PRIVATE KEY-----\nMIIE\n-----END PRIVATE KEY-----',
        ], JSON_THROW_ON_ERROR);
        $svc = new PaymentDoumenIntlMutualExclusionService();

        #when
        $svc->closeAllOtherPaymentMethods(self::COMPANY_ID);

        #then
        $stored = json_decode($this->bucket[$this->wxKey()], true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayNotHasKey('cert_url', $stored);
        $this->assertArrayNotHasKey('cert_name', $stored);
    }
}
