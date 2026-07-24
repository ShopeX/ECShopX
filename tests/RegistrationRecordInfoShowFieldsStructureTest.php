<?php

/**
 * registration-record-show-fields：报名详情 activity_info 透传 show_fields（TC-1/TC-2）
 */

use SelfserviceBundle\Services\RegistrationRecordService;

class RegistrationRecordInfoShowFieldsStructureTest extends TestCase
{
    private function methodBody(string $class, string $method): string
    {
        $ref = new ReflectionMethod($class, $method);
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($lines);
        $slice = array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1);

        return implode("\n", $slice);
    }

    /**
     * TC-1：getRocordInfo 方法体透传 show_fields，且不对 show_fields 做 json_decode。
     */
    public function testGetRocordInfoActivityInfoPassesShowFieldsWithoutJsonDecode(): void
    {
        $body = $this->methodBody(RegistrationRecordService::class, 'getRocordInfo');
        $this->assertStringContainsString("'show_fields' => \$activity_info['show_fields']", $body);
        $this->assertStringNotContainsString("json_decode(\$activity_info['show_fields']", $body);
        $this->assertStringNotContainsString('json_decode($activity_info["show_fields"]', $body);
    }

    /**
     * TC-2：getRocordInfo 保留既有字段；getRocordList 同样透传 show_fields。
     */
    public function testGetRocordInfoKeepsExistingFieldsAndListDoesNotPassShowFields(): void
    {
        $infoBody = $this->methodBody(RegistrationRecordService::class, 'getRocordInfo');
        $this->assertStringContainsString("'is_offline_verify' => \$activity_info['is_offline_verify']", $infoBody);
        $this->assertStringContainsString("'area_name' => \$area_name", $infoBody);

        $listBody = $this->methodBody(RegistrationRecordService::class, 'getRocordList');
        $this->assertStringContainsString("'show_fields' => \$activity_info['show_fields']", $listBody);
        $this->assertStringNotContainsString("json_decode(\$activity_info['show_fields']", $listBody);
    }
}
