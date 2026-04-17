<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace CompanysBundle\Services;

use CompanysBundle\Services\Shops\WxShopsService;
use DistributionBundle\Services\DistributorService;

/**
 * 与 GET /companys/setting（Companys::getCompanySetting）中 brand_name 规则一致，供邮件等无 JWT 场景按 company_id 解析。
 */
class CompanySettingBrandNameService
{
    public const DEFAULT_BRAND = 'ECShopX';

    /**
     * 解析店铺展示用品牌名：wx 店铺设置 + 自营总店 name 覆盖；trim 后为空则 DEFAULT_BRAND（中英文同一逻辑、同一字符串）。
     */
    public function resolveForCompanyId(int $companyId): string
    {
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getWxShopsSetting($companyId);
        if (!is_array($result)) {
            $result = [];
        }
        $selfDistributorInfo = (new DistributorService())->getDistributorSelf($companyId, true);
        if (!empty($selfDistributorInfo) && $result) {
            $result['brand_name'] = $selfDistributorInfo['name'] ?? '';
        }
        $brand = trim((string) ($result['brand_name'] ?? ''));

        return $brand !== '' ? $brand : self::DEFAULT_BRAND;
    }
}
