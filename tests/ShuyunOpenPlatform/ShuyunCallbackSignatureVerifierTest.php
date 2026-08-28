<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ShuyunOpenPlatformBundle\Auth\InMemoryShuyunCallbackNonceStore;
use ShuyunOpenPlatformBundle\Auth\ShuyunCallbackSignatureVerifier;

class ShuyunCallbackSignatureVerifierTest extends TestCase
{
    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-SIGN-01 */
    public function testValidSignPasses(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $secret = 'mysecret';
        $t = '1690000000000';
        $sign = 'ce57b6a4c37a7b57fea78dc98f79e414';
        $this->assertTrue($v->verify($secret, $t, $sign));
        $this->assertSame($sign, $v->expectedSign($secret, $t));
    }

    /** @see .tasks/plans/shuyun-open-platform-auth-automation.md TC-CALLBACK-SIGN-02 */
    public function testTamperedSignFails(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $this->assertFalse($v->verify('mysecret', '1690000000000', 'deadbeefdeadbeefdeadbeefdeadbeef'));
    }

    public function testVerifyHttpCallbackSyRequestTimeHeaderOnly(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $secret = 'mysecret';
        $sign = '96d7565d8a6976780dca2c7401053e77';
        $req = Request::create('http://localhost/c?sign='.$sign, 'POST', [], [], [], [
            'HTTP_SY_REQUEST_TIME' => '1690000000000',
        ], '[]');
        $this->assertTrue($v->verifyHttpCallback($secret, $req, $sign));
    }

    public function testVerifyHttpCallbackSortedQueryPlusHeader(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $secret = 'mysecret';
        $sign = 'b947a417a2fd0aa40212f08e5952ee88';
        $req = Request::create(
            'http://localhost/c?a=aaa&z=zzz&sign='.$sign,
            'POST',
            [],
            [],
            [],
            ['HTTP_SY_REQUEST_TIME' => '169'],
            '[]'
        );
        $this->assertTrue($v->verifyHttpCallback($secret, $req, $sign));
    }

    public function testVerifyHttpCallbackLegacyCallBackTimeInQuery(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $secret = 'mysecret';
        $sign = 'a5fa1736fdb08d8355ada28ae802783b';
        $req = Request::create(
            'http://localhost/c?callBackTime=1690000000000&sign='.$sign,
            'POST',
            [],
            [],
            [],
            [],
            '[]'
        );
        $this->assertTrue($v->verifyHttpCallback($secret, $req, $sign));
    }

    /**
     * TC-01-10 / AC-01-09：Shuyun body+nonce 重放
     * #given 合法签名含 raw body、SY-Request-Time、SY-Request-Nonce
     * #when 篡改 body 或重复提交同一 nonce
     * #then 验签失败
     */
    public function testTc0110RejectsBodyTamperingAndNonceReplay(): void
    {
        #given
        $v = new ShuyunCallbackSignatureVerifier();
        $store = new InMemoryShuyunCallbackNonceStore();
        $secret = 'mysecret';
        $nonce = 'tc-01-10-nonce-001';
        $time = (string) ((int) (microtime(true) * 1000));
        $body = '{"platCode":"OFFLINE"}';
        $req = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => $time,
            'HTTP_SY_REQUEST_NONCE' => $nonce,
        ], $body);
        $sign = $v->expectedHttpCallbackSign($secret, $req);

        #when first verify
        $first = $v->verifyHttpCallback($secret, $req, $sign, $store);

        #then first succeeds
        $this->assertTrue($first, 'TC-01-10: valid signed callback must pass once');

        #when replay same nonce
        $replayReq = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => $time,
            'HTTP_SY_REQUEST_NONCE' => $nonce,
        ], $body);
        $replay = $v->verifyHttpCallback($secret, $replayReq, $sign, $store);

        #then replay rejected
        $this->assertFalse($replay, 'TC-01-10: nonce replay must be rejected');

        #when body tampered but sign unchanged
        $tamperedBody = '{"platCode":"HACKED"}';
        $tamperedReq = Request::create('/cb', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_SY_REQUEST_TIME' => $time,
            'HTTP_SY_REQUEST_NONCE' => 'tc-01-10-nonce-002',
        ], $tamperedBody);
        $tampered = $v->verifyHttpCallback($secret, $tamperedReq, $sign, $store);

        #then body binding rejects tamper
        $this->assertFalse($tampered, 'TC-01-10: body tampering must fail signature verification');
    }
}
