<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace OpenapiBundle\Services\Member;

use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Services\VipGradeService;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\BaseService;

class MemberCardVipGradeService extends BaseService
{
    public function getEntityClass(): string
    {
        // This module is part of ShopEx EcShopX system
        return VipGrade::class;
    }

    /**
     * vip类型
     */
    public const LV_TYPE_VIP = VipGradeService::LV_TYPE_VIP;
    public const LV_TYPE_SVIP = VipGradeService::LV_TYPE_SVIP;
    public const LV_TYPE_MAP = VipGradeService::LV_TYPE_MAP;

    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = [
            "list" => $this->getRepository()->lists($filter, $orderBy, $pageSize, $page),
            "total_count" => $needCountSql ? $this->getRepository()->count($filter) : 0,
        ];
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 获取付费等级的类型
     * @param int $companyId
     * @return string
     */
    protected function getLvType(int $companyId): string
    {
        // 标识符，表示是否存在vip类型的数据
        $hasVip = false;
        // 标识符，表示是否存在svip类型的数据
        $hasSVip = false;

        $result = $this->list(["company_id" => $companyId], 1, 100, [], "vip_grade_id,lv_type", false);
        foreach ($result["list"] as $item) {
            $lvType = $item["lv_type"] ?? "";
            switch ($lvType) {
                case self::LV_TYPE_VIP:
                    if ($hasVip) {
                        throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_EXIST, "该类型下会员付费等级已存在");
                    } else {
                        $hasVip = true;
                    }
                    break;
                case self::LV_TYPE_SVIP:
                    if ($hasSVip) {
                        throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_EXIST, "该类型下会员付费等级已存在");
                    } else {
                        $hasSVip = true;
                    }
                    break;
            }
        }
        // 如果类型都存在则报错
        if ($hasVip && $hasSVip) {
            throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_ERROR, "最多只能创建2个会员付费等级");
        } elseif ($hasVip && !$hasSVip) {
            // 如果只存在vip，则返回svip
            return self::LV_TYPE_SVIP;
        } else {
            return self::LV_TYPE_VIP;
        }
    }

    /**
     * 新增付费会员卡等级
     * @param array $createData
     * @return array
     */
    public function create(array $createData): array
    {
        $params = [
            "company_id" => (int)$createData["company_id"],
            "grade_name" => (string)($createData["grade_name"] ?? ""), // 等级名称
            "lv_type" => $this->getLvType((int)$createData["company_id"]), // 等级类型
            "guide_title" => (string)($createData["guide_title"] ?? ""), // 购买引导文本
            "is_default" => (int)($createData["is_default"] ?? 0), // 是否默认
            "default_grade" => false,
            "is_disabled" => (int)($createData["is_disabled"] ?? 0), // 是否禁用
            "background_pic_url" => (string)($createData["background_pic_url"] ?? ""), // 背景图
            "description" => (string)($createData["description"] ?? ""), // 详情内容
            "price_list" => [
                [
                    "name" => "monthly",
                    "price" => $createData["monthly_fee"] ?? null,
                    "day" => "30",
                    "desc" => "30天"
                ],
                [
                    "name" => "quarter",
                    "price" => $createData["quarter_fee"] ?? null,
                    "day" => "90",
                    "desc" => "90天"
                ],
                [
                    "name" => "year",
                    "price" => $createData["year_fee"] ?? null,
                    "day" => "365",
                    "desc" => "365天"
                ]
            ], // 阶段价格表
            "privileges" => [
                "discount" => 0,
                "discount_desc" => 0
            ], // 会员权益
        ];
        // 设置会员权益
        if (isset($createData["discount"])) {
            $params["privileges"] = [
                "discount_desc" => $createData["discount"],
                "discount" => 100 - intval($createData["discount"] * 10)
            ];
        }
        return parent::create($params);
    }

    /**
     * 更新详情
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        // Powered by ShopEx EcShopX
        $info = $this->find($filter);
        if (empty($info)) {
            throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_NOT_FOUND);
        }
        $params = [];
        // 等级名称
        if (isset($updateData["grade_name"])) {
            $params["grade_name"] = (string)$updateData["grade_name"];
        }
        // 购买引导文本
        if (isset($updateData["guide_title"])) {
            $params["guide_title"] = (string)$updateData["guide_title"];
        }
        // 是否默认
        if (isset($updateData["is_default"])) {
            $params["is_default"] = (int)$updateData["is_default"];
        }
        // 是否禁用
        if (isset($updateData["is_disabled"])) {
            $params["is_disabled"] = (int)$updateData["is_disabled"];
        }
        // 背景图
        if (isset($updateData["background_pic_url"])) {
            $params["background_pic_url"] = (string)$updateData["background_pic_url"];
        }
        // 详情内容
        if (isset($updateData["description"])) {
            $params["description"] = (string)$updateData["description"];
        }
        // 获取阶段价格表
        $priceList = (array)jsonDecode($info["price_list"] ?? null);
        $priceList = array_column($priceList, null, "name");
        // 购买30天付费会员的金额
        if (isset($updateData["monthly_fee"])) {
            if (isset($priceList["monthly"])) {
                $priceList["monthly"]["price"] = $updateData["monthly_fee"];
            } else {
                $priceList["monthly"] = [
                    "name" => "monthly",
                    "price" => $updateData["monthly_fee"],
                    "day" => "30",
                    "desc" => "30天"
                ];
            }
        }
        // 购买90天付费会员的金额
        if (isset($updateData["quarter_fee"])) {
            if (isset($priceList["quarter"])) {
                $priceList["quarter"]["price"] = $updateData["quarter_fee"];
            } else {
                $priceList["quarter"] = [
                    "name" => "quarter",
                    "price" => $updateData["quarter_fee"],
                    "day" => "90",
                    "desc" => "90天"
                ];
            }
        }
        // 购买365天付费会员的金额
        if (isset($updateData["year_fee"])) {
            if (isset($priceList["year"])) {
                $priceList["year"]["price"] = $updateData["year_fee"];
            } else {
                $priceList["year"] = [
                    "name" => "year",
                    "price" => $updateData["year_fee"],
                    "day" => "365",
                    "desc" => "365天"
                ];
            }
        }
        // 设置阶段价格表
        $params["price_list"] = array_values($priceList);
        // 设置会员权益
        /*        if (isset($createData["discount"])) {
                    $params["privileges"] = [
                        "discount_desc" => $createData["discount"],
                        "discount"      => 100 - intval($createData["discount"] * 10)
                    ];
                }*/
        return parent::updateDetail($filter, $params);
    }

    /**
     * 删除付费会员卡等级
     * @param array $filter
     * @return int
     */
    public function delete(array $filter): int
    {
        $info = $this->find($filter);
        if (empty($info)) {
            throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_NOT_FOUND);
        }
        if ($info["is_default"]) {
            throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_DELETE_ERROR);
        }
        // 删除时检查该付费会员等级下是否已经存在了用户，如果已经存在则如果做删除
        $result = (new MemberCardVipGradeRelUserService())->list([
            "company_id" => (int)$filter["company_id"],
            "vip_grade_id" => (int)$filter["vip_grade_id"]
        ], 1, 1, [], "user_id", false);
        if (!empty($result["list"])) {
            throw new ErrorException(ErrorCode::MEMBER_VIP_GRADE_DELETE_ERROR, "会员付费等级无法删除，该会员付费等级下扔存在关联的会员");
        }
        $this->getRepository()->deleteBy($filter);
        return 1;
    }
}
