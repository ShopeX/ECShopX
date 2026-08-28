<?php

declare(strict_types=1);

namespace Tests\Security\Low\Tls;

use CompanysBundle\Ego\CompanysActivationEgo;
use ShopexAIBundle\Services\AliyunImageService;
use ShopexAIBundle\Services\JimengImageService;
use ShopexAIBundle\Services\OutfitAnyoneService;
use SystemLinkBundle\Services\Jushuitan\Request as JushuitanRequest;
use SystemLinkBundle\Services\ShopexErp\OpenApi\Request as ShopexErpOpenApiRequest;
use SystemLinkBundle\Services\ShopexErp\Request as ShopexErpRequest;
use TestCase;
use ThirdPartyBundle\Services\SaasErpCentre\Request as SaasErpRequest;
use WsugcBundle\Services\ContentCheckService;

/**
 * codex-security-05-low T40 / F-174..182 / TC-05-01
 */
class TlsVerifyContractTest extends TestCase
{
    public function testTc0501AliyunImageClientEnablesTlsVerification(): void
    {
        #given
        $body = $this->constructorBody(AliyunImageService::class);

        #when / #then
        $this->assertTlsVerifyEnabled($body);
    }

    public function testTc0501ContentCheckEnablesTlsVerification(): void
    {
        #given
        $body = $this->methodBody(ContentCheckService::class, 'requestWxApi');

        #when / #then
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYPEER, true', $body);
        $this->assertStringContainsString('CURLOPT_SSL_VERIFYHOST, 2', $body);
        $this->assertStringNotContainsString('CURLOPT_SSL_VERIFYPEER, false', $body);
    }

    public function testTc0501JimengImageClientEnablesTlsVerification(): void
    {
        #given
        $body = $this->constructorBody(JimengImageService::class);

        #when / #then
        $this->assertTlsVerifyEnabled($body);
    }

    public function testTc0501JushuitanClientEnablesTlsVerificationWithoutSecretLogging(): void
    {
        #given
        $body = $this->methodBody(JushuitanRequest::class, 'call');

        #when / #then
        $this->assertTlsVerifyEnabled($body);
        $this->assertStringNotContainsString('===appSecret:', $body);
    }

    public function testTc0501OutfitAnyoneClientEnablesTlsVerification(): void
    {
        #given
        $body = $this->constructorBody(OutfitAnyoneService::class);

        #when / #then
        $this->assertTlsVerifyEnabled($body);
    }

    public function testTc0501SaasErpClientEnablesTlsVerificationWithoutTokenLogging(): void
    {
        #given
        $body = $this->methodBody(SaasErpRequest::class, 'call');

        #when / #then
        $this->assertTlsVerifyEnabled($body);
        $this->assertStringNotContainsString('===token:', $body);
    }

    public function testTc0501ShopexErpClientEnablesTlsVerificationWithoutTokenLogging(): void
    {
        #given
        $body = $this->methodBody(ShopexErpRequest::class, 'call');

        #when / #then
        $this->assertTlsVerifyEnabled($body);
        $this->assertStringNotContainsString('===token:', $body);
    }

    public function testTc0501ShopexOpenApiClientEnablesTlsVerificationWithoutTokenLogging(): void
    {
        #given
        $body = $this->methodBody(ShopexErpOpenApiRequest::class, 'call');

        #when / #then
        $this->assertTlsVerifyEnabled($body);
        $this->assertStringNotContainsString('===token:', $body);
    }

    public function testTc0501StandaloneActivationEnablesTlsVerification(): void
    {
        #given
        $body = $this->methodBody(CompanysActivationEgo::class, 'independentCheckActiveCode');

        #when / #then
        $this->assertTlsVerifyEnabled($body);
    }

    private function assertTlsVerifyEnabled(string $body): void
    {
        $normalized = str_replace(' ', '', $body);
        $this->assertStringContainsString("'verify'=>true", $normalized);
        $this->assertStringNotContainsString("'verify'=>false", $normalized);
    }

    private function constructorBody(string $class): string
    {
        $ref = new \ReflectionMethod($class, '__construct');

        return $this->sliceMethodSource($ref);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);

        return $this->sliceMethodSource($ref);
    }

    private function sliceMethodSource(\ReflectionMethod $ref): string
    {
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
