<?php

declare(strict_types=1);

namespace Tests\ShuyunOpenPlatform;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
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
        $sign = '96b3b5d255d9ab923a9e772dd74ff572';
        $req = Request::create('http://localhost/c?sign='.$sign, 'POST', [], [], [], [
            'HTTP_SY_REQUEST_TIME' => '1690000000000',
        ], '[]');
        $this->assertTrue($v->verifyHttpCallback($secret, $req, $sign));
    }

    public function testVerifyHttpCallbackSortedQueryPlusHeader(): void
    {
        $v = new ShuyunCallbackSignatureVerifier();
        $secret = 'mysecret';
        $sign = 'bb5b6cd3930cbaa625f7f42b2a111462';
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
        $sign = 'ce57b6a4c37a7b57fea78dc98f79e414';
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
}
