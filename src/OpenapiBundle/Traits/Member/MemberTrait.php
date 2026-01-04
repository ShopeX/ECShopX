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

namespace OpenapiBundle\Traits\Member;

use Carbon\Carbon;
use DepositBundle\Services\DepositTrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use MembersBundle\Services\MemberTagsService;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Member\MemberInfoService;
use OpenapiBundle\Services\Member\MemberService;
use OpenapiBundle\Services\Member\PointService;
use OpenapiBundle\Services\Member\MemberCardVipGradeRelUserService;
use OpenapiBundle\Services\Salesperson\WorkWechatRelService;
use OpenapiBundle\Services\Salesperson\ShopSalespersonService;
use KaquanBundle\Services\VipGradeService;

/**
 * Trait MemberTrait
 * @package OpenapiBundle\Traits\Controller
 * @property
 */
trait MemberTrait
{
    /**
     * 获取列表方法的请求数据
     * @param Request $request
     * @return array
     */
    protected function getListRequestData(Request $request): array
    {
        $requestData = [];
        foreach ([
                     // 推荐相关的
                     "inviter_id" => null, // 推荐人id
                     "inviter_mobile" => null, // 推荐人手机号
                     // 导购相关的
                     "salesperson_id" => null, // 导购id
                     "salesperson_mobile" => null, // 导购手机号
                     // 标签相关的
                     "tag_id" => null, // 标签id
                     "tag_name" => null, // 标签名字
                     // 会员等级相关的
                     "grade_id" => null, // 会员等级id
                     "grade_name" => null, // 会员等级名称
                     // 付费会员等级相关的
                     "vip_grade_id" => null, // 付费会员等级id
                     "vip_grade_name" => null, // 付费会员等级名称
                     // 会员详情相关
                     "have_consume" => null, // 有无购买记录，【0 没有】【1 有】
                     // 基本信息相关的
                     "mobile" => null, // 手机号
                     "source_from" => null, // 来源渠道
                     "status" => null, // 会员的状态，【0 已禁用】【1 未禁用】
                     "card_code" => null, // 会员卡号
                     "start_date" => null, // 会员创建时间 >> 开始时间
                     "end_date" => null, // 会员创建时间 >> 结束时间
                 ] as $requestKey => $alias) {
            if ($request->has($requestKey)) {
                $requestData[is_null($alias) ? $requestKey : $alias] = $request->input($requestKey);
            }
        }
        return $requestData;
    }

    /**
     * 临时存储会员相关的信息
     * @var \stdClass
     * inviter 推荐人
     * salesperson 导购人
     * tag 标签
     * rel_tag 会员与标签的关联数据
     * grade 会员等级
     * vip_grade 付费会员等级
     */
    protected $memberResult = null;

    public function initMemberResult()
    {
        $this->memberResult = new \stdClass();
    }

