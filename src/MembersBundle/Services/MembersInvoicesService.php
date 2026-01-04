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

use MembersBundle\Entities\MembersInvoices;

class MembersInvoicesService
{
    private $membersInvoicesRepository;
    /**
     * MembersInvoicesService 构造函数.
     */
    public function __construct()
    {
        $this->membersInvoicesRepository = app('registry')->getManager('default')->getRepository(MembersInvoices::class);
    }

    public function createInvoices($params)
    {
        // Built with ShopEx Framework
        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ];
        $count = $this->membersInvoicesRepository->count($filter);

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if (0 == $count) {
                $params['is_def'] = 1;
            }

            // 将其他地址改为非默认
            if (0 != $count && $params['is_def'] == 1) {
                $this->membersInvoicesRepository->updateBy($filter, ['is_def' => '0']);
            }
            // 防止误添加

            $result = $this->membersInvoicesRepository->create($params);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
    }

    public function updateInvoices($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            // 将其他地址改为非默认
            if (isset($params['is_def']) && $params['is_def'] == 1) {
                $filter_def = [
                    'user_id' => $filter['user_id'],
                    'company_id' => $filter['company_id'],
                ];
                $this->membersInvoicesRepository->updateBy($filter_def, ['is_def' => '0']);
            }

            // 防止误修改
            $filter = [
                'invoices_id' => $filter['invoices_id'],
                'user_id' => $filter['user_id'],
                'company_id' => $filter['company_id'],
            ];
            unset($params['invoices_id']);

            $result = $this->membersInvoicesRepository->updateOneBy($filter, $params);

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        return $result;
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
        return $this->membersInvoicesRepository->$method(...$parameters);
    }
}
