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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CompanyRelLogistics;

class CompanyRelLogisticsServices
{
    private $companyRelLogisticsRepository;

    public function __construct()
    {
        $this->companyRelLogisticsRepository = app('registry')->getManager('default')->getRepository(CompanyRelLogistics::class);
    }

    /**
     * 创建公司启用物流
     */
    public function createCompanyRelLogistics($data)
    {
        return $this->companyRelLogisticsRepository->create($data);
    }

    /**
     * 获取公司启用物流列表
     */
    public function getCompanyRelLogisticsList(array $filter)
    {
        $conn = app('registry')->getConnection('default');
        $count = $conn->createQueryBuilder()->select('count(*)')->from('logistics')->execute()->fetchColumn();
        $result['total_count'] = intval($count);

        $result['list'] = $conn->createQueryBuilder()->select('*')->from('logistics')->execute()->fetchAll();

        $rel = $this->companyRelLogisticsRepository->lists(['company_id' => $filter['company_id'], 'distributor_id' => $filter['distributor_id'], 'supplier_id' => $filter['supplier_id']], 1, -1, ['id' => 'ASC'], 'corp_id,id,company_id');
        $rel = array_column($rel['list'], null, 'corp_id');
        foreach ($result['list'] as $key => $row) {
            if (isset($rel[$row['corp_id']])) {
                $result['list'][$key] = array_merge($row, $rel[$row['corp_id']]);
            } else {
                $result['list'][$key]['company_id'] = null;
                $result['list'][$key]['id'] = null;
            }

            if (!empty($filter['corp_name']) && trim($filter['corp_name']) != trim($row['corp_name'])) {

                if (!isset($rel[$row['corp_id']])) {
                    unset($result['list'][$key]);
                    $result['total_count']--;
                }
            }

            if (!empty($filter['status']) && $filter['status'] == 1) {
                if (!isset($rel[$row['corp_id']])) {
                    unset($result['list'][$key]);
                    $result['total_count']--;
                }
            }

            if (!empty($filter['status']) && $filter['status'] == 2) {
                if (isset($rel[$row['corp_id']])) {
                    unset($result['list'][$key]);
                    $result['total_count']--;
                }
            }
        }

        return $result;
    }

    public function getCompanyRelLogistics(array $filter, $page = 1, $pageSize = 100, $orderBy = ['id' => 'DESC'], $cols = '*')
    {
        return $this->companyRelLogisticsRepository->lists($filter, $page, $pageSize, $orderBy, $cols);
    }

    /**
     * 删除公司关闭的物流
     */
    public function deleteCompanyRelLogistics($data)
    {
        return $this->companyRelLogisticsRepository->deleteBy($data);
    }

    /**
     * 更新公司启用的物流
     */
    public function updateCompanyRelLogistics($filter, $data)
    {
        return $this->companyRelLogisticsRepository->updateBy($filter, $data);
    }

    public function getCount($filter)
    {
        return $this->companyRelLogisticsRepository->count($filter);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companyRelLogisticsRepository->$method(...$parameters);
    }
}
