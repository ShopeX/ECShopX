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

namespace MembersBundle\Services;

use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\MedicationPersonnel;
use MembersBundle\Repositories\MedicationPersonnelRepository;

class MedicationPersonnelService
{
    /** @var MedicationPersonnelRepository $medicationPersonnelRepository */
    public $medicationPersonnelRepository;

    public function __construct()
    {
        $this->medicationPersonnelRepository = app('registry')->getManager('default')->getRepository(MedicationPersonnel::class);
    }

    /**
     * 创建用药人
     * @param $params
     * @return true[]
     */
    public function create($params)
    {
        // 查询身份证号是否有重复
        $info = $this->medicationPersonnelRepository->getInfo([
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'user_family_id_card' => $params['user_family_id_card'],
        ]);
        if ($info) {
            throw new ResourceException(trans('MembersBundle/Members.cannot_add_duplicate_medication_personnel'));
        }

        if (!in_array($params['relationship'], [1,2,3,4,5])) {
            throw new ResourceException(trans('MembersBundle/Members.relationship_type_error'));
        }

        // 检查是否重复添加本人
        if ($params['relationship'] == 1) {
            $selfInfo = $this->medicationPersonnelRepository->getInfo([
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'relationship' => 1,
            ]);
            if ($selfInfo) {
                throw new ResourceException(trans('MembersBundle/Members.self_relationship_exists'));
            }
        }

        if ($params['user_family_age'] < 6) {
            throw new ResourceException(trans('MembersBundle/Members.under_6_not_supported'));
        }

        // 存在本人的用药人，默认为本人
        if ($params['relationship'] == 1) {
            $params['is_default'] = 1;
        }

        $this->medicationPersonnelRepository->create($params);

        return ['success' => true];
    }

    /**
     * 修改用药人信息
     * @param $filter
     * @param $params
     * @return true[]
     */
    public function update($filter, $params)
    {
        $info = $this->medicationPersonnelRepository->getInfo($filter);
        if (!$info) {
            throw new ResourceException(trans('MembersBundle/Members.medication_personnel_not_exists'));
        }
        // 查询身份证号是否有重复
        $sameInfo = $this->medicationPersonnelRepository->getInfo([
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'user_family_id_card' => $params['user_family_id_card'],
        ]);
        if ($sameInfo && $sameInfo['id'] != $info['id']) {
            throw new ResourceException(trans('MembersBundle/Members.cannot_add_duplicate_medication_personnel'));
        }

        if (!in_array($params['relationship'], [1, 2, 3, 4, 5])) {
            throw new ResourceException(trans('MembersBundle/Members.relationship_type_error'));
        }

        // 检查是否重复添加本人
        if ($params['relationship'] == 1) {
            $selfInfo = $this->medicationPersonnelRepository->getInfo([
                'company_id' => $params['company_id'],
                'user_id' => $params['user_id'],
                'relationship' => 1,
            ]);
            if ($selfInfo && $selfInfo['id'] != $info['id']) {
                throw new ResourceException(trans('MembersBundle/Members.self_relationship_exists'));
            }
        }

        if ($params['user_family_age'] < 6) {
            throw new ResourceException(trans('MembersBundle/Members.under_6_not_supported'));
        }

        // 存在本人的用药人，默认为本人
        if ($params['relationship'] == 1) {
            $params['is_default'] = 1;
        }

        $result = $this->medicationPersonnelRepository->updateOneBy(['id' => $info['id']], $params);

        return ['success' => true];
    }

    /**
     * 获取用药人列表
     * @param $params
     * @return array
     */
    public function getList($params)
    {
        $result = $this->medicationPersonnelRepository->lists([
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
        ], '*', $params['page'] ?? 1, $params['pageSize'] ?? -1, ['is_default' => 'desc', 'created' => 'desc']);

        return $result;
    }

    /**
     * 获取用药人信息
     * @param $params
     * @return array
     */
    public function getDetail($params)
    {
        $result = $this->medicationPersonnelRepository->getInfo($params);
        if (empty($result)) {
            throw new ResourceException(trans('MembersBundle/Members.medication_personnel_info_not_exists'));
        }

        return $result;
    }

    public function deleteMedicationPersonnel($params)
    {
        $this->medicationPersonnelRepository->deleteBy([
            'id' => $params['id'],
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ]);

        return ['success' => true];
    }
}
