<?php

declare(strict_types=1);

namespace Tests\Security\Medium\Idor;

use FormBundle\Http\Api\V1\Action\Transcripts;
use FormBundle\Services\TranscriptService;
use TestCase;

/**
 * codex-security-04-medium T31-RED / F-088,F-089 / TC-04-01a
 */
class FormTranscriptTenantScopeTest extends TestCase
{
    /**
     * TC-04-01a：删除成绩单须校验 transcript 归属当前租户。
     * #given deleteTranscript 与 TranscriptService::delete
     * #when 检查租户 scope
     * #then 须传 company_id 并校验归属
     */
    public function testTc0401aDeleteTranscriptScopesByCompanyId(): void
    {
        #given
        $controllerBody = $this->methodBody(Transcripts::class, 'deleteTranscript');
        $serviceRef = new \ReflectionMethod(TranscriptService::class, 'delete');
        $serviceParams = array_map(
            static fn (\ReflectionParameter $p) => $p->getName(),
            $serviceRef->getParameters()
        );

        $serviceBody = $this->getMethodSourceLines($serviceRef);

        #when / #then
        $this->assertStringContainsString('delete($companyId', $controllerBody);
        $this->assertContains('companyId', $serviceParams);
        $this->assertStringContainsString('company_id', $serviceBody);
    }

    /**
     * TC-04-01a / F-089,F-133：更新成绩单须校验 transcript/property 归属当前租户。
     * #given TranscriptService::update
     * #when 检查租户 scope
     * #then 须调用 FormTenantScopeGuard
     */
    public function testTc0401aUpdateTranscriptScopesByCompanyId(): void
    {
        #given
        $serviceBody = $this->methodBody(TranscriptService::class, 'update');

        #when / #then
        $this->assertStringContainsString('FormTenantScopeGuard', $serviceBody);
        $this->assertStringContainsString('assertTranscriptIdBelongsToCompany', $serviceBody);
        $this->assertStringContainsString('assertTranscriptPropertyIdsBelongToTranscript', $serviceBody);
    }

    private function methodBody(string $class, string $method): string
    {
        return $this->getMethodSourceLines(new \ReflectionMethod($class, $method));
    }

    private function getMethodSourceLines(\ReflectionMethod $ref): string
    {
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);

        return implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    }
}