    /**
     * 追加推荐人信息至会员列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendInviterToList(int $companyId, array &$list)
    {
        // 如果不存在推荐信息
        if (is_null($this->memberResult) || !property_exists($this->memberResult, "inviter") || !is_array($this->memberResult->inviter)) {
            $inviterIds = (array)array_column($list, "inviter_id");
            if (!empty($inviterIds)) {
                // 获取推荐人信息
                $memberService = new MemberService();
                $result = $memberService->list(["company_id" => $companyId, "user_id" => $inviterIds], 1, count($inviterIds), [], sprintf("%s.user_id,%s.mobile,%s.username", $memberService->getAliasMembers(), $memberService->getAliasMembers(), $memberService->getAliasMembersInfo()), false);
                $this->memberResult->inviter = (array)array_column($result["list"], null, "user_id");
            }
        }
        // 追加数据
        foreach ($list as &$item) {
            $inviterId = $item["inviter_id"] ?? 0;
            $item["inviter"] = [
                "id" => $inviterId,
                "name" => $this->memberResult->inviter[$inviterId]["username"] ?? "",
                "mobile" => $this->memberResult->inviter[$inviterId]["mobile"] ?? "",
            ];
            unset($item["inviter_id"]);
        }
    }

    /**
     * 追加导购信息至会员列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendSalespersonToList(int $companyId, array &$list)
    {
        // 是否基于导购来做的过滤
        $bySalespersonFilter = false;
        // 如果不存在导购信息
        if (!property_exists($this->memberResult, "salesperson") || !is_array($this->memberResult->salesperson)) {
            $userIds = (array)array_column($list, "user_id");
            if (!empty($userIds)) {
                $relResult = (new WorkWechatRelService())->list(["company_id" => $companyId, "user_id" => $userIds, "is_bind" => 1], 1, count($userIds), [], "user_id,salesperson_id");
                $salespersonIds = (array)array_column($relResult["list"], "salesperson_id", "user_id");
                // 如果存在导购信息则进一步去查询导购详情
                if (!empty($salespersonIds)) {
                    $salespersonResult = (new ShopSalespersonService())->list(["company_id" => $companyId, "salesperson_id" => array_values($salespersonIds)], -1, -1, [], "*", false);
                    $salespersons = (array)array_column($salespersonResult["list"], null, "salesperson_id");
                    foreach ($salespersonIds as $userId => $salespersonId) {
                        $this->memberResult->salesperson[$userId] = $salespersons[$salespersonId] ?? [];
                    }
                }
            }
        } else {
            $bySalespersonFilter = true;
        }
        // 追加数据
        foreach ($list as &$item) {
            if ($bySalespersonFilter) {
                $userId = 0;
            } else {
                $userId = $item["user_id"] ?? 0;
            }
            $item["salesperson"] = [
                "id" => $this->memberResult->salesperson[$userId]["salesperson_id"] ?? 0,
                "name" => $this->memberResult->salesperson[$userId]["name"] ?? "",
                "mobile" => $this->memberResult->salesperson[$userId]["mobile"] ?? "",
            ];
            unset($item["salesperson_id"]);
        }
    }

    /**
     * 追加标签信息至会员列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendTagToList(int $companyId, array &$list)
    {
        $relTagWithUserIdArray = [];
        $userIds = (array)array_column($list, "user_id");
        if (!empty($userIds)) {
            $relTagList = (new MemberTagsService())->getUserRelTagList(["company_id" => $companyId, "user_id" => $userIds], ["user_id", "tag_id", "tag_name"]);
            foreach ($relTagList as $relTag) {
                $relTagWithUserIdArray[$relTag["user_id"] ?? 0][] = [
                    "id" => $relTag["tag_id"] ?? 0,
                    "name" => $relTag["tag_name"] ?? ""
                ];
            }
        }

        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["tags"] = $relTagWithUserIdArray[$userId] ?? [];
        }
    }

    /**
     * 追加付费会员信息至会员列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendVipGradeToList(int $companyId, array &$list)
    {
        $time = time();
        $relVipGradeWithUserIdArray = [];
        $userIds = (array)array_column($list, "user_id");
        if (!empty($userIds)) {
            $result = (new MemberCardVipGradeRelUserService())->list(["company_id" => $companyId, "user_id" => $userIds], 1, 100);
            $relVipGradeList = $result["list"];
            if ($relVipGradeList) {
                $vipGradeService = new VipGradeService();
                $vipGrade = $vipGradeService->lists(["company_id" => $companyId]);
                $vipGradeName = array_column($vipGrade, "grade_name", "vip_grade_id");
            }
            foreach ($relVipGradeList as $relVipGrade) {
                $endDate = $relVipGrade["end_date"] ?? 0;
                if ($endDate < $time) {
                    continue;
                }
                $relVipGradeWithUserIdArray[$relVipGrade["user_id"] ?? 0][] = [
                    "id" => $relVipGrade["vip_grade_id"] ?? 0,
                    "type" => $relVipGrade["vip_type"] ?? "",
                    "end_date" => $relVipGrade["end_date"] ?? 0,
                    "grade_nme" => $vipGradeName[$relVipGrade["vip_grade_id"] ?? 0] ?? "",
                ];
            }
        }

        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["vip_grades"] = $relVipGradeWithUserIdArray[$userId] ?? [];
        }
    }

    /**
     * 追加当前积分至会员列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendPointToList(int $companyId, array &$list)
    {
        $pointWithUserIdArray = [];
        $userIds = (array)array_column($list, "user_id");
        if (!empty($userIds)) {
            $result = (new PointService())->list(["company_id" => $companyId, "user_id" => $userIds], 1, count($userIds), [], "user_id,point", false);
            $pointWithUserIdArray = (array)array_column($result["list"], null, "user_id");
        }
        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["point"] = (int)($pointWithUserIdArray[$userId]["point"] ?? 0);
        }
    }

    /**
     * 追加用户储值的信息至列表中
     * @param int $companyId 企业id
     * @param array $list 列表数据
     */
    protected function appendDepositToList(int $companyId, array &$list)
    {
        $depositTradeService = new DepositTrade();
        $depositWithUserIdArray = [];
        $userIds = (array)array_column($list, "user_id");
        if (!empty($userIds)) {
            // 获取这些用户的储蓄充值总额
            $result = $depositTradeService->getDepositTradeRechargeCount($userIds);
            $depositWithUserIdArray = (array)array_column($result, "money_sum", "user_id");
        }
        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["deposit"] = [
                // 储值余额
                "have" => (string)bcdiv($depositTradeService->getUserDepositTotal($companyId, $userId), 100, 2),
                // 储值总额（只包含充值的）
                "total" => (string)bcdiv($depositWithUserIdArray[$userId] ?? 0, 100, 2)
            ];
        }
    }

    /**
     * 追加用户信息到列表中
     * @param int $companyId
     * @param array $list
     */
    protected function appendInfoToList(int $companyId, array &$list, bool $keepUserId = false)
    {
        // 获取用户id
        $userIds = (array)array_column($list, "user_id");
        $userIds = array_unique($userIds);
        // 用户的列表数据
        $memberList = [];
        if (!empty($userIds)) {
            $memberService = new MemberService();
            $result = $memberService->list(["company_id" => $companyId, "user_id" => $userIds], 1, count($userIds), [], sprintf("%s.user_id,%s.mobile", $memberService->getAliasMembers(), $memberService->getAliasMembers()));
            $memberList = (array)array_column($result["list"], "mobile", "user_id");
        }

        // 填充参数
        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["mobile"] = (string)($memberList[$userId] ?? "");
            if (!$keepUserId) {
                unset($item["user_id"]);
            }
        }
    }

    /**
     * 追加用户详情到列表中
     * @param int $companyId
     * @param array $list
     */
    protected function appendDetailToList(int $companyId, array &$list, bool $keepUserId = false)
    {
        // 获取用户id
        $userIds = (array)array_column($list, "user_id");
        $userIds = array_unique($userIds);
        // 用户的列表数据
        $memberList = [];
        if (!empty($userIds)) {
            $memberService = new MemberInfoService();
            $result = $memberService->list(["company_id" => $companyId, "user_id" => $userIds], -1, -1, ["user_id" => "DESC"], "user_id,username");
            $memberList = (array)array_column($result["list"], "username", "user_id");
        }

        // 填充参数
        foreach ($list as &$item) {
            $userId = $item["user_id"] ?? 0;
            $item["username"] = (string)($memberList[$userId] ?? "");
            if (!$keepUserId) {
                unset($item["user_id"]);
            }
        }
    }

    /**
     * 处理时间格式
     * @param int $companyId
     * @param array $list
     */
    protected function handleDataToList(int $companyId, array &$list)
    {
        foreach ($list as &$data) {
            // 时间戳转时间格式
            if (isset($data["created"])) {
                $data["created"] = Carbon::createFromTimestamp($data["created"])->toDateTimeString();
            }
            if (isset($data["updated"])) {
                $data["updated"] = Carbon::createFromTimestamp($data["updated"])->toDateTimeString();
            }
            // 爱好转成数组结构
            if (isset($data["habbit"])) {
                $data["habbit"] = array_values((array)jsonDecode($data["habbit"] ?? []));
                foreach ($data["habbit"] as &$datum) {
                    $datum["ischecked"] = $datum["ischecked"] === "true" ? true : false;
                }
            }
            // 客户端显示的status状态
            if (isset($data["disabled"])) {
                switch ((int)$data["disabled"]) {
                    case 0:
                        $data["status"] = 1;
                        break;
                    case 1:
                        $data["status"] = 0;
                        break;
                    default:
                        $data["status"] = null;
                }
                unset($data["disabled"]);
            }
            // 对映射字段做过滤
            foreach ([
                         "sex" => MemberInfoService::SEX_MAP,
                         "edu_background" => MemberInfoService::EDU_BACKGROUND_MAP,
                         "income" => MemberInfoService::INCOME_MAP,
                         "industry" => MemberInfoService::INDUSTRY_MAP,
                     ] as $key => $map) {
                // 不存在该字段就跳过，不做强制处理
                if (!isset($data[$key])) {
                    continue;
                }
                if (isset($map[$data[$key]])) {
                    // 存在则做强类型转换
                    $data[$key] = (int)$data[$key];
                } else {
                    // 不存在就去找value（对新老数据做兼容）
                    $result = array_search($data[$key], $map, true);
                    $data[$key] = $result === false ? null : $result;
                }
                // 映射字段的描述内容
                // $data[sprintf("%s_desc", $key)] = $map[$data[$key]] ?? null;
            }
        }
    }

    /**
     * 检查创建时的formData参数并整理参数
     * @param Request $request 请求体
     * @return array 整理好的参数
     */
    protected function checkCreateFormDataAndHandle(Request $request): array
    {
        $requestData = $request->all();
        // 将多个标签名做拆分
        if (isset($requestData["tag_names"])) {
            $requestData["tag_names"] = (array)explode(",", (string)$requestData["tag_names"]);
        }
        // 将多个标签id做拆分
        if (isset($requestData["tag_ids"])) {
            $requestData["tag_ids"] = (array)explode(",", (string)$requestData["tag_ids"]);
        }
        // 将爱好的json内容转成数组
        if (isset($requestData["habbit"])) {
            $requestData["habbit"] = (array)jsonDecode($requestData["habbit"]);
        }
        // 参数验证
        if ($messageBag = validation($requestData, [
            "mobile" => ["required"],
            "source_from" => ["nullable", Rule::in(array_keys(MemberService::SOURCE_FROM_MAP))],
            "inviter_mobile" => ["nullable", new MobileRule()],
            "salesperson_mobile" => ["nullable", new MobileRule()],
            "union_id" => ["nullable", "string"],
            "status" => ["nullable", Rule::in([0, 1])],
            "tag_names" => ["nullable"],
            "tag_ids" => ["nullable"],
            "card_code" => ["nullable", "string"],
            "grade_id" => ["nullable", "integer", "min:0"],
            "username" => ["nullable", "string"],
            "avatar" => ["nullable", "string"],
            "sex" => ["nullable", Rule::in(key(MemberInfoService::SEX_MAP))],
            "birthday" => ["nullable", "date"],
            "habbit" => ["nullable", "array"],
            "habbit.*.name" => ["required", "string"],
            "habbit.*.ischecked" => ["required", Rule::in(["false", "true", false, true])],
            "edu_background" => ["nullable", Rule::in(MemberInfoService::EDU_BACKGROUND_MAP)],
            "income" => ["nullable", Rule::in(MemberInfoService::INCOME_MAP)],
            "industry" => ["nullable", Rule::in(MemberInfoService::INDUSTRY_MAP)],
            "email" => ["nullable", "email"],
            "address" => ["nullable", "string"],
            "remarks" => ["nullable", "string"],
        ], [
            "mobile.*" => "手机号必填",
            "source_from.*" => "来源渠道填写错误",
            "inviter_mobile.*" => "推荐人手机号填写错误",
            "salesperson_mobile.*" => "绑定导购手机号填写错误",
            "union_id.*" => "微信unionid填写错误",
            "status.*" => "会员状态填写错误",
            "tag_names.*" => "会员标签名填写错误",
            "tag_ids.*" => "会员标签ID填写错误",
            "card_code.*" => "会员卡号填写错误",
            "grade_id.*" => "会员等级ID填写错误",
            "username.*" => "姓名/昵称填写错误",
            "avatar.*" => "头像url填写错误",
            "sex.*" => "性别填写错误",
            "birthday.*" => "生日填写错误",
            "habbit.*" => "爱好填写错误",
            "edu_background.*" => "学历填写错误",
            "income.*" => "年收入填写错误",
            "industry.*" => "行业填写错误",
            "email.*" => "email填写错误",
            "address.*" => "地址填写错误",
            "remarks.*" => "备注填写错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 拼接参数
        $requestData = [
            // 会员信息
            "mobile" => (string)$requestData["mobile"], // 手机号
            "source_from" => (string)($requestData["source_from"] ?? "openapi"), // 来源渠道
            "inviter_mobile" => (string)($requestData["inviter_mobile"] ?? ""), // 推荐人手机号
            "salesperson_mobile" => (string)($requestData["salesperson_mobile"] ?? ""), // 导购手机号
            "union_id" => (string)($requestData["union_id"] ?? ""), // 微信的unionid
            "status" => (int)($requestData["status"] ?? 1), // 会员的状态，【0 已禁用】【1 未禁用】
            // 会员标签
            "tag_name" => (array)($requestData["tag_names"] ?? null), // 标签名
            "tag_id" => (array)($requestData["tag_id"] ?? null), // 标签id
            // 会员卡与等级
            "card_code" => (string)($requestData["card_code"] ?? ""), // 会员卡号
            "grade_id" => (int)($requestData["grade_id"] ?? -1), // 会员等级id
            // 会员基础信息
            "username" => (string)($requestData["username"] ?? ""), // 姓名
            // "nickname"              => (string)($requestData["nickname"], // 昵称
            "avatar" => (string)($requestData["avatar"] ?? ""), // 头像url
            "sex" => (string)($requestData["sex"] ?? 0), // 性别，【0 未知】【1 男】【2 女】
            "birthday" => (string)($requestData["birthday"] ?? ""), // 生日，日期格式 2021-06-16 15:35:41
            "habbit" => (array)($requestData["habbit"] ?? null), // 爱好
            "edu_background" => (int)($requestData["edu_background"] ?? 4), // 学历
            "income" => (int)($requestData["income"] ?? 4), // 年收入
            "industry" => (int)($requestData["industry"] ?? 12), // 行业
            "email" => (string)($requestData["email"] ?? ""), // email
            "address" => (string)($requestData["address"] ?? ""), // 地址
            "remarks" => (string)($requestData["remarks"] ?? ""), // 备注
        ];
        // 检查手机格式
        $this->checkMobile($request, $requestData);
        // 标签参数的值转换
        foreach (["tag_name", "tag_id"] as $key) {
            if (is_null($requestData[$key])) {
                $requestData[$key] = [];
            } elseif (is_array($requestData[$key])) {
                continue;
            } else {
                $requestData[$key] = (array)explode(",", (string)$requestData[$key]);
            }
        }
        return $requestData;
    }

    /**
     * 验证手机号
     * @param Request $request 请求体
     * @param array $filter
     */
    protected function checkMobile(Request $request, array &$filter)
    {
        // 获取会员手机号
        $mobile = $request->input("mobile");
        // 对手机号做验证
        $rule = new MobileRule();
        if (!$rule->passes("mobile", $mobile)) {
            throw new ErrorException(ErrorCode::MEMBER_EXIST, "会员手机号已存在");
        }
        // 设置手机号到过滤条件中
        $filter["mobile"] = $mobile;
    }
}
