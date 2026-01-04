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

namespace CrossBorderBundle\Services;

use CrossBorderBundle\Entities\Taxstrategy as crossborderTaxstrategy;

class Taxstrategy
{
    private $entityRepository;

    /**
     * 构造函数.
     */
    public function __construct()
    {
        // 0x53686f704578
        $this->entityRepository = app('registry')->getManager('default')->getRepository(crossborderTaxstrategy::class);
    }

    /**
     * 税费策略列表
     */
    public function getList($company_id, $page, $pageSize, $keywords)
    {
        $page = $page ? $page : 1;
        $pageSize = $pageSize ? $pageSize : 10;

        // 查询条件
        if (!empty($keywords)) { // 国家名称
            $filter['taxstrategy_name|contains'] = $keywords;
        }
        $filter['company_id'] = $company_id;
        $filter['state'] = 1;
        // 查询内容
        $find = [
            'id',
            'taxstrategy_name',
//            'taxstrategy_content',
            'created',
            'updated',
        ];

        return $this->entityRepository->lists($filter, $find, $page, $pageSize, ['created' => 'desc']);
    }

    /**
     * 税费策略详情
     */
    public function getInfo($filter)
    {
        // 0x53686f704578
        $data = $this->entityRepository->getInfo($filter);
        $taxstrategy_content = json_decode($data['taxstrategy_content'], true);
        unset($data['taxstrategy_content']);
        foreach ($taxstrategy_content as $k => $v) {
            $data['taxstrategy_content'][] = json_decode($v, true);
        }
        return $data;
    }

    /**
     * 税费策略添加
     */
    public function addSave($company_id, $add_data = [])
    {
        $add_data['company_id'] = $company_id;
        $add_data['state'] = 1;

        $db = $this->entityRepository->create($add_data);
        if (!empty($db['id'])) {
            return $db['id'];
        } else {
            return false;
        }
    }

    /**
     * 税费策略数据更新(删除，更新状态为-1)
     */
    public function updateSave($filter, $params)
    {
        $db = $this->entityRepository->updateBy($filter, $params);
        if ($db) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 税费策略删除
     */
    public function isDel($userinfo, $origincountry_id)
    {
        // 使用更新数据方法
    }
}
