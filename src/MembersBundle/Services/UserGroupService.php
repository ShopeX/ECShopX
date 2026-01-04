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

use MembersBundle\Entities\MemberGroup;
use MembersBundle\Entities\MemberRelGroup;
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;

/**
 *
 */
class UserGroupService
{
    private $memberGroupRepository;
    private $memberRelGroupRepository;
    private $membersRepository;
    private $membersInfoRepository;

    /**
     * MemberGroupService 构造函数.
     */
    public function __construct()
    {
        $this->memberGroupRepository = app('registry')->getManager('default')->getRepository(MemberGroup::class);
        $this->memberRelGroupRepository = app('registry')->getManager('default')->getRepository(MemberRelGroup::class);
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $this->membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
    }

    /**
     * 创建会员分组
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function createUserGroup($data)
    {
        //查询当前导购员的分组是否已存在
        $filter = [
            'salesperson_id' => $data['salesperson_id'],
            'group_name' => $data['group_name']
        ];
        $find_result = $this->groupNameExistenceOrNot($filter);
        if ($find_result) {
            throw new ResourceException(trans('MembersBundle/Members.group_exists'));
        }

        $create_result = $this->memberGroupRepository->create($data);

        if ($create_result) {
            $result = ['result' => true];
        }

        return $result;
    }

    /**
     * 导购员获取分组列表
     * @param $filter array 条件
     * @return mixed
     */
    public function getUserGroupList($filter)
    {
        $orderBy = ['sort' => 'DESC', 'group_id' => 'DESC'];
        $lists = $this->memberGroupRepository->getLists($filter, 'group_id,group_name,sort', 1, -1, $orderBy);
        foreach ($lists as &$list) {
            $filter = [
                'group_id' => $list['group_id']
            ];
            $list['user_count'] = $this->memberRelGroupRepository->count($filter);
        }
        return $lists;
    }

    /**
     * 导购员根据分组获取会员列表
     * @param $filter array 条件
     * @return mixed
     */
    public function getUsersByGroup($filter)
    {
        $users = $this->memberRelGroupRepository->getLists($filter, 'user_id');
        $tmp = [];
        foreach ($users as $user) {
            array_push($tmp, $user['user_id']);
        }
        $users = $tmp;
        $filter = [
            'user_id' => $users,
            'company_id' => $filter['company_id']
        ];
        $list = $this->membersInfoRepository->lists($filter, $orderBy = ["created" => "DESC"], $pageSize = 1000);
        return $list;
    }

    /**
     * @param $filter array 条件
     * @param $data array 修改信息
     * @return mixed
     */
    public function updateUserGroup($filter, $data)
    {
        //原分组
        $old_group = $this->memberGroupRepository->getInfo($filter);
        if (!$old_group) {
            throw new ResourceException(trans('MembersBundle/Members.group_not_exists'));
        }

        // 检测分组是否已存在
        $filter_group = [
            'salesperson_id' => $filter['salesperson_id'],
            'group_name' => $data['group_name']
        ];
        if ($old_group['group_name'] != $data['group_name']) {
            $find_result = $this -> groupNameExistenceOrNot($filter_group);
            if ($find_result) {
                throw new ResourceException(trans('MembersBundle/Members.group_exists'));
            }
        }

        //执行修改
        $result = $this->memberGroupRepository->updateOneBy($filter, $data);
        return $result;
    }

    /**
     * @param $filter array 条件
     * @param $data array 修改信息
     * @return mixed
     */
    public function deleteUserGroup($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //分组是否存在
            $old_group = $this->memberGroupRepository->getInfo($filter);
            if (!$old_group) {
                throw new ResourceException(trans('MembersBundle/Members.group_not_exists'));
            }

            //执行删除
            $result = $this->memberGroupRepository->deleteBy($filter);
            //删除用户分组数据
            $this->memberRelGroupRepository->deleteBy($filter);

            $conn->commit();

            return [ 'result' => true ];
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * 检测分组是否存在
     * @param  [array] $filter ['salesperson_id','group_name'] 导购员ID，分组名称
     * @return [boolean] true:分组已存在，false:分组不存在
     */
    public function groupNameExistenceOrNot($filter)
    {
        if (!isset($filter['group_name']) || !isset($filter['salesperson_id'])) {
            throw new ResourceException(trans('MembersBundle/Members.condition_missing'));
        }
        $group_name = trim($filter['group_name']);
        $salesperson_id = intval(trim($filter['salesperson_id']));

        if (!$group_name) {
            throw new ResourceException(trans('MembersBundle/Members.group_name_cannot_be_empty'));
        }
        if (empty($salesperson_id)) {
            throw new ResourceException(trans('MembersBundle/Members.invalid_guide'));
        }
        $find_result = $this->memberGroupRepository -> getInfo($filter);

        if ($find_result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 移动会员到分组
     * @param array $data [user_ids,group_id,salesperson_id,$company_id]
     */
    public function moveUserToGroup($data)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //删除原分组中的会员
            $filter = [
                'group_id' => $data['group_id'],
                'salesperson_id' => $data['salesperson_id'],
                'company_id' => $data['company_id']
            ];
            $this->memberRelGroupRepository->deleteBy($filter);

            foreach ($data['user_ids'] as $user_id) {
//                //该用户是否已分组
//                $filter = [
//                    'user_id' => $user_id,
//                    'salesperson_id' => $data['salesperson_id'],
//                    'company_id' => $data['company_id']
//                ];
//                $extend = $this->memberRelGroupRepository->getInfo($filter);

                $data_per = [
                    'user_id' => $user_id,
                    'group_id' => $data['group_id'],
                    'salesperson_id' => $data['salesperson_id'],
                    'company_id' => $data['company_id']
                ];

//                if ($extend) { //已分组，修改分组
//                    $result = $this->memberRelGroupRepository->updateOneBy($filter, $data_per);
//                } else { //未分组，添加分组
                $result = $this->memberRelGroupRepository->create($data_per);
//                }
            }
            $conn->commit();
            return ['result' => true];
        } catch (\Exception $exception) {
            $conn->rollback();
            throw $exception;
        }
    }
}
