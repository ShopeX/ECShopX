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

namespace KaquanBundle\Services;

use GoodsBundle\Services\MultiLang\MagicLangTrait;
use GoodsBundle\Services\MultiLang\MultiLangService;
use KaquanBundle\Entities\MemberCard;
use KaquanBundle\Entities\MemberCardGrade;
use MembersBundle\Services\MemberService;

class MemberCardService
{
    use MagicLangTrait;
    private $cardType = "MEMBER_CARD";
    private $memberCardRepository;
    /** @var \KaquanBundle\Repositories\MembercardGradeRepository */
    public $memberCardGradeRepository;

    public const DEFAULT_GRADE_YES = 1;
    public const DEFAULT_GRADE_NO = 0;

    public function __construct()
    {
        $this->memberCardRepository = app('registry')->getManager('default')->getRepository(MemberCard::class);
        $this->memberCardGradeRepository = app('registry')->getManager('default')->getRepository(MemberCardGrade::class);
    }

    public function setMemberCard($companyId, $params)
    {
        $filter = [ 'company_id' => $companyId ];
        $result = $this->memberCardRepository->update($filter, $params);

        return $result;
    }

    public function getMemberCard($companyId)
    {
        $result = $this->memberCardRepository->get(['company_id' => $companyId]);

        return $result;
    }

    public function getGradeIdByName($companyId, $gradeName)
    {
        $result = $this->memberCardGradeRepository->get(['company_id' => $companyId, 'grade_name' => $gradeName]);
        if ($result) {
            return $result->getGradeId();
        }
        return null;
    }

    /**
     * 创建会员卡默认等级
     */
    public function setDefaultGrade($gradeInfo)
    {
        $filter = [
            'company_id' => $gradeInfo['company_id'],
            'default_grade' => true
        ];
        $defaultGrade = $this->memberCardGradeRepository->get($filter);
        if ($defaultGrade) {
            return $defaultGrade;
        }

        return $this->memberCardGradeRepository->setDefaultGrade($gradeInfo);
    }

    /**
     * 创建会员卡等级
     */
    public function updateGrade($companyId, $newGrades)
    {
        foreach ($newGrades as $key => $grade) {
            if (is_numeric($newGrades[$key]['privileges']['discount']) && $newGrades[$key]['privileges']['discount'] != '10') {
                $newGrades[$key]['privileges']['discount_desc'] = $newGrades[$key]['privileges']['discount'];
                $newGrades[$key]['privileges']['discount'] = 100 - intval($newGrades[$key]['privileges']['discount'] * 10);
            } else {
                $newGrades[$key]['privileges']['discount'] = 0;
                $newGrades[$key]['privileges']['discount_desc'] = 10;
            }
            $newGrades[$key]['company_id'] = $companyId;
        }
        $gradeList = $this->memberCardGradeRepository->getListByCompanyId($companyId);

        $gradeIds = array_column($gradeList, 'grade_id');
        $newGradeIds = array_column($newGrades, 'grade_id');
        $deleteIds = array_diff($gradeIds, $newGradeIds);

        $newGradesList = [];
        $result = $this->memberCardGradeRepository->update($companyId, $newGrades, $deleteIds, $newGradesList);

        $packageSetService = new PackageSetService();
        $packageReceiveService = new PackageReceivesService();

        if (!empty($gradeIds)) {
            // 删除已有关联
            $packageSetService->deleteTriggerByAssociationId($companyId, $gradeIds, 'grade');
        }
        foreach ($newGradesList as $item) {
            if (!empty($item['voucher_package'])) {
                $packageSetService->setTriggerByPackageIdSet($companyId, $item['voucher_package'], $item['grade_id'], 'grade');
                $packageReceiveService->clearReceivesRecord($companyId, $item['grade_id'], 'grade');
            }
        }

        return $result;
    }

    public function getNextGradeByGradeId($gradeId)
    {
        $gradeInfo = $this->getGradeByGradeId($gradeId);
        $grades = $this->getGradeListByCompanyId($gradeInfo['company_id']);
        $nextGradeInfo = [];
        if ($gradeInfo && $grades) {
            foreach ($grades as $key => $value) {
                if ($value['grade_id'] == $gradeId) {
                    $nextIndex = $key + 1;
                    $nextGradeInfo = (isset($grades[$nextIndex]) && $grades[$nextIndex]) ? $grades[$nextIndex] : [];
                }
            }
        }
        return $nextGradeInfo;
    }

