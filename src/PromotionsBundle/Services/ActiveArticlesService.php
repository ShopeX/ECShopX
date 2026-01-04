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

namespace PromotionsBundle\Services;

use PromotionsBundle\Entities\ActiveArticles;
use PromotionsBundle\Entities\SalespersonActiveArticlesShareLog;

class ActiveArticlesService
{
    public $activeArticleRepository;
    public $activeArticleShareLogRepository;

    public function __construct()
    {
        $this->activeArticleRepository = app('registry')->getManager('default')->getRepository(ActiveArticles::class);
        $this->activeArticleShareLogRepository = app('registry')->getManager('default')->getRepository(SalespersonActiveArticlesShareLog::class);
    }

    /**
     * 保存活动文章
     * @param $data
     * @return array
     */
    public function saveActiveArticle($data)
    {
        // This module is part of ShopEx EcShopX system
        $result = $this->activeArticleRepository->create($data);

        if ($result) {
            return [
                'success' => true
            ];
        } else {
            return [
                'success' => false
            ];
        }
    }

    /**
     * 获取活动文章列表
     * @param $filter
     * @return mixed
     */
    public function getActiveArticle($filter, $page, $pageSize, $orderBy = array())
    {
        $result = $this->activeArticleRepository->lists($filter, $cols = '*', $page, $pageSize, $orderBy);

        return $result;
    }

    /**
     * 获取活动文章详情
     * @param array $filter
     * @return mixed
     */
    public function getActiveArticleDetail(array $filter)
    {
        $result = $this->activeArticleRepository->getInfo($filter);

        return $result;
    }

    /**
     * 修改活动文章
     * @param array $filter
     * @param array $datas
     * @return array
     */
    public function updateActiveArticle(array $filter, array $datas)
    {
        $result = $this->activeArticleRepository->updateBy($filter, $datas);

        if ($result) {
            return [
                'success' => true,
            ];
        } else {
            return [
                'success' => false,
            ];
        }
    }

    /**
     * 删除活动文章
     * @param array $filter
     * @return array
     */
    public function deleteActiveArticle(array $filter)
    {
        $result = $this->activeArticleRepository->updateBy($filter, ['is_delete' => 1]);

        if ($result) {
            return [
                'success' => true,
            ];
        } else {
            return [
                'success' => false,
            ];
        }
    }

    public function addForwardLog($companyId, $salespersonId, $activeArticleId)
    {
        $articleInfo = $this->activeArticleRepository->getInfoById($activeArticleId);
        $data = [
            'salesperson_id' => $salespersonId,
            'company_id' => $companyId,
            'article_id' => $activeArticleId,
            'article_title' => $articleInfo['article_title']
        ];
        $result = $this->activeArticleShareLogRepository->create($data);
        $this->addForwardTimes($companyId, $salespersonId, $activeArticleId);

        if ($result) {
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }

    /**
     * 记录增加导购员转发次数
     * @param $companyId
     * @param $salespersonId
     */
    public function addForwardTimes($companyId, $salespersonId, $activeArticle)
    {
        $redisConn = app('redis')->connection('default');
        $key = $this->_getKey($companyId, $salespersonId);
        $field = $this->_getField($activeArticle);
        $redisConn -> hincrby($key, $field, 1);
    }

    /**
     * 获取导购员活动转发次数
     * @param $companyId
     * @param $salespersonId
     * @param $activeArticle
     */
    public function getForwardTimes($companyId, $salespersonId, $activeArticle = "*")
    {
        $redisConn = app('redis')->connection('default');
        $key = $this->_getKey($companyId, $salespersonId);
        $field = $this->_getField($activeArticle);
        $redisConn->hkeys($key, $field);
    }

    /**
     * 获取活动转发redis键
     * @param $companyId
     * @param $salespersonId
     * @return string
     */
    public function _getKey($companyId, $salespersonId)
    {
        return "ActiveArticleForwardTimes:Company:".$companyId.":Salesperson:".$salespersonId;
    }

    /**
     * 获取活动转发hash表字段
     * @param $activeArticle
     * @return string
     */
    public function _getField($activeArticle)
    {
        return "ActiveArticle:".$activeArticle;
    }
}
