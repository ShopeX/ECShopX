<?php

declare(strict_types=1);

namespace Tests\EspierBundle;

use EspierBundle\Services\Export\NormalItemsCodeExportService;

/**
 * 商品 H5 二维码导出 URL 路径 — subpages/item/espier-detail
 * 计划：.tasks/plans/h5-qrcode-espier-detail-path.md
 */
class NormalItemsCodeExportH5UrlTest extends \TestCase
{
    /**
     * TC-1：formatH5ItemUrl 拼接完整 H5 商品详情 URL。
     * #given domain=d.example.com, itemId=8472, distributorId=0
     * #when 调用 formatH5ItemUrl
     * #then 返回 https://d.example.com/subpages/item/espier-detail?id=8472&dtid=0
     */
    public function testFormatH5ItemUrlReturnsSubpagesPath(): void
    {
        #given
        $domain = 'd.example.com';
        $itemId = 8472;
        $distributorId = 0;

        #when
        $url = NormalItemsCodeExportService::formatH5ItemUrl($domain, $itemId, $distributorId);

        #then
        $this->assertSame(
            'https://d.example.com/subpages/item/espier-detail?id=8472&dtid=0',
            $url
        );
    }

    /**
     * TC-2：formatH5ItemUrl 结果不含旧路径 pages/item/espier-detail。
     * #given domain=d.example.com, itemId=8472, distributorId=0
     * #when 调用 formatH5ItemUrl
     * #then 结果字符串不含 /pages/item/espier-detail
     */
    public function testFormatH5ItemUrlDoesNotContainOldPagesPath(): void
    {
        #given
        $domain = 'd.example.com';
        $itemId = 8472;
        $distributorId = 0;

        #when
        $url = NormalItemsCodeExportService::formatH5ItemUrl($domain, $itemId, $distributorId);

        #then
        $this->assertStringNotContainsString(
            '/pages/item/espier-detail',
            $url
        );
    }
}
