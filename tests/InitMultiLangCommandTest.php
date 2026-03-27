<?php

/**
 * InitMultiLangCommand 使用 config('langue.list') ?? config('langue') 作为语言列表（计划 default-lang-config 1.2）。
 * 断言：写入前读取的列表为数组，且与新格式 list 一致。
 */

use EspierBundle\Commands\InitMultiLangCommand;

class InitMultiLangCommandTest extends TestCase
{
    /**
     * 命令 writeLangueConfig 使用的列表必须为 config('langue.list') ?? config('langue')，且为数组。
     * 否则新格式下 in_array 会得到错误结果，或写入错误结构。
     */
    public function testWriteLangueConfigUsesLangueListFromConfig(): void
    {
        $expectedList = config('langue.list') ?? config('langue');
        $expectedList = is_array($expectedList) ? $expectedList : [];

        $command = new InitMultiLangCommand();
        $ref = new \ReflectionMethod($command, 'writeLangueConfig');
        $ref->setAccessible(true);

        $configPath = config_path('langue.php');
        $backup = file_exists($configPath) ? file_get_contents($configPath) : null;

        try {
            $ref->invoke($command, 'zh-CN'); // 已存在的语种，不应改变文件内容（或仅确保不报错）
            $content = file_get_contents($configPath);
            $this->assertNotEmpty($content, 'config 文件应有内容');

            $return = include $configPath;
            if (is_array($return)) {
                $isNewFormat = array_key_exists('list', $return) && array_key_exists('default', $return);
                if ($isNewFormat) {
                    $this->assertArrayHasKey('list', $return);
                    $this->assertSame($expectedList, $return['list'], '新格式下 list 应与 config(langue.list) 一致');
                } else {
                    $this->assertSame($expectedList, array_values($return), '旧格式下应与 config(langue.list) ?? config(langue) 一致');
                }
            }
        } finally {
            if ($backup !== null) {
                file_put_contents($configPath, $backup);
            }
        }
    }

    /**
     * 追加新语种时，写入的必须为扁平语言码数组（来自 config('langue.list') ?? config('langue')），不能带 list/default 键。
     */
    public function testWriteLangueConfigAppendsNewLangAsFlatList(): void
    {
        $currentList = config('langue.list') ?? config('langue');
        $currentList = is_array($currentList) ? $currentList : [];
        $newLang = 'en-US';
        $this->assertNotContains($newLang, $currentList, '测试用新语种不应已在列表中');

        $command = new InitMultiLangCommand();
        $ref = new \ReflectionMethod($command, 'writeLangueConfig');
        $ref->setAccessible(true);

        $configPath = config_path('langue.php');
        $backup = file_exists($configPath) ? file_get_contents($configPath) : null;

        try {
            $ref->invoke($command, $newLang);

            $return = include $configPath;
            $this->assertIsArray($return, '写入后 config 应为数组');
            $this->assertContains($newLang, $return, '应包含新追加的语种');
            $this->assertArrayNotHasKey('list', $return, '写入的应为扁平列表');
            $this->assertArrayNotHasKey('default', $return, '写入的应为扁平列表');
        } finally {
            if ($backup !== null) {
                file_put_contents($configPath, $backup);
            }
        }
    }
}