    public function getDefaultGradeByCompanyId($companyId)
    {
        $filter = [
            'company_id' => $companyId,
            'default_grade' => true,
        ];
        app('log')->info(__FUNCTION__.':'.__LINE__.':filter:'.json_encode($filter));
        $grade = $this->memberCardGradeRepository->get($filter);
        app('log')->info(__FUNCTION__.':'.__LINE__.':grade:'.json_encode($grade));
        if ($grade) {
            $result = [
                'grade_id' => $grade->getGradeId(),
                'company_id' => $grade->getCompanyId(),
                'grade_name' => $grade->getGradeName(),
                'default_grade' => $grade->getDefaultGrade(),
                'background_pic_url' => $grade->getBackgroundPicUrl(),
                'promotion_condition' => $grade->getPromotionCondition(),
                'privileges' => $grade->getPrivileges(),
                'description' => $grade->getDescription(),
            ];
        } else {
            $result = [];
        }
        return $result;
    }


    public function getGradeByGradeId($gradeId)
    {
        $grade = $this->memberCardGradeRepository->get(['grade_id' => $gradeId]);
        if ($grade) {
            $result = [
                'grade_id'=>$grade->getGradeId(),
                'company_id' => $grade->getCompanyId(),
                'grade_name' => $grade->getGradeName(),
                'default_grade' => $grade->getDefaultGrade(),
                'background_pic_url' => $grade->getBackgroundPicUrl(),
                'promotion_condition' => $grade->getPromotionCondition(),
                'privileges' => $grade->getPrivileges(),
                'description' => $grade->getDescription(),
                'grade_background' => $grade->getGradeBackground(),
            ];
            $service = new MultiLangService();
            $lang = $this->getLang();
            $result = $service->getOneLangData($result,[],'membercard_grade',$lang,$result['grade_id'],'membercard_grade');
            return $result;
        }

        return $grade;
    }

    public function getGradeListByCompanyId($companyId, $isMemberCount = true)
    {
        $crmOpen = 'false';
        $gradeList = $this->memberCardGradeRepository->getListByCompanyId($companyId);
        if ($gradeList) {
            $memberService = new MemberService();
            foreach ($gradeList as $k => $v) {
                if ($isMemberCount) {
                    $filter = [
                        'grade_id' => $v['grade_id'],
                        'company_id' => $companyId,
                    ];
                    $membersCount = $memberService->membersRepository->count($filter);
                    $gradeList[$k]['member_count'] = $membersCount;
                }

                if ($v['promotion_condition']) {
                    $gradeList[$k]['promotion_condition'] = json_decode($v['promotion_condition'], 1);
                }
                if ($v['privileges']) {
                    $gradeList[$k]['privileges'] = json_decode($v['privileges'], 1);
                }
                if ($v['default_grade'] == '1') {
                    $gradeList[$k]['default_grade'] = true;
                } else {
                    $gradeList[$k]['default_grade'] = false;
                }
                $gradeList[$k]['crm_open'] = $crmOpen;
            }
        }

        usort($gradeList, function ($a, $b) {
            $a['total'] = isset($a['promotion_condition']['total_consumption']) ? $a['promotion_condition']['total_consumption'] : 0;
            $b['total'] = isset($b['promotion_condition']['total_consumption']) ? $b['promotion_condition']['total_consumption'] : 0;
            if ($a['total'] == $b['total']) {
                return 0;
            } else {
                return ($a['total'] > $b['total']) ? 1 : -1;
            }
        });

        // 附上卡券包信息
        $gradeIdList = array_column($gradeList, 'grade_id');

        $bindPackageIndex = (new PackageSetService())->getBindPackageList($companyId, $gradeIdList, 'grade');

        foreach ($gradeList as $key => $item) {
            if (isset($bindPackageIndex[$item['grade_id']])) {
                $gradeList[$key]['voucher_package'] = $bindPackageIndex[$item['grade_id']];
            } else {
                $gradeList[$key]['voucher_package'] = [];
            }
        }

        return $gradeList;
    }

    /**
     * 获取简易公司等级列表
     *
     * @param int $companyId
     * @return mixed
     */
    public function getCompanyGradeSimpleList(int $companyId)
    {
        return $this->memberCardGradeRepository->getListByCompanyId($companyId, 'grade_id,grade_name');
    }

    public function getGradeInfo($filter)
    {
        $grade = $this->memberCardGradeRepository->get($filter);
        if ($grade) {
            $result = [
                'company_id' => $grade->getCompanyId(),
                'grade_id' => $grade->getGradeId(),
                'grade_name' => $grade->getGradeName(),
                'default_grade' => $grade->getDefaultGrade(),
                'background_pic_url' => $grade->getBackgroundPicUrl(),
                'promotion_condition' => $grade->getPromotionCondition(),
                'privileges' => $grade->getPrivileges(),
                'description' => $grade->getDescription(),
            ];

            return $result;
        }

        return [];
    }
}
