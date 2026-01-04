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

namespace SuperAdminBundle\Services;

use OrdersBundle\Services\CompanyRelLogisticsServices;
use SuperAdminBundle\Entities\Logistics;

class LogisticsService
{
    /** @var \SuperAdminBundle\Repositories\LogisticsRepository */
    public $logisticsRepository;

    public function __construct()
    {
        $this->logisticsRepository = app('registry')->getManager('default')->getRepository(Logistics::class);
    }

    /**
     * 添加物流公司
     */
    public function createLogistics($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->logisticsRepository->create($params);

            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 编辑物流公司
     */
    public function updateLogistics($params, $filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();

        try {
            $result = $this->logisticsRepository->updateOneBy($filter, $params);
            //更新公司启用物流表的数据
            $companyRelLogisticsServices = new CompanyRelLogisticsServices();
            $data = ['corp_code' => $params['corp_code'],'corp_name' => $params['corp_name']];

            $count = $companyRelLogisticsServices->getCount($filter);
            if ($count > 0) {
                $companyRelLogisticsServices->updateCompanyRelLogistics($filter, $data);
            }
            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }


    /**
     *  获取物流公司列表
     */
    public function getLogisticsList($filter, $page = 1, $pageSize = 20, $orderBy = ['order_sort' => 'asc'])
    {
        $result = $this->logisticsRepository->lists($filter, '*', $page, $pageSize, $orderBy);

        return $result;
    }

    public function deleteLogistics($id)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->logisticsRepository->deleteById($id);

            //同时删除公司启用物流数据
            $companyRelLogisticsServices = new CompanyRelLogisticsServices();
            $companyRelLogisticsServices->deleteCompanyRelLogistics(['corp_id' => $id]);

            $conn->commit();

            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function batchdeleteLogistics($params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            foreach ((array)$params as $corp_id) {
                $this->logisticsRepository->deleteById($corp_id);
                //同时删除公司启用物流数据
                $companyRelLogisticsServices = new CompanyRelLogisticsServices();
                $companyRelLogisticsServices->deleteCompanyRelLogistics(['corp_id' => $corp_id]);
            }
            $conn->commit();

            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getLogisticsFirst($filter)
    {
        return $this->logisticsRepository->getInfo($filter);
    }

    /**
     *  清除物流信息表
     */
    public function clearLogistics()
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder()->delete('logistics')->execute();
        return true;
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->logisticsRepository->$method(...$parameters);
    }
}
