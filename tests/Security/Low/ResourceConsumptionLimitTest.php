<?php

declare(strict_types=1);

namespace Tests\Security\Low;

use EmployeePurchaseBundle\Services\EmployeesService;
use EspierBundle\Services\UploadFileService;
use TestCase;

/**
 * codex-security-05-low T40-RED / F-192,F-193 / TC-05-06
 */
class ResourceConsumptionLimitTest extends TestCase
{
    /**
     * TC-05-06 / F-192：表格导入须限制解压/处理规模，不得无限 set_time_limit(0)。
     * #given UploadFileService::handleUploadFile
     * #when 检查导入资源上限
     * #then 须含行数/大小/超时限制
     */
    public function testTc0506ImportHasResourceLimits(): void
    {
        #given
        $body = $this->methodBody(UploadFileService::class, 'handleUploadFile');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'MAX_IMPORT_ROWS',
                'max_import_rows',
                'IMPORT_MAX_ROWS',
                'maxRows',
                'MAX_SPREADSHEET_ROWS',
            ]),
            'handleUploadFile must cap spreadsheet import row count'
        );
        $this->assertStringNotContainsString('set_time_limit(0)', $body);
    }

    /**
     * TC-05-06 / F-193：员工邮箱验证码发送须有冷却/频率限制。
     * #given EmployeesService::sendEmailVcode
     * #when 检查限流逻辑
     * #then 须含 cooldown/rate limit 防护
     */
    public function testTc0506EmailVcodeHasRateLimit(): void
    {
        #given
        $body = $this->methodBody(EmployeesService::class, 'sendEmailVcode');

        #when / #then
        $this->assertTrue(
            $this->containsAny($body, [
                'cooldown',
                'rateLimit',
                'rate_limit',
                'assertEmailVcodeRateLimit',
                'EMAIL_VCODE_COOLDOWN',
                'throttle',
            ]),
            'sendEmailVcode must throttle repeated SMTP sends'
        );
    }

    /**
     * @param string[] $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
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
