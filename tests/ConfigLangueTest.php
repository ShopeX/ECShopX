<?php

/**
 * config/langue.php 结构验收（计划 default-lang-config TC4）
 * 断言返回数组包含 list（语言列表）与 default（默认语言，为 list 中一项）。
 */
class ConfigLangueTest extends TestCase
{
    public function testLangueConfigHasListAndDefault(): void
    {
        $config = config('langue');
        $this->assertIsArray($config, 'config(langue) 应返回数组');
        $this->assertArrayHasKey('list', $config, '应包含 list 键');
        $this->assertArrayHasKey('default', $config, '应包含 default 键');
        $this->assertIsArray($config['list'], 'list 应为数组');
        $this->assertContains($config['default'], $config['list'], 'default 须为 list 中的一项');
    }

    /**
     * 调用方兼容：语言列表应由 config('langue.list') ?? config('langue') 取得，且为字符串数组。
     * 计划 default-lang-config 1.2 / TC4
     */
    public function testCallerCompatibleLangueListIsArrayOfStrings(): void
    {
        $list = config('langue.list') ?? config('langue');
        $list = is_array($list) ? $list : [];
        $this->assertIsArray($list, '调用方兼容列表应为数组');
        foreach ($list as $v) {
            $this->assertIsString($v, '列表中每项应为语言码字符串');
        }
    }
}
