<?php

declare(strict_types=1);

namespace Tests\Security;

use CompanysBundle\MultiLang\MultiLangItem;
use TestCase;

/**
 * ECX-10753：country_code 经 MultiLangItem 拼表名时的 SQL 注入防护。
 */
class MultiLangItemTableLanguageSanitizeTest extends TestCase
{
    public function testLegacyLanguageCodesMapToKnownSuffixes(): void
    {
        $item = new MultiLangItem('zh-CN');
        $table = $this->readTable($item);

        $this->assertSame('item_multi_lang_mod_lang_zhCN', $table);
    }

    public function testSafeLanguageCodeIsNormalized(): void
    {
        $item = new MultiLangItem('zh');
        $table = $this->readTable($item);

        $this->assertSame('item_multi_lang_mod_lang_zh', $table);
    }

    public function testSqlInjectionPayloadFallsBackToDefaultLanguageSuffix(): void
    {
        $payload = 'zh WHERE 1=1 AND EXTRACTVALUE(1,CONCAT(0x3a,(SELECT version()))) #';
        $item = new MultiLangItem($payload);
        $table = $this->readTable($item);

        $this->assertSame('item_multi_lang_mod_lang_zhCN', $table);
        $this->assertDoesNotMatchRegularExpression('/\s|\'|"|;|#/', $table);
    }

    public function testQuoteAndUnionKeywordsAreRejected(): void
    {
        $item = new MultiLangItem("zh' UNION SELECT 1--");
        $table = $this->readTable($item);

        $this->assertSame('item_multi_lang_mod_lang_zhCN', $table);
    }

    private function readTable(MultiLangItem $item): string
    {
        $ref = new \ReflectionProperty(MultiLangItem::class, 'table');
        $ref->setAccessible(true);

        return (string) $ref->getValue($item);
    }
}
