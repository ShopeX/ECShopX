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

namespace AdaPayBundle\Services;

use Dingo\Api\Exception\ResourceException;

use AdaPayBundle\Entities\AdapayBankCodes;

class BankCodeService
{
    private $dataSource = 'https://cdn.cloudpnr.com/adapayresource/documents/Adapay%E6%94%AF%E6%8C%81%E9%93%B6%E8%A1%8C%E5%88%97%E8%A1%A8.xlsx';
    private $localPath = 'adapay/adapay_bank_local.xlsx';
    public $adapayBankCodesRepository;

    public function __construct()
    {
        $this->adapayBankCodesRepository = app('registry')->getManager('default')->getRepository(AdapayBankCodes::class);
    }

    //将银行数据读取到本地数据库
    public function getData($isUseLocal = true)
    {
        if ($isUseLocal) {
            $dataPath = storage_path($this->localPath);
        } else {
            $dataPath = storage_path('adapay/adapay_bank.xlsx');
            $bankData = file_get_contents($this->dataSource);
            file_put_contents($dataPath, $bankData);
        }
        $count = 0;

        $results = app('excel')->toArray(new \stdClass(), $dataPath);
        $results = $results[1]; // 读取第二张sheet

        if (!$results) {
            return false;
        }

        array_shift($results);//移出表头
        //$conn = app('registry')->getConnection('default');
        //$conn->beginTransaction();
        foreach ($results as $v) {
            $filter = [
                'bank_code' => $v[0],
                'bank_name' => $v[1],
            ];
            $rs = $this->getInfo($filter);
            if (!$rs) {
                $rs = $this->create($filter);
                $count++;
            }
        }
        //$conn->commit();

        echo("写入 $count 条银行数据(adapay)");
    }
    public function getBankName($bank_code)
    {
        $info = $this->getInfo(['bank_code' => $bank_code]);
        if (!$info) {
            throw new ResourceException("银行编号不存在");
        }
        return $info['bank_name'];
    }
    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->adapayBankCodesRepository->$method(...$parameters);
    }
}
