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

use Carbon\Carbon;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Exceptions\ErrorException;
use MembersBundle\Entities\MembersInfo;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Data\MemberOperateLogData;
use OpenapiBundle\Services\BaseService;

class MemberInfoService extends BaseService
{
    public function getEntityClass(): string
    {
        return MembersInfo::class;
    }

    /**
     * 性别的映射关键
     */
    public const SEX_MAP = [
        0 => "未知",
        1 => "男",
        2 => "女",
    ];

    /**
     * 学历的映射关键
     */
    public const EDU_BACKGROUND_MAP = [
        0 => "硕士及以上",
        1 => "本科",
        2 => "大专",
        3 => "高中/中专及以下",
        4 => "其他",
    ];

    /**
     * 学历的映射关键
     */
    public const INCOME_MAP = [
        0 => "5万以下",
        1 => "5万 ~ 15万",
        2 => "15万 ~ 30万",
        3 => "30万以上",
        4 => "其他",
    ];

    /**
     * 行业的映射关键
     */
    public const INDUSTRY_MAP = [
        0 => "金融/银行/投资",
        1 => "计算机/互联网",
        2 => "媒体/出版/影视/文化",
        3 => "政府/公共事业",
        4 => "房地产/建材/工程",
        5 => "咨询/法律",
        6 => "加工制造",
        7 => "教育培训",
        8 => "医疗保健",
        9 => "运输/物流/交通",
        10 => "零售/贸易",
        11 => "旅游/度假",
        12 => "其他"
    ];

    /**
     * 验证性别
     * @param int $sex 性别的值 【0 未知】【1 男】【2 女】
     * @return $this
     */
    protected function checkSex(int &$sex): self
    {
        if (!isset(self::SEX_MAP[$sex])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "性别填写错误");
        }
        return $this;
    }

    /**
     * 验证生日格式 【yyyy-mm-dd】
     * @param string $birthday 生日日期
     * @return $this
     */
    protected function checkBirthDay(string &$birthday): self
    {
        try {
            $birthday = Carbon::parse($birthday)->toDateString();
        } catch (\Exception $exception) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "生日格式填写错误");
        }
        return $this;
    }

    /**
     * 检查的爱好有误
     * @param array|string $habbit 爱好
     * @return $this
     */
    protected function checkHabbit(&$habbit): self
    {
        $habbit = (array)jsonDecode($habbit);
        foreach ($habbit as &$datum) {
            if (!isset($datum["name"]) || !isset($datum["ischecked"])) {
                throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "爱好填写错误");
            }
            if (is_string($datum["ischecked"])) {
                $datum["ischecked"] = $datum["ischecked"] === "true" ? true : false;
            } else {
                $datum["ischecked"] = (bool)$datum["ischecked"];
            }
        }
        return $this;
    }

    /**
     * 验证学历
     * @param int $eduBackground
     * @return $this
     */
    protected function checkEduBackground(int &$eduBackground)
    {
        if (!isset(self::EDU_BACKGROUND_MAP[$eduBackground])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "学历填写错误");
        }
        return $this;
    }

    /**
     * 验证年收入
     * @param int $income
     * @return $this
     */
    protected function checkIncome(int &$income)
    {
        if (!isset(self::INCOME_MAP[$income])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "年收入填写错误");
        }
        return $this;
    }

    /**
     * 验证年收入
     * @param int $income
     * @return $this
     */
    protected function checkIndustry(int &$industry)
    {
        if (!isset(self::INDUSTRY_MAP[$industry])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "行业填写错误");
        }
        return $this;
    }

    public function create(array $createData): array
    {
        $data = [
            "user_id" => (int)($createData["user_id"] ?? 0),
            "company_id" => (int)($createData["company_id"] ?? 0),
            "username" => (string)($createData["username"] ?? ""), // 姓名
            "avatar" => (string)($createData["avatar"] ?? ""), // 头像
            "sex" => (int)($createData["sex"] ?? 0), // 性别。0 未知 1 男 2 女
            "birthday" => (string)($createData["birthday"] ?? ""), // 生日
            "address" => (string)($createData["address"] ?? []), // 地址
            "email" => (string)($createData["email"] ?? ""), // email
            "industry" => (int)($createData["industry"] ?? 0), // 行业
            "income" => (int)($createData["income"] ?? 0), // 年收入
            "edu_background" => (int)($createData["edu_background"] ?? 0), // 学历
            "habbit" => (array)($createData["habbit"] ?? []), // 爱好
            "have_consume" => null,
            "other_params" => json_encode([]),
        ];
        $this->checkSex($data["sex"])
            ->checkBirthDay($data["birthday"])
            ->checkHabbit($data["habbit"])
            ->checkEduBackground($data["edu_background"])
            ->checkIncome($data["income"])
            ->checkIndustry($data["industry"]);
        // 插入数据
        return parent::create($data);
    }

    /**
     * 更新会员详情
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        if (empty($filter)) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS);
        }
        // 定义实际的更新数据
        $actualUpdateData = [];
        // 设置姓名
        if (isset($updateData["username"])) {
            $actualUpdateData["username"] = (string)$updateData["username"];
        }
        // 设置头像
        if (isset($updateData["avatar"])) {
            $actualUpdateData["avatar"] = (string)$updateData["avatar"];
        }
        // 设置性别，【0 未知】【1 男】【2 女】
        if (isset($updateData["sex"])) {
            $actualUpdateData["sex"] = (int)$updateData["sex"];
            $this->checkSex($actualUpdateData["sex"]);
        }
        // 设置生日
        if (isset($updateData["birthday"])) {
            $actualUpdateData["birthday"] = (string)$updateData["birthday"];
            $this->checkBirthDay($actualUpdateData["birthday"]);
        }
        // 设置爱好
        if (isset($updateData["habbit"])) {
            $actualUpdateData["habbit"] = $updateData["habbit"];
            $this->checkHabbit($actualUpdateData["habbit"]);
        }
        // 设置学历
        if (isset($updateData["edu_background"])) {
            $actualUpdateData["edu_background"] = (int)$updateData["edu_background"];
            $this->checkEduBackground($actualUpdateData["edu_background"]);
        }
        // 设置年收入
        if (isset($updateData["income"])) {
            $actualUpdateData["income"] = (int)$updateData["income"];
            $this->checkIncome($actualUpdateData["income"]);
        }
        // 设置行业
        if (isset($updateData["industry"])) {
            $actualUpdateData["industry"] = (int)$updateData["industry"];
            $this->checkIndustry($actualUpdateData["industry"]);
        }
        // 设置邮箱
        if (isset($updateData["email"])) {
            $actualUpdateData["email"] = (string)$updateData["email"];
        }
        // 设置地址
        if (isset($updateData["address"])) {
            $actualUpdateData["address"] = (string)$updateData["address"];
        }
        if (empty($actualUpdateData)) {
            return [];
        }
        $oldUserInfo = [];
        try {
            $newUserInfo = $this->getRepository()->updateOneBy($filter, $actualUpdateData, $oldUserInfo);
        } catch (ResourceException $resourceException) {
            throw new ErrorException(ErrorCode::MEMBER_NOT_FOUND);
        }
        // 设置新老数据
        MemberOperateLogData::instance()->register($newUserInfo, $oldUserInfo);
        return $newUserInfo;
    }

    public function list(array $filter, int $page = 1, int $pageSize = 100, array $orderBy = ["user_id" => "DESC"], string $cols = "*", bool $needCountSql = true): array
    {
        $result = $this->getRepository()->lists($filter, $orderBy, $pageSize, $page);
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }
}
