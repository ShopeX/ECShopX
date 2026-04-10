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

namespace EspierBundle\Services;

use EspierBundle\Entities\OfflineBankAccount;

class OfflineBankAccountService
{
    /** @var \EspierBundle\Repositories\OfflineBankAccountRepository */
    public $offlineBankAccountRepository;

    public function __construct()
    {
        $this->offlineBankAccountRepository = app('registry')->getManager('default')->getRepository(OfflineBankAccount::class);
    }

    /**
     * 线下转账列表中的 bank_name 为历史快照；按 bank_account_id 从收款账户取当前语言银行名（与 getLists 多语言一致）并覆盖。
     *
     * @param int   $companyId
     * @param array $rows      offline_payment 列表行（引用 bank_account_id、bank_name）
     * @return array
     */
    public function applyLocalizedBankNameToOfflinePaymentRows(int $companyId, array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }
        $bankAccountIds = array_unique(array_filter(array_map('intval', array_column($rows, 'bank_account_id'))));
        if (!$bankAccountIds) {
            return $rows;
        }
        $banks = $this->offlineBankAccountRepository->getLists(
            ['company_id' => $companyId, 'id' => $bankAccountIds],
            'id,bank_name',
            1,
            -1,
            []
        );
        $bankNameById = [];
        foreach ($banks as $bankRow) {
            $bankNameById[(int) $bankRow['id']] = $bankRow['bank_name'] ?? '';
        }
        foreach ($rows as $k => $row) {
            $bid = isset($row['bank_account_id']) ? (int) $row['bank_account_id'] : 0;
            if ($bid && array_key_exists($bid, $bankNameById)) {
                $rows[$k]['bank_name'] = $bankNameById[$bid];
            }
        }

        return $rows;
    }

    public function createData($params)
    {
        // 如果is_default=1,其他的设置为0
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->create($params);
    }

    public function update($filter, $params)
    {
        // TS: 53686f704578
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->updateBy($filter, $params);
    }

    /**
     * Dynamically call the SubdistrictService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // TS: 53686f704578
        return $this->offlineBankAccountRepository->$method(...$parameters);
    }
}