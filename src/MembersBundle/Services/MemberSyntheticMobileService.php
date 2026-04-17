<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;

class MemberSyntheticMobileService
{
    /**
     * 是否为邮箱注册时分配的占位手机号。
     * 当前规则：**`10` + 9 位数字**（与 {@see allocateUnique} 一致）。
     * 兼容历史数据：**`199` + 8 位数字**。
     */
    public static function isAllocatedSyntheticMobile(?string $mobile): bool
    {
        if ($mobile === null || $mobile === '') {
            return false;
        }

        return (bool) (preg_match('/^10\d{9}$/', $mobile) || preg_match('/^199\d{8}$/', $mobile));
    }

    /**
     * 前台会员接口：占位手机号不展示给客户端，置为空字符串。
     *
     * @param array $member members 行或 getMemberInfo 合并后的单会员数组（引用修改）
     */
    public static function stripSyntheticMobileForFrontApi(array &$member): void
    {
        foreach (['mobile', 'region_mobile'] as $key) {
            if (!array_key_exists($key, $member)) {
                continue;
            }
            $v = (string) $member[$key];
            if ($v !== '' && self::isAllocatedSyntheticMobile($v)) {
                $member[$key] = '';
            }
        }
    }

    /**
     * 店铺端 / 导出等：已绑定登录邮箱且 mobile 为占位号时，不在接口结果中返回手机号（置空）。
     * 有真实手机号的会员（即使填写了 login_email）不受影响。
     */
    public static function stripPlaceholderMobileForEmailRegisteredMember(array &$member): void
    {
        if (trim((string) ($member['login_email'] ?? '')) === '') {
            return;
        }
        foreach (['mobile', 'region_mobile'] as $key) {
            if (!array_key_exists($key, $member)) {
                continue;
            }
            $v = (string) $member[$key];
            if ($v !== '' && self::isAllocatedSyntheticMobile($v)) {
                $member[$key] = '';
            }
        }
    }

    /**
     * 为邮箱注册会员分配占位手机号：**`10` 开头 + 9 位随机数字**（共 11 位），保证 `(mobile, company_id)` 唯一。
     * 非公众移动号段，仅作技术占位；前台展示见 {@see stripSyntheticMobileForFrontApi}。
     */
    public function allocateUnique(int $companyId): string
    {
        $memberService = new MemberService();
        for ($i = 0; $i < 80; $i++) {
            $suffix = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            $candidate = '10' . $suffix;
            $existing = $memberService->getInfoByMobile($companyId, $candidate);
            if (!$existing) {
                return $candidate;
            }
        }
        throw new ResourceException(trans('MembersBundle/Members.synthetic_mobile_failed'));
    }
}
