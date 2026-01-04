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

use MembersBundle\Entities\MemberRemarks;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;

/**
 *
 */
class UserRemarksService
{
    private $membersRepository;
    private $membersInfoRepository;
    private $membersRemarksRepository;

    /**
     * MemberRemarksService 构造函数.
     */
    public function __construct()
    {
        $this->membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $this->membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);
        $this->membersRemarksRepository = app('registry')->getManager('default')->getRepository(MemberRemarks::class);
    }

    /**
     * 添加/修改会员备注
     * @param $data
     * @return array
     */
    public function addRemarks($data)
    {
        try {
            $filter = [
                'salesperson_id' => $data['salesperson_id'],
                'company_id' => $data['company_id'],
                'user_id' => $data['user_id']
            ];
            $remarks = $this -> getRemarks($filter);
            if ($remarks) { // 已备注，修改备注
                $this->membersRemarksRepository->updateOneBy($filter, $data);
            } else { //未备注，增加备注
                $this->membersRemarksRepository->create($data);
            }
            return ['status' => true];
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * 删除会员备注
     * @param $filter
     * @return array
     */
    public function deleteRemarks($filter)
    {
        try {
            $this->membersRemarksRepository->deleteBy($filter);
            return ['status' => true];
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    /**
     * 获取会员备注
     * @param $filter
     * @return mixed
     */
    public function getRemarks($filter)
    {
        $remarks = $this->membersRemarksRepository->getInfo($filter);
        return $remarks;
    }
}
