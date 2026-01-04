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

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\UserDailyRecord;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\Members;
use Dingo\Api\Exception\ResourceException;

class UserDailyRecordService
{
    private $entityRepository;

    public function __construct()
    {
        // 53686f704578
        $this->entityRepository = app('registry')->getManager('default')->getRepository(UserDailyRecord::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function create($params)
    {
        if (!($params['form_data'] ?? [])) {
            throw new ResourceException(trans('SelfserviceBundle.data_error'));
        }
        foreach ($params['form_data'] as $value) {
            if (!($value['field_value'] ?? '') && ($value['is_required'] ?? false) == 'true') {
                throw new ResourceException(trans('SelfserviceBundle.field_required_simple', ['field_title' => $value['field_title']]));
            }
            if ($value['form_element'] == 'number' && !is_numeric($value['field_value'])) {
                throw new ResourceException(trans('SelfserviceBundle.field_must_be_number', ['field_title' => $value['field_title']]));
            }
        }
        if (isset($params['id'])) {
            $filter['id'] = $params['id'];
            $filter['company_id'] = $params['company_id'];
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    public function getStatisticalAnalysis($filter, $orderBy = ['record_date' => 'DESC'], $size = 5, $page = 1)
    {
        $result['list'] = [];
        $result['keyindex'] = [];

        if (!($filter['user_id'] ?? 0)) {
            return $result;
        }
        if (!($filter['temp_id'] ?? 0)) {
            return $result;
        }
        $listdata = $this->entityRepository->lists($filter, $page, $size, $orderBy)['list'];
        if (!$listdata) {
            return $result;
        }
        foreach ($listdata as $key => $value) {
            $formData = $value['form_data'];
            foreach ($formData as $val) {
                $result['list'][$val['field_name']]['fieldname'] = $val['field_title'];
                $result['list'][$val['field_name']]['fieldkey'] = $val['field_name'];
                $result['list'][$val['field_name']]['fieldvalue'][$key] = floatval($val['field_value']);
                if ($key == 0) { //本次
                    $result['list'][$val['field_name']]['thisweek'] = floatval($val['field_value']);
                }
                if ($key == 1) { //上次
                    $result['list'][$val['field_name']]['lastweek'] = floatval($val['field_value']);
                }
                if (isset($val['key_index']) && $val['key_index']) {
                    $result['keyindex'][$val['field_name']]['fieldvalue'][$key] = floatval($val['field_value']);
                    $result['keyindex'][$val['field_name']]['fieldname'] = $val['field_title'];
                    $result['keyindex'][$val['field_name']]['fieldkey'] = $val['field_name'];
                    if ($key == 0) { //本次
                        $result['keyindex'][$val['field_name']]['thisweek'] = floatval($val['field_value']);
                    }
                    if ($key == 1) { //上次
                        $result['keyindex'][$val['field_name']]['lastweek'] = floatval($val['field_value']);
                    }
                }
            }
        }
        $list = [];
        $keyIndex = [];
        if ($result['list'] ?? []) {
            foreach ($result['list'] as $value) {
                $list[] = $value;
            }
        }
        if ($result['keyindex'] ?? []) {
            foreach ($result['keyindex'] as $value) {
                $keyIndex[] = $value;
            }
        }
        $result['list'] = $list;
        $result['keyindex'] = $keyIndex;
        return $result;
    }

    /**
        * @brief  获取所有会员最新的体测报告
        *
        * @param $companyId
        * @param $tempId
        * @param $shopId
        * @param $datedata  时间戳
        *
        * @return
     */
    public function getAllUserStatisticalAnalysis($filter, $page = 1, $pageSize = 20)
    {
        $result = [
            'list' => [],
            'total_count' => 0,
            'colstitle' => [],
        ];
        if (!($filter['company_id'] ?? 0) || !($filter['temp_id'] ?? 0)) {
            return $result;
        }

        $memberEntityRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $memberInfoEntityRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

        if ($filter['mobile'] ?? '') {
            $members = $memberEntityRepository->getDataList(['mobile' => $filter['mobile'], 'company_id' => $filter['company_id']]);
            if (!$members) {
                return $result;
            }
            $filter['user_id'] = array_column($members, 'user_id');
        }
        if ($filter['username'] ?? '') {
            $memberInfo = $memberInfoEntityRepository->getDataList(['username' => $filter['username'], 'company_id' => $filter['company_id']]);
            if (!$memberInfo) {
                return $result;
            }
            $filter['user_id'] = array_column($memberInfo, 'user_id');
        }
        unset($filter['mobile'], $filter['username']);

        $datalist = $this->entityRepository->getUserRecordingGroupByUserId($filter, $page, $pageSize);
        if (!($datalist['list'] ?? [])) {
            return $result;
        }

        $result['total_count'] = $datalist['total_count'];
        $colTitle = [];
        $listdata = [];

        $userIds = array_column($datalist['list'], 'user_id');
        if (!($members ?? [])) {
            $members = $memberEntityRepository->getDataList(['user_id' => $userIds, 'company_id' => $filter['company_id']]);
        }
        if (!($memberInfo ?? [])) {
            $memberInfo = $memberInfoEntityRepository->getDataList(['user_id' => $userIds, 'company_id' => $filter['company_id']]);
        }

        $members = array_column($members, null, 'user_id');
        $memberInfo = array_column($memberInfo, null, 'user_id');

        foreach ($datalist['list'] as $key => $val) {
            $formData = $val['form_data'] ?? [];
            unset($val['form_data']);
            $list = $val;
            $list['mobile'] = $members[$val['user_id']]['mobile'] ?? '';
            $list['username'] = $memberInfo[$val['user_id']]['username'] ?? '';
            foreach ($formData as $k => $elem) {
                if (($elem['key_index'] ?? false) == 'true') {
                    $colTitle[$k] = [
                        'prop' => $elem['field_name'],
                        'label' => $elem['field_title'],
                    ];
                    $list[$elem['field_name']] = $elem['field_value'];
                }
            }
            $listdata[] = $list;
        }
        $result['list'] = $listdata;
        foreach ($colTitle as $value) {
            $result['colstitle'][] = $value;
        }
        return $result;
    }
}
