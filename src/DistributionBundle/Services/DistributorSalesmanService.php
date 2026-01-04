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

namespace DistributionBundle\Services;

use DistributionBundle\Entities\DistributorSalesman;
use DistributionBundle\Entities\Distributor;
use MembersBundle\Services\MemberService;

use Dingo\Api\Exception\ResourceException;

class DistributorSalesmanService
{
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(DistributorSalesman::class);
    }

    /**
     * 添加销售员，导购员
     */
    public function createSalesman($params)
    {
        if (!ismobile($params['mobile'])) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.mobile_format_error'));
        }

        $params['is_valid'] = 'true';

        $oldData = $this->entityRepository->getInfo(['mobile' => $params['mobile'], 'company_id' => $params['company_id']]);
        if ($oldData) {
            if ($oldData['is_valid'] != 'delete') {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.mobile_already_exists'));
            } else {
                return $this->updateSalesman(['salesman_id' => $oldData['salesman_id'], 'company_id' => $oldData['company_id']], $params);
            }
        }

        $this->checkDistributorId($params['distributor_id'], $params['company_id']);

        $memberService = new MemberService();
        $userId = $memberService->getUserIdByMobile($params['mobile'], $params['company_id']);

        // 后续直接生成会员
        if (!$userId) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.mobile_not_member'));
        }

        $params['user_id'] = $userId;
        return $this->entityRepository->create($params);
    }

    // 新增
    public function hincrbyChildCount($companyId, $salesmanId)
    {
        if ($salesmanId) {
            return $this->entityRepository->hincrbyChildCount($companyId, $salesmanId);
        }
    }

    /**
     * 检查选择的店铺ID是否有效
     */
    private function checkDistributorId($distributorId, $companyId)
    {
        $distributorEntityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $res = $distributorEntityRepository->getInfo(['distributor_id' => $distributorId, 'company_id' => $companyId]);
        return $res ? true : false;
    }

    /**
     * 更新导购员信息
     *
     * @param array $filter 更新条件 // company_id, salesman_id
     * @param array $data 更新数据
     */
    public function updateSalesman($filter, $data)
    {
        $infoById = $this->entityRepository->getInfo(['salesman_id' => $filter['salesman_id'], 'company_id' => $filter['company_id']]);
        if (!$infoById) {
            throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.confirm_update_data'));
        }

        if (isset($data['mobile'])) {
            if (!ismobile($data['mobile'])) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.mobile_format_error'));
            }
            $oldData = $this->entityRepository->getInfo(['mobile' => $data['mobile'], 'company_id' => $filter['company_id']]);
            if ($oldData && $oldData['salesman_id'] != $filter['salesman_id']) {
                throw new ResourceException(trans('DistributionBundle/Services/DistributorSalesmanService.mobile_already_exists'));
            }
        }

        if (isset($data['distributor_id'])) {
            $this->checkDistributorId($data['distributor_id'], $filter['company_id']);
        }

        return $this->entityRepository->updateOneBy($filter, $data);
    }

    /**
     * 获取导购员ID
     */
    public function getSalesmanList($filter, $page = 1, $pageSize = 100)
    {
        $lists = $this->entityRepository->lists($filter, $page, $pageSize, ['created' => 'desc']);
        if ($lists['total_count'] > 0) {
            // 获取店铺名称
            $distributorEntityRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorIds = array_column($lists['list'], 'distributor_id');
            $distributorLists = $distributorEntityRepository->lists(['distributor_id' => $distributorIds], ["created" => "DESC"], $pageSize, 1, false);
            $distributors = array_column($distributorLists['list'], 'name', 'distributor_id');

            foreach ($lists['list'] as $key => $row) {
                if (isset($distributors[$row['distributor_id']])) {
                    $lists['list'][$key]['distributor_name'] = $distributors[$row['distributor_id']];
                }
            }
        }

        return $lists;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
