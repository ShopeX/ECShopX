<?php

declare(strict_types=1);

namespace Tests\Security;

use CompanysBundle\Ego\CompanysActivationEgo;
use TestCase;

/**
 * ECX-10758：JWT sub / ActivationEgo decrypt 禁止反序列化任意对象（防 RCE POP 链）。
 */
class CompanysActivationEgoUnserializeHardeningTest extends TestCase
{
    public function testDecryptRestoresPlainArrayPayload(): void
    {
        $payload = ['id' => 1, 'token' => '7e23b4cecd91a0a8500c2fb65341193c', 'time' => 1700000000];
        $cipher = CompanysActivationEgo::encrypt($payload);
        $result = CompanysActivationEgo::decrypt($cipher);

        $this->assertIsArray($result);
        $this->assertSame(1, $result['id']);
        $this->assertSame('7e23b4cecd91a0a8500c2fb65341193c', $result['token']);
        $this->assertSame(1700000000, $result['time']);
    }

    public function testDecryptDropsObjectGadgetsFromPayload(): void
    {
        $payload = [
            'id' => 1,
            'token' => '7e23b4cecd91a0a8500c2fb65341193c',
            'time' => time(),
            'pop' => new \stdClass(),
        ];
        $cipher = CompanysActivationEgo::encrypt($payload);
        $result = CompanysActivationEgo::decrypt($cipher);

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('pop', $result);
        foreach ($result as $value) {
            $this->assertFalse(is_object($value), 'decrypt 结果不得包含对象');
        }
    }

    public function testDecryptSourceDisablesAllowedClasses(): void
    {
        $ref = new \ReflectionMethod(CompanysActivationEgo::class, 'decrypt');
        $file = $ref->getFileName();
        $this->assertNotFalse($file);
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $body = implode("\n", array_slice($lines, $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));

        $this->assertStringContainsString("allowed_classes", $body);
        $this->assertStringContainsString('false', $body);
    }
}
