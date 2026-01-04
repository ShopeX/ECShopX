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

use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\VipGrade;
use KaquanBundle\Entities\VipGradeRelUser;
use MembersBundle\Services\MemberService;

class VipGradeService
{
    /** @var \KaquanBundle\Repositories\VipGradeRepository */
    public $entityRepository;
    private $vipGradeRelUserRepository;

    private $_callVipGradeRelUser = false;

    /**
     * vip类型
     */
    public const LV_TYPE_VIP = "vip";
    public const LV_TYPE_SVIP = "svip";
    public const LV_TYPE_MAP = [
        self::LV_TYPE_VIP => "普通vip",
        self::LV_TYPE_SVIP => "进阶vip",
    ];

    public static function getLvTypeMap()
    {
        return [
            self::LV_TYPE_VIP => trans('KaquanBundle.normal_vip'),
            self::LV_TYPE_SVIP => trans('KaquanBundle.advanced_vip'),
        ];
    }

    public function __construct()
    {
        // 456353686f7058
        $this->entityRepository = app('registry')->getManager('default')->getRepository(VipGrade::class);
    }

    public function createVipGrade($companyId, $params)
    {
        $packageSetService = new PackageSetService();
        $packageReceiveService = new PackageReceivesService();

        $filter['company_id'] = $companyId;
        $gradeList = $this->entityRepository->lists($filter);
        if ($gradeList) {
            $gradeIds = array_column($gradeList, 'vip_grade_id');
            $newGradeIds = array_column($params, 'vip_grade_id');
            $deleteIds = array_diff($gradeIds, $newGradeIds);
            if ($deleteIds = array_filter($deleteIds)) {
                $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
                $memberFilter = [
                    'vip_grade_id' => $deleteIds,
                    'company_id' => $companyId,
                ];
                $memberInfo = $entityRepository->lists($memberFilter);
                if (count($memberInfo['list']) <= 0) {
                    $delFilter['vip_grade_id'] = $deleteIds;
                    $this->entityRepository->deleteBy($delFilter);

                    $packageSetService->deleteTriggerByAssociationId($companyId, $deleteIds, 'vip_grade');
                } else {
                    throw new ResourceException(trans('KaquanBundle.members_in_grade_cannot_delete'));
                }
            }
        }

        $result = [];
        foreach ($params as $key => $grade) {
            app('log')->info('$grade'.var_export($grade, 1));
            if (!is_array($grade)) {
                $params[$key] = json_decode($grade, true);
            }
            if (is_numeric($params[$key]['privileges']['discount']) && $params[$key]['privileges']['discount'] != '10') {
                $params[$key]['privileges']['discount_desc'] = $params[$key]['privileges']['discount'];
                $params[$key]['privileges']['discount'] = 100 - intval($params[$key]['privileges']['discount'] * 10);
            } else {
                $params[$key]['privileges']['discount'] = 0;
                $params[$key]['privileges']['discount_desc'] = 10;
            }
            $params[$key]['company_id'] = $companyId;

            if ($grade['vip_grade_id'] && isset($gradeIds) && in_array($grade['vip_grade_id'], $gradeIds)) {
                $filter = [
                    'company_id' => $companyId,
                    'vip_grade_id' => $grade['vip_grade_id']
                ];
                $result[$key] = $this->entityRepository->updateOneBy($filter, $params[$key]);
                $associationId = $grade['vip_grade_id'];
                $packageSetService->deleteTriggerByAssociationId($companyId, $associationId, 'vip_grade');
            } elseif (!$grade['vip_grade_id']) {
                $result[$key] = $this->entityRepository->create($params[$key]);
                $tempItem = $this->entityRepository->getInfo(['vip_grade_id' => $result[$key]['vip_grade_id']]);
                $associationId = $tempItem['vip_grade_id'];
            }

            if (!empty($grade['voucher_package']) && isset($associationId)) {
                $packageSetService->setTriggerByPackageIdSet($companyId, $grade['voucher_package'], $associationId, 'vip_grade');
                $packageReceiveService->clearReceivesRecord($companyId, $associationId, 'vip_grade');
            }
        }



        return true;
    }

    /**
     * 获取已过期付费会员数量
     */
    public function countExpiredVipGrade($companyId, $vipType)
    {
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        return $entityRepository->count(['company_id' => $companyId, 'vip_type' => $vipType, 'end_date|lt' => time()]);
    }

    /**
     * 获取默认的付费会员等级详情，默认引导用户购买的付费会员等级
     */
    public function getDefaultGradeInfo($companyId)
    {
        return $this->entityRepository->getInfo(['is_default' => true, 'company_id' => $companyId]);
    }

    /**
     * 获取过期付费会员信息
     */
    public function getExpiredVipGradeUser($companyId, $vipType, $page = 1, $pageSize = 100)
    {
        $entityRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
        $data = $entityRepository->lists(['company_id' => $companyId, 'vip_type' => $vipType, 'end_date|lt' => time()], ["id" => "DESC"], $pageSize, $page);
        $return = [];
        $memberService = new memberService();
        if ($data['total_count'] > 0) {
            $userIds = array_column($data['list'], 'user_id');
            $users = $memberService->getMobileByUserIds($companyId, $userIds);
            foreach ($users as $userId => $mobile) {
                $return[] = [
                    'user_id' => $userId,
                    'mobile' => $mobile,
                ];
            }
        }

        return $return;
    }

    public function listDataVipGrade($filter)
    {
        $gradeList = $this->lists($filter);

        // 附上卡券包信息
        $gradeIdList = array_column($gradeList, 'vip_grade_id');
        $bindPackageIndex = (new PackageSetService())->getBindPackageList($filter['company_id'], $gradeIdList, 'vip_grade');

        foreach ($gradeList as $key => $item) {
            if (isset($bindPackageIndex[$item['vip_grade_id']])) {
                $gradeList[$key]['voucher_package'] = $bindPackageIndex[$item['vip_grade_id']];
            } else {
                $gradeList[$key]['voucher_package'] = [];
            }
        }

        return $gradeList;
    }

    public function getCurrentVipGradeUserId(int $companyId, int $vipGradeId, string $vipType): array
    {
        $filter = [
            'company_id' => $companyId,
            'vip_grade_id' => $vipGradeId,
            'vip_type' => $vipType,
            'end_date|gt' => time()
        ];
        $listData = $this->vipGradeRelUserRepository->getLists($filter, 'user_id');

        return array_column($listData, 'user_id');
    }

    public function callVipGradeRelUser($isUse = true)
    {
        if ($isUse) {
            $this->vipGradeRelUserRepository = app('registry')->getManager('default')->getRepository(VipGradeRelUser::class);
            $this->_callVipGradeRelUser = true;
        } else {
            $this->_callVipGradeRelUser = false;
        }
        return $this;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($this->_callVipGradeRelUser) {
            return $this->vipGradeRelUserRepository->$method(...$parameters);
        } else {
            return $this->entityRepository->$method(...$parameters);
        }
    }
}
