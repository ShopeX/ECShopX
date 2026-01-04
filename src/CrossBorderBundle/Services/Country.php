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

use CrossBorderBundle\Entities\OriginCountry;
use Dingo\Api\Exception\StoreResourceFailedException;
use GoodsBundle\Entities\Items;

class Country
{
    private $entityRepository;

    /**
     * ItemsService 构造函数.
     */
    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(OriginCountry::class);
    }

    /**
     * 产地国列表
     */
    public function getList($company_id, $page, $pageSize, $keywords)
    {
        $page = $page ? $page : 1;
        $pageSize = $pageSize ? $pageSize : 10;

        // 查询条件
        if (!empty($keywords)) { // 国家名称
            $filter['origincountry_name|contains'] = $keywords;
        }
        $filter['company_id'] = $company_id;
        $filter['state'] = 1;
        // 查询内容
        $find = [
            'origincountry_id',
            'origincountry_name',
            'origincountry_img_url',
            'created',
            'updated',
        ];

        return $this->entityRepository->lists($filter, $find, $page, $pageSize, ['created' => 'desc']);
    }

    /**
     * 添加产地国家信息
     */
    public function saveAdd($userinfo, $params = [])
    {
        // 判断是否已经存在
        $this->isexistence($userinfo['company_id'], $params['origincountry_name']);
        // 处理添加数据

        $params['company_id'] = $userinfo['company_id'];
        $params['state'] = 1;

        $db = $this->entityRepository->create($params);

        if (!empty($db['origincountry_id'])) {
            return $db['origincountry_id'];
        } else {
            return false;
        }
    }

    /**
     * 更改国家信息
     */
    public function saveUpdate($userinfo, $params, $origincountry_id)
    {
        // 判断是否已经存在
        $this->isexistence($userinfo['company_id'], $params['origincountry_name'], $origincountry_id);
        // 处理添加数据
        $params['updated'] = time();

        $filter['origincountry_id'] = $origincountry_id;
        $filter['company_id'] = $userinfo['company_id'];
        $filter['state'] = 1;
        $db = $this->entityRepository->updateBy($filter, $params);

        if ($db) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除产地国信息
     */
    public function saveDel($userinfo, $origincountry_id)
    {

        // 判断是否有商品在使用
        $where['company_id'] = $userinfo['company_id'];
        $where['origincountry_id'] = $origincountry_id;
        $count = app('registry')->getManager('default')->getRepository(Items::class)->count($where);
        if ($count > 0) {
            throw new \Exception('有商品正在使用该国家，不可删除');
        }
        // 处理添加数据
        $params['state'] = '-1';
        $params['updated'] = time();

        $filter['origincountry_id'] = $origincountry_id;
        $filter['company_id'] = $userinfo['company_id'];
        $filter['state'] = 1;

        $db = $this->entityRepository->updateBy($filter, $params);

        if ($db) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 产地国家是否存
     */
    public function isexistence($company_id = '', $origincountry_name = '', $origincountry_id = null)
    {
        $filter['company_id|eq'] = $company_id;
        $filter['origincountry_name|eq'] = $origincountry_name;
        $filter['state|eq'] = 1;

        if (!empty($origincountry_id)) {
            $filter['origincountry_id|neq'] = $origincountry_id;
        }

        if ($this->entityRepository->count($filter) > 0) {
            throw new StoreResourceFailedException('该国家已经存在');
        } else {
            return false;
        }
    }
}
