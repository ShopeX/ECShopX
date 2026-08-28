<?php

declare(strict_types=1);

namespace Tests\EspierBundle\Support;

use EspierBundle\Support\OrderByWhitelist;

/**
 * codex-security-01-platform-baseline T02：OrderByWhitelist（TC-01-04..05）
 */
class OrderByWhitelistTest extends \PHPUnit\Framework\TestCase
{
    /**
     * TC-01-04 / AC-01-04：非法 ORDER BY 列名 → 回退默认，SQL 不含用户原串
     * #given sort 含 id;drop 注入列名
     * #when 调用 fromSortString
     * #then 返回默认排序，不含 id;drop
     */
    public function testTc0104RejectsIllegalOrderByColumn(): void
    {
        #given
        $sort = 'id;drop desc';
        $allowed = ['created', 'id'];
        $default = ['created' => 'DESC'];

        #when
        $orderBy = OrderByWhitelist::fromSortString($sort, $allowed, $default);

        #then
        $this->assertSame(['created' => 'DESC'], $orderBy);
        $this->assertStringNotContainsString('id;drop', json_encode($orderBy));
    }

    /**
     * TC-01-05 / AC-01-05：非法 sort 方向 → 仅允许 asc/desc，回退默认
     * #given sort 方向为 ASC--
     * #when 调用 fromSortString
     * #then 回退默认排序
     */
    public function testTc0105RejectsIllegalSortDirection(): void
    {
        #given
        $sort = 'created ASC--';
        $allowed = ['created'];
        $default = ['created' => 'DESC'];

        #when
        $orderBy = OrderByWhitelist::fromSortString($sort, $allowed, $default);

        #then
        $this->assertSame(['created' => 'DESC'], $orderBy);
    }
}
