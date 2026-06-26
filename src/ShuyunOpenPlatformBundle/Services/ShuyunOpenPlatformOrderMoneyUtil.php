<?php

declare(strict_types=1);

namespace ShuyunOpenPlatformBundle\Services;

/**
 * 订单出站金额：商城整数 **分** → 数云 **元**（订单计划 **D-MONEY-01**）。
 */
final class ShuyunOpenPlatformOrderMoneyUtil
{
    /**
     * @param  int|string  $fen  分为单位的非负整数（字符串数字亦可）
     *
     * @return string 两位小数字符串（非网关 JSON 用；如商品同步等）
     */
    public static function fenToYuan(int|string $fen): string
    {
        $n = is_int($fen) ? $fen : (int) $fen;
        if ($n < 0) {
            $n = 0;
        }
        if (\function_exists('bcdiv')) {
            return bcdiv((string) $n, '100', 2);
        }

        return sprintf('%d.%02d', intdiv($n, 100), $n % 100);
    }

    /**
     * 分 → 元，供 {@see shuyun.base.trade.sync} / {@see shuyun.base.refund.sync} / {@see shuyun.base.product.sync} 等要求 **JSON Number** 的字段使用。
     * 两位小数；优先 `bcdiv` 再转 float，减少浮点长尾。
     *
     * @param  int|string  $fen
     */
    public static function fenToYuanNumber(int|string $fen): float
    {
        $n = is_int($fen) ? $fen : (int) $fen;
        if ($n < 0) {
            $n = 0;
        }
        if (\function_exists('bcdiv')) {
            return (float) bcdiv((string) $n, '100', 2);
        }

        return round($n / 100.0, 2);
    }
}
