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

namespace OpenapiBundle\Filter\Member;

use KaquanBundle\Services\MemberCardService;
use OpenapiBundle\Filter\BaseFilter;
use OpenapiBundle\Services\Member\MemberService;
use OpenapiBundle\Services\Member\MemberTagService;
use OpenapiBundle\Services\Member\MemberCardVipGradeService;
use OpenapiBundle\Services\Salesperson\ShopSalespersonService;

class MemberFilter extends BaseFilter
{
    protected $memberResult;

    public function __construct(?array $requestInputData = null, ?\stdClass $memberResult = null)
    {
        $this->memberResult = is_null($memberResult) ? new \stdClass() : $memberResult;
        parent::__construct($requestInputData);
    }

    protected function init()
    {
        // 设置企业id
        $this->memberResult->company_id = $this->filter["company_id"];

        // 有无购买记录，【0 没有】【1 有】
        if (isset($this->requestData["have_consume"])) {
            $this->filter["have_consume"] = (int)$this->requestData["have_consume"];
        }
        // 手机号
        if (isset($this->requestData["mobile"])) {
            $this->filter["mobile"] = (string)$this->requestData["mobile"];
        }
        // 来源渠道
        if (isset($this->requestData["source_from"])) {
            $this->filter["source_from"] = (string)$this->requestData["source_from"];
        }
        // 会员的状态，【0 已禁用】【1 未禁用】
        if (isset($this->requestData["status"])) {
            $this->filter["status"] = (int)$this->requestData["status"];
        }
        // 会员卡号
        if (isset($this->requestData["user_card_code"])) {
            $this->filter["user_card_code"] = (string)$this->requestData["user_card_code"];
        }
        // 商派会员Id
        if (isset($this->requestData["plat_account"])) {
            $this->filter["user_id"] = (string)$this->requestData["plat_account"];
        }

        $this->setTimeRange("created_start", "created_end");
        $this->setInviter();
        $this->setSalesperson();
        $this->setTag();
        $this->setGrade();
        $this->setVipGrade();
        $this->setStatus();
    }

    /**
     * 根据推荐信息来设置过滤条件
     */
    protected function setInviter()
    {
        if (!isset($this->requestData["inviter_mobile"])) {
            return;
        }
        // 推荐人的过滤条件
        $inviterFilter = [
            "mobile" => $this->requestData["inviter_mobile"],
            "company_id" => $this->filter["company_id"]
        ];
        // 获取推荐人信息
        $memberService = new MemberService();
        $result = $memberService->list($inviterFilter, -1, -1, [], sprintf("%s.user_id,%s.mobile,%s.username", $memberService->getAliasMembers(), $memberService->getAliasMembers(), $memberService->getAliasMembersInfo()), false);
        // 存入临时数组中
        $this->memberResult->inviter = (array)array_column($result["list"], null, "user_id");
        // 过滤推荐人id
        $this->filter["inviter_id"] = array_keys($this->memberResult->inviter);
    }

    /**
     * 根据导购信息来设置过滤条件
     */
    protected function setSalesperson()
    {
        if (!isset($this->requestData["salesperson_mobile"])) {
            return;
        }
        // 导购详情
        $salespersonInfo = (new ShopSalespersonService())
            ->find(["company_id" => $this->filter["company_id"], "mobile" => $this->requestData["salesperson_mobile"]]);
        // 获取导购id
        $this->filter["salesperson_id"] = (int)($salespersonInfo["salesperson_id"] ?? 0);
        // 临时存储
        $this->memberResult->salesperson = [0 => $salespersonInfo];
    }

    /**
     * 根据标签信息来设置过滤条件
     */
    protected function setTag()
    {
        if (isset($this->requestData["tag_id"])) {
            // 获取标签ID
            $this->filter["tag_id"] = (int)$this->requestData["tag_id"];
        } elseif (isset($this->requestData["tag_name"])) {
            // 获取标签详情
            $tagInfo = (new MemberTagService())->find([
                "tag_name" => $this->requestData["tag_name"],
                "company_id" => $this->filter["company_id"]
            ]);
            // 获取标签ID
            $this->filter["tag_id"] = (int)($tagInfo["tag_id"] ?? 0);
            // 临时存储
            $this->memberResult->tag = [
                $this->filter["tag_id"] => $tagInfo
            ];
        } else {
            return;
        }
    }

    /**
     * 根据会员等级信息来设置过滤条件
     */
    protected function setGrade()
    {
        if (isset($this->requestData["grade_id"])) {
            // 获取会员等级ID
            $this->filter["grade_id"] = (int)$this->requestData["grade_id"];
        } elseif (isset($this->requestData["grade_name"])) {
            // 获取会员等级
            $gradeInfo = (new MemberCardService())->getGradeInfo([
                "company_id" => $this->filter["company_id"],
                "grade_name" => $this->requestData["grade_name"]
            ]);
            // 获取会员等级ID
            $this->filter["grade_id"] = (int)($gradeInfo["grade_id"] ?? 0);
            // 临时存储
            $this->memberResult->grade = [
                $this->filter["grade_id"] => $gradeInfo
            ];
        } else {
            return;
        }
    }

    /**
     * 根据付费会员等级信息来设置过滤条件
     */
    protected function setVipGrade()
    {
        if (isset($this->requestData["vip_grade_id"])) {
            // 获取付费会员等级ID
            $this->filter["vip_grade_id"] = (int)$this->requestData["vip_grade_id"];
        } elseif (isset($this->requestData["vip_grade_name"])) {
            // 获取付费会员等级
            $vipGradeInfo = (new MemberCardVipGradeService())->find([
                "company_id" => $this->filter["company_id"],
                "grade_name" => $this->requestData["vip_grade_name"],
                "is_disabled" => 1
            ]);
            // 获取付费会员等级ID
            $this->filter["vip_grade_id"] = (int)($vipGradeInfo["grade_id"] ?? 0);
            // 临时存储
            $this->memberResult->vip_grade = [
                $this->filter["vip_grade_id"] => $vipGradeInfo
            ];
        } else {
            return;
        }
    }

    /**
     * 根据用户状态来设置过滤条件
     * @param array $filter 外部其他逻辑的过滤条件
     * @param int $status 用户状态
     */
    protected function setStatus()
    {
        if (!isset($this->requestData["status"])) {
            return;
        }
        switch ((int)$this->requestData["status"]) {
            case 0:
                $this->filter["disabled"] = 1;
                break;
            case 1:
                $this->filter["disabled"] = 0;
                break;
            default:
                $this->filter["disabled"] = (int)$this->requestData["status"];
        }
    }
}
