<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor\Distribution;

use DistributionBundle\Http\Api\V1\Action\DistributorWhiteList;
use DistributionBundle\Services\DistributorWhiteListService;
use TestCase;

/**
 * codex-security-04-medium T31-GREEN-IDOR-Rest / F-106 / AC-04-01
 */
class DistributorWhitelistScopeTest extends TestCase
{
    /**
     * TC-04-01 / F-106：白名单删除须绑定 auth company_id。
     */
    public function testTc0401DeleteWhiteListScopesByAuthCompanyId(): void
    {
        #given
        $body = $this->methodBody(DistributorWhiteList::class, 'deleteWhiteList');

        #when / #then
        $this->assertStringContainsString('company_id', $body);
    }

    /**
     * TC-04-01 / F-106：白名单删除 service 须按 company_id 过滤。
     */
    public function testTc0401DeleteWhiteListServiceBindsCompanyId(): void
    {
        #given
        $deleteOneBody = $this->methodBody(DistributorWhiteListService::class, 'deleteOneWhiteList');
        $deleteByBody = $this->methodBody(DistributorWhiteListService::class, 'deleteByDistributorId');

        #when / #then
        $this->assertStringContainsString('company_id', $deleteOneBody);
        $this->assertStringContainsString('company_id', $deleteByBody);
    }

    private function methodBody(string $class, string $method): string
    {
        $ref = new \ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
