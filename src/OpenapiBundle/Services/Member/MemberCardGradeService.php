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

use KaquanBundle\Entities\MemberCardGrade;
use KaquanBundle\Services\MemberCardService;
use OpenapiBundle\Constants\CommonConstant;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Services\BaseService;

class MemberCardGradeService extends BaseService
{
    public function getEntityClass(): string
    {
        // ShopEx EcShopX Business Logic Layer
        return MemberCardGrade::class;
    }

    public function list(array $filter, int $page = 1, int $pageSize = CommonConstant::DEFAULT_PAGE_SIZE, array $orderBy = [], string $cols = "*", bool $needCountSql = true): array
    {
        $result = [
            "total_count" => $needCountSql ? $this->getRepository()->count($filter) : 0,
            "list" => $this->getRepository()->getList($cols, $filter, ($page - 1) * $pageSize, $pageSize, $orderBy),
        ];
        $this->handlerListReturnFormat($result, $page, $pageSize);
        return $result;
    }

    /**
     * 检查是否达到上限
     * @param int $companyId
     * @param array $list
     * @return $this
     */
    protected function checkUpperLimit(int $companyId, array &$list): self
    {
        $list = $this->getRepository()->getList("grade_id", ["company_id" => $companyId], 0, -1);
        if (count($list) >= 6) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_ERROR, "最多只能创建5个会员等级");
        }
        return $this;
    }

    /**
     * 创建会员卡等级
     * @param array $createData
     * @return array
     */
    public function create(array $createData): array
    {
        // 拼装数据
        $params = [
            // 企业id
            "company_id" => (int)$createData["company_id"],
            // 会员卡等级名称
            "grade_name" => (string)($createData["grade_name"] ?? ""),
            // 等级卡背景图
            "background_pic_url" => (string)($createData["background_pic_url"] ?? ""),
            // 外部唯一标识，外部调用方自定义的值
            "external_id" => (string)($createData["external_id"] ?? ""),
            // 会员权益
            "privileges" => [],
            // 升级条件
            "promotion_condition" => [],
            // 是否默认等级
            "default_grade" => $this->getRepository()->count(["company_id" => $createData["company_id"]]) === 0
        ];
        // 会员权益
        if (isset($createData["discount"]) && is_numeric($createData["discount"])) {
            $params["privileges"] = [
                "discount_desc" => $createData["discount"],
                "discount" => 100 - intval($createData["discount"] * 10)
            ];
        }
        // 升级条件
        if (isset($createData["total_consumption"]) && is_numeric($createData["total_consumption"])) {
            $params["promotion_condition"] = [
                "total_consumption" => $createData["total_consumption"],
            ];
        }
        $params["grade_id"] = 0;
        $params["company_id"] = (int)$createData["company_id"];
        $params["default_grade"] = MemberCardService::DEFAULT_GRADE_NO;

        // 数据验证
        $list = [];
        $this->checkUpperLimit($params["company_id"], $list);

        $newList = [];
        $this->getRepository()->update($params["company_id"], [$params], [], $newList);
        return (array)array_shift($newList);
    }

    /**
     * 更新会员卡等级
     * @param array $filter
     * @param array $updateData
     * @return array
     */
    public function updateDetail(array $filter, array $updateData): array
    {
        $info = $this->find($filter);
        if (empty($info)) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_NOT_FOUND);
        }
        $params = [];
        // 会员卡等级名称
        if (isset($updateData["grade_name"])) {
            $params["grade_name"] = (string)$updateData["grade_name"];
        }
        // 等级卡背景图
        if (isset($updateData["background_pic_url"])) {
            $params["background_pic_url"] = (string)$updateData["background_pic_url"];
        }
        // 等级卡背景图
        if (isset($updateData["external_id"])) {
            $params["external_id"] = (string)$updateData["external_id"];
        }
        // 会员权益
        if (isset($updateData["discount"]) && is_numeric($updateData["discount"])) {
            $params["privileges"] = [
                "discount_desc" => $updateData["discount"],
                "discount" => 100 - intval($updateData["discount"] * 10)
            ];
        }
        // 升级条件
        if (isset($updateData["total_consumption"]) && is_numeric($updateData["total_consumption"])) {
            $params["promotion_condition"] = [
                "total_consumption" => $updateData["total_consumption"],
            ];
        }
        if (empty($params)) {
            return [];
        }
        $params = array_merge($filter, $params);
        $newList = [];
        $this->getRepository()->update($params["company_id"], [$params], [], $newList);
        return (array)array_shift($newList);
    }

    /**
     * 删除会员卡等级
     * @param array $filter
     * @return int
     */
    public function delete(array $filter): int
    {
        // 删除时检查该会员等级下是否已经存在了用户，如果已经存在则如果做删除
        $result = (new MemberService())->list(["company_id" => (int)$filter["company_id"], "grade_id" => (int)$filter["grade_id"]], 1, 1, [], "*", false);
        if (!empty($result["list"])) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_DELETE_ERROR, "会员等级无法删除，该会员等级下扔存在关联的会员");
        }
        $this->getRepository()->update((int)$filter["company_id"], [], [(int)$filter["grade_id"]]);
        return 1;
    }

    /**
     * 批量保存会员等级（数云同步等）：按 grade_level 升序；最低级为默认等级。
     * $data[] 约定：grade_id = 外部/数云侧稳定 ID（写入 membercard_grade.external_id），grade_level = 层级序号（如数云 grades[].id），grade_name = 展示名。
     * 已存在相同 external_id 则更新该行；否则新增。删除：除默认等级与本次仍占用的本地 grade_id 外，其余非默认等级一律删除（含无 external_id 的手工等级）；删除前仍校验会员占用。
     *
     * @param  array<string, mixed>  $options  preserve_promotion_condition_on_update=true 时，更新已有行保留 promotion_condition
     */
    public function batchSave($companyId, $data, array $options = [])
    {
        $companyId = (int) $companyId;
        $preservePromotionConditionOnUpdate = ! empty($options['preserve_promotion_condition_on_update']);
        if ($data === []) {
            return $this->getRepository()->getList('*', ['company_id' => $companyId], 0, -1);
        }
        usort($data, function ($a, $b) {
            return $a['grade_level'] <=> $b['grade_level'];
        });
        $memberCardService = new MemberCardService();

        $externalIds = [];
        foreach ($data as $row) {
            $eid = (int) ($row['grade_id'] ?? 0);
            if ($eid > 0) {
                $externalIds[] = $eid;
            }
        }
        $externalIds = array_values(array_unique($externalIds));

        $conn = app('registry')->getConnection('default');
        $curLists = [];
        if ($externalIds !== []) {
            $idsSql = implode(',', array_map('intval', $externalIds));
            $sql = 'SELECT * FROM membercard_grade WHERE company_id = '.$companyId
                .' AND default_grade = '.(int) $memberCardService::DEFAULT_GRADE_NO
                .' AND external_id IN ('.$idsSql.')';
            $curLists = $conn->fetchAll($sql);
        }
        $curExternalLists = [];
        foreach ($curLists as $row) {
            $extKey = (string) ($row['external_id'] ?? '');
            if ($extKey !== '') {
                $curExternalLists[$extKey] = $row;
            }
        }

        $defaultGrade = $this->getRepository()->getInfo(['company_id' => $companyId, 'default_grade' => $memberCardService::DEFAULT_GRADE_YES]);
        if (empty($defaultGrade)) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_ERROR, '缺少默认会员等级，无法同步。');
        }

        $gradeInfo = [];
        foreach ($data as $key => $item) {
            $externalKey = (string) (int) ($item['grade_id'] ?? 0);
            $gradeLevel = (int) ($item['grade_level'] ?? 0);
            $existingPromotionCondition = null;
            if ($key === 0) {
                $existingPromotionCondition = $defaultGrade['promotion_condition'] ?? null;
            } elseif ($externalKey !== '' && isset($curExternalLists[$externalKey])) {
                $existingPromotionCondition = $curExternalLists[$externalKey]['promotion_condition'] ?? null;
            }
            $grade = [
                'company_id' => $companyId,
                'grade_name' => $item['grade_name'],
                'privileges' => ['discount' => '10'],
                'promotion_condition' => MemberCardGradeBatchSavePromotionConditionResolver::resolve(
                    $gradeLevel,
                    $preservePromotionConditionOnUpdate,
                    $existingPromotionCondition,
                ),
                'external_id' => $externalKey,
                'default_grade' => $memberCardService::DEFAULT_GRADE_NO,
            ];
            if ($key === 0) {
                $grade['default_grade'] = $memberCardService::DEFAULT_GRADE_YES;
                $grade['grade_id'] = $defaultGrade['grade_id'];
            } elseif ($externalKey !== '' && isset($curExternalLists[$externalKey])) {
                $grade['grade_id'] = $curExternalLists[$externalKey]['grade_id'];
            } else {
                $grade['grade_id'] = '';
            }
            $gradeInfo[] = $grade;
        }

        $keepGradeIds = [(int) $defaultGrade['grade_id']];
        foreach ($gradeInfo as $g) {
            $gid = $g['grade_id'] ?? '';
            if ($gid !== '' && $gid !== null && is_numeric($gid)) {
                $keepGradeIds[] = (int) $gid;
            }
        }
        $keepGradeIds = array_values(array_unique($keepGradeIds));

        $curList = $memberCardService->getGradeListByCompanyId($companyId);
        $deleteIds = [];
        foreach ($curList as $g) {
            $isDefault = $g['default_grade'] === true || $g['default_grade'] === '1' || $g['default_grade'] === 1;
            if ($isDefault) {
                continue;
            }
            $gid = (int) $g['grade_id'];
            if (!in_array($gid, $keepGradeIds, true)) {
                $deleteIds[] = $gid;
            }
        }

        $noDeleteMsg = [];
        $_curList = array_column($curList, null, 'grade_id');
        foreach ($deleteIds as $id) {
            if (!isset($_curList[$id])) {
                continue;
            }
            if ((int) ($_curList[$id]['member_count'] ?? 0) > 0) {
                $ext = trim((string) ($_curList[$id]['external_id'] ?? ''));
                $name = (string) ($_curList[$id]['grade_name'] ?? '');
                $noDeleteMsg[] = $ext !== '' ? $ext.'-'.$name : $name;
            }
        }
        if ($noDeleteMsg !== []) {
            throw new ErrorException(ErrorCode::MEMBER_GRADE_ERROR, implode(';', $noDeleteMsg).'，已有会员在使用，不能变更。');
        }

        $memberCardService->updateGrade($companyId, $gradeInfo, $deleteIds);

        return $this->getRepository()->getList('*', ['company_id' => $companyId], 0, -1);
    }

}
