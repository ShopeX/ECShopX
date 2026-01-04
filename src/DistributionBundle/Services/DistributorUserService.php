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

use DistributionBundle\Entities\DistributorUser;
use DistributionBundle\Entities\Distributor;
use SalespersonBundle\Services\SalespersonService;

use WorkWechatBundle\Services\WorkWechatRelService;

class DistributorUserService
{
    private $entityRepository;
    private $entityDistributor;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(DistributorUser::class);
        $this->entityDistributor = app('registry')->getManager('default')->getRepository(Distributor::class);
    }

    /**
     * 会员关联店铺，过期到对应的导购员
     */
    public function getSalesmanId($params)
    {
        // 如果会员本身是导购员，那么该会员的导购员为0
        $salespersonService = new SalespersonService();
        $userIsSalesman = $salespersonService->getSalespersonDetail(['user_id' => $params['user_id']]);
        $salesmanId = 0;
        if ($userIsSalesman) {
            return $salesmanId;
        }

        // 如果邀请人是导购员，那么该会员的导购员是邀请人
        $params['inviter_id'] = $params['inviter_id'] ?? 0;
        if ($params['inviter_id']) {
            $salesman = $salespersonService->getSalespersonDetail(['user_id' => $params['inviter_id']]);
            if ($salesman) {
                return $salesman['salesperson_id'];
            }
        }


        // 如果邀请人不是导购员，则获取邀请人所属的导购员
        //$data = $this->entityRepository->getInfo(['user_id'=>$params['inviter_id'], 'company_id'=>$params['company_id']]);
        //if ($data) {
        //    return $data['salesman_id'];
        //}

        return $salesmanId;
    }

    /**
    *   获取会员对应的导购员信息
    */
    public function getSalesmanInfo($params)
    {
        $salespersonService = new SalespersonService();
        //无邀请人根据用户id筛选
        $salesmanId = 0;
        $salesman = $this->entityRepository->getInfo(['user_id' => $params['user_id'], 'company_id' => $params['company_id']]);
        if ($salesman) {
            $salesmanId = $salesman['salesman_id'];
            $salemanInfo = $salespersonService->getInfo(['salesperson_id' => $salesmanId, 'company_id' => $params['company_id']]);
            return $salemanInfo;
        }
        // 如果会员本身是导购员，那么该会员的导购员为0
        $userIsSalesman = $salespersonService->getInfo(['user_id' => $params['user_id'], 'company_id' => $params['company_id']]);
        if ($userIsSalesman) {
            return $params;
        }

        //如果有邀请人
        if ($params['inviter_id']) {
            // 如果邀请人是导购员，那么该会员的导购员是邀请人
            $salesman = $salespersonService->getInfo(['user_id' => $params['inviter_id'], 'company_id' => $params['company_id']]);
            if ($salesman) {
                $salesmanId = $salesman['salesperson_id'];
            } else {
                // 如果邀请人不是导购员，则获取邀请人所属的导购员
                $data = $this->entityRepository->getInfo(['user_id' => $params['inviter_id'], 'company_id' => $params['company_id']]);
                if ($data) {
                    $salesmanId = $data['salesman_id'];
                }
            }

            if ($salesmanId) {
                $salemanInfo = $salespersonService->getInfo(['salesperson_id' => $salesmanId, 'company_id' => $params['company_id']]);
                return $salemanInfo;
            }
            return false;
        } else {
            return false;
        }
    }

    public function createData($params)
    {
        if (!$params['distributor_id'] && !$params['salesperson_id'] && !$params['inviter_id']) {
            return true;
        }
        if ($params['salesperson_id'] ?? 0) {
            $params['salesman_id'] = $params['salesperson_id'];
        } else {
            $params['salesman_id'] = $this->getSalesmanId($params);
        }
        $filter = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
        ];
        if ($params['distributor_id'] ?? null) {
            $filter['distributor_id'] = $params['distributor_id'];
        }
        if ($params['salesman_id'] ?? null) {
            $filter['salesman_id'] = $params['salesman_id'];
        }
        $data = $this->entityRepository->getInfo($filter);
        if ($data) {
            return $data;
        }
        $result = $this->entityRepository->create($params);
        return $result;
    }

    public function updateUserSalesman($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $user_ids = $filter['user_ids'];
            $filter = ['distributor_id' => $filter['distributor_id'], 'company_id' => $filter['company_id']];
            foreach ($user_ids as $user_id) {
                $filter['user_id'] = $user_id;
                $this->entityRepository->updateOneBy($filter, $params);
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
    * 获取会员的关联信息
    * @return bindSysUserId:绑定导购员id
    * @return sysUserId:拉新导购员id
    * @return sysStoreId:拉新门店id
    */
    public function getUserRelData($params)
    {
        $return = [
            'bindSysUserId' => 0,
            'sysUserId' => 0,
            'sysStoreId' => 0,
        ];
        // 查询绑定导购员id
        $workWechatRelService = new WorkWechatRelService();
        $work_filter = [
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
            'is_bind' => true,
        ];
        $work_wechat_rel = $workWechatRelService->getWorkWechatRel($work_filter);
        if ($work_wechat_rel && $work_wechat_rel['list']) {
            $_list = $work_wechat_rel['list'];
            $return['bindSysUserId'] = $_list[0]['salesperson_id'] ?? 0;
        }
        // 查询拉新导购员id 和 拉新门店id
        $filter = [
            'distributor_id' => $params['distributor_id'],
            'user_id' => $params['user_id'],
            'company_id' => $params['company_id'],
        ];
        $data = $this->entityRepository->getInfo($filter);
        if ($data) {
            $return['sysUserId'] = $data['salesman_id'];
            $return['sysStoreId'] = $data['shop_id'];
        }
        return $return;
    }

    /**
     * 根据会员id获取绑定导购和导购店铺
     * @param  string $company_id 企业ID
     * @param  string $user_id    会员ID
     * @return array
     */
    public function getBindUserRelData($company_id, $user_id)
    {
        $return = [
            'bind_salesman_id' => 0,
            'bind_salesman_distributor_id' => 0,
        ];
        $workWechatRelService = new WorkWechatRelService();
        $work_filter = [
            'user_id' => $user_id,
            'company_id' => $company_id,
            'is_bind' => true,
        ];
        $work_wechat_rel = $workWechatRelService->getWorkWechatRel($work_filter);
        if ($work_wechat_rel && $work_wechat_rel['list']) {
            $_list = $work_wechat_rel['list'];
            $return['bind_salesman_id'] = $_list[0]['salesperson_id'] ?? 0;
        } else {
            return $return;
        }

        // 获取导购店铺信息
        $salespersonService = new SalespersonService();
        $filter = [
            'company_id' => $company_id,
            'salesperson_id' => $return['bind_salesman_id'],
            'store_type' => 'distributor',
        ];
        $relShopData = $salespersonService->getSalespersonRelShopdata($filter, 1, -1);
        if ($relShopData['list']) {
            $return['bind_salesman_distributor_id'] = $relShopData['list'][0]['shop_id'];
        }
        return $return;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
