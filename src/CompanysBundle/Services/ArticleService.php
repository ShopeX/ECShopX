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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\Article;

use Dingo\Api\Exception\ResourceException;
use SupplierBundle\Services\SupplierItemsService;
use WechatBundle\Services\Material as MaterialService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberItemsFavService;

class ArticleService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        //$this->entityRepository = app('registry')->getManager('default')->getRepository(Article::class);
        $this->entityRepository = getRepositoryLangue(Article::class);
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
        return $this->entityRepository->$method(...$parameters);
    }

    // 文章关注
    public function articleFocus($params)
    {
        if (!$params['article_id'] || !$params['company_id']) {
            throw new ResourceException("参数错误");
        }

        $filter = ['article_id' => $params['article_id'], 'company_id' => $params['company_id']];
        $articleInfo = $this->entityRepository->getInfo($filter);

        if (!$articleInfo) {
            throw new ResourceException("文章不存在");
        }

        $result['count'] = app('redis')->hincrby('articleFocus:'. $articleInfo['company_id'], $articleInfo['article_id'], +1);

        return $result;
    }

    // 获取文章关注条数
    public function articleFocusNum($params)
    {
        if (!$params['article_id'] || !$params['company_id']) {
            throw new ResourceException("参数错误");
        }

        $result['count'] = app('redis')->hget('articleFocus:'. $params['company_id'], $params['article_id']);
        $result['count'] = $result['count'] ? $result['count'] : 0;
        return $result;
    }

    // 文章点赞
    public function articlePraise($params)
    {
        if (!$params['user_id'] || !$params['article_id'] || !$params['company_id']) {
            throw new ResourceException("参数错误");
        }

        // 检测文章是否存在
        $filter = ['article_id' => $params['article_id'], 'company_id' => $params['company_id']];
        $articleInfo = $this->entityRepository->getInfo($filter);

        if (!$articleInfo) {
            throw new ResourceException("文章不存在");
        }

        // 检测是否点赞
        $check = $this->articlePraiseCheck($params);
        if ($check['status']) {
            // 统计点赞数量
            $result['count'] = app('redis')->hincrby('articlePraise:'. $articleInfo['company_id'], $articleInfo['article_id'], -1);
            // 记录点赞用户
            app('redis')->hDel('articlePraiseUser:'. $articleInfo['company_id'] . ':' . $articleInfo['article_id'], $params['user_id']);
            $result['status'] = false;
        } else {
            // 统计点赞数量
            $result['count'] = app('redis')->hincrby('articlePraise:'. $articleInfo['company_id'], $articleInfo['article_id'], +1);
             // 记录点赞用户
             app('redis')->hSet('articlePraiseUser:'. $articleInfo['company_id'] . ':' . $articleInfo['article_id'], $params['user_id'], time());
            $result['status'] = true;
        }

        return $result;
    }

    // 文章点赞
    public function articlePraiseNum($params)
    {
        if (!$params['article_id'] || !$params['company_id']) {
            throw new ResourceException("参数错误");
        }

        $result['count'] = app('redis')->hget('articlePraise:'. $params['company_id'], $params['article_id']);
        $result['count'] = $result['count'] ? $result['count'] : 0;
        return $result;
    }

    // 文章点赞检测
    public function articlePraiseCheck($params)
    {
        if (!$params['user_id'] || !$params['article_id'] || !$params['company_id']) {
            throw new ResourceException("参数错误");
        }

        $result = app('redis')->hGet('articlePraiseUser:'. $params['company_id'] . ':' . $params['article_id'], $params['user_id']);

        return ['status' => $result ? true : false ];
    }

    //获取文章点赞数量 和 用户对文章的点赞结果
    public function getArticlePraiseResult($articleIds, $companyId, $userId = null)
    {
        if (!$articleIds) {
            return [];
        }
        $result = [];
        foreach ($articleIds as $articleId) {
            $result[$articleId]['count'] = intval(app('redis')->hget('articlePraise:'. $companyId, $articleId));
            if ($userId) {
                $status = app('redis')->hget('articlePraiseUser:'. $companyId . ':' . $articleId, $userId);
                $result[$articleId]['status'] = $status ? true : false;
            }
        }
        return $result;
    }


    public function getArticleList($filter, $page = 1, $pageSize = -1, $orderBy = ['release_time' => 'desc'], $isHaveCount = false)
    {
        $authorizerAppId = $filter['authorizer_appid'];
        $userId = $filter['user_id'] ?? 0;
        unset($filter['authorizer_appid'], $filter['user_id']);
        $result = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        if (!$result['list']) {
            return $result;
        }
        // $operatorIds = array_column($result['list'], 'operator_id');
        // $operator = $this->getOperatorData($filter['company_id'], $operatorIds);
        foreach ($result['list'] as $key => $value) {
            // $result['list'][$key]['author'] = $value['author'] ?: ($operator[$value['operator_id']]['username'] ?? '');
            // $result['list'][$key]['head_portrait'] = $value['head_portrait'] ?: ($operator[$value['operator_id']]['head_portrait'] ?? '');
            if ($value['article_type'] != 'bring') {
                continue;
            }
            if (!$isHaveCount) {
                continue;
            }
            $params = [
                'company_id' => $value['company_id'],
                'article_id' => $value['article_id'],
            ];
            $result['list'][$key]['articleFocusNum'] = $this->articleFocusNum($params); //文章关注条数
            $result['list'][$key]['articlePraiseNum'] = $this->articlePraiseNum($params);  //文章点赞数
            $result['list'][$key]['isPraise'] = false;  //文章是否被点赞
            if ($userId) {
                $params['user_id'] = $userId;
                $result['list'][$key]['isPraise'] = $this->articlePraiseCheck($params)['status'];  //文章是否被点赞
            }
        }
        return $result;
    }

    public function getArticleInfo($filter, $isHaveCount = false)
    {
        $authorizerAppId = $filter['authorizer_appid'];
        $userId = $filter['user_id'] ?? 0;
        unset($filter['authorizer_appid'], $filter['user_id']);

        $result = $this->entityRepository->getInfo($filter);
        if (!$result) {
            return $result;
        }
        $operator = $this->getOperatorData($result['company_id'], $result['operator_id']);
        $result['author'] = $result['author'] ?: ($operator[$result['operator_id']]['username'] ?? '');
        $result['head_portrait'] = $result['head_portrait'] ?: ($operator[$result['operator_id']]['head_portrait'] ?? 0);
        if ($result['article_type'] != 'bring') {
            return $result;
        }
        $result['content'] = $this->proArticleContent($result['content'], $authorizerAppId, $userId);
        if (!$isHaveCount) {
            return $result;
        }
        $params = [
            'company_id' => $result['company_id'],
            'article_id' => $result['article_id'],
        ];
        $result['articleFocusNum'] = $this->articleFocusNum($params); //文章关注条数
        $result['articlePraiseNum'] = $this->articlePraiseNum($params);  //文章点赞数
        $result['isPraise'] = false;  //文章是否被点赞
        if ($userId) {
            $params['user_id'] = $userId;
            $result['isPraise'] = $this->articlePraiseCheck($params)['status'];  //文章是否被点赞
        }
        return $result;
    }

    private function getOperatorData($companyId, $operatorIds)
    {
        $filter = ['company_id' => $companyId, 'operator_id' => $operatorIds];
        $operatorsService = new OperatorsService();
        $operatorList = $operatorsService->operatorsRepository->lists($filter);
        $result = array_column($operatorList['list'], null, 'operator_id');
        return $result;
    }

    public function proArticleContent($content, $authorizerAppId = '', $userId = 0, $dataSource = '')
    {
        if (is_array($content)) {
            $goodsIds = [];
            foreach ($content as $key => $value) {
                if (isset($value['base']['padded'])) {
                    $content[$key]['base']['padded'] = (!$value['base']['padded'] || $value['base']['padded'] === 'false') ? false : true;
                }
                if ($value['name'] == 'goods' && $value['data']) {
                    $goodsId = array_column($value['data'], 'item_id');
                    $goodsIds = array_merge($goodsIds, $goodsId);
                }
                if ($value['name'] == 'film' && isset($value['data'])) {
                    foreach ($value['data'] as $k => $val) {
                        if (isset($val['media_id'])) {
                            $videoUrl = $this->getVideoPicUrl($val['media_id'], $authorizerAppId);
                            if ($videoUrl) {
                                $content[$key]['data'][$k]['url'] = $videoUrl;
                            }
                        }
                    }
                }
                if (isset($value['config'])) {
                    if (isset($value['config']['content'])) {
                        $content[$key]['config']['content'] = (!$value['config']['content'] || $value['config']['content'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['dot'])) {
                        $content[$key]['config']['dot'] = (!$value['config']['dot'] || $value['config']['dot'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['dotCover'])) {
                        $content[$key]['config']['dotCover'] = (!$value['config']['dotCover'] || $value['config']['dotCover'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['height'])) {
                        $content[$key]['config']['height'] = intval($value['config']['height']);
                    }
                    if (isset($value['config']['interval'])) {
                        $content[$key]['config']['interval'] = intval($value['config']['interval']);
                    }
                    if (isset($value['config']['padded'])) {
                        $content[$key]['config']['padded'] = (!$value['config']['padded'] || $value['config']['padded'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['rounded'])) {
                        $content[$key]['config']['rounded'] = (!$value['config']['rounded'] || $value['config']['rounded'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['bold'])) {
                        $content[$key]['config']['bold'] = (!$value['config']['bold'] || $value['config']['bold'] === 'false') ? false : true;
                    }
                    if (isset($value['config']['italic'])) {
                        $content[$key]['config']['italic'] = (!$value['config']['italic'] || $value['config']['italic'] === 'false') ? false : true;
                    }
                }
            }
            $itemData = $this->getItemsData($goodsIds, $dataSource);
            $collect = $this->getUserItemCollect($userId, $goodsIds);
            if ($itemData) {
                foreach ($content as $key => $value) {
                    if ($value['name'] != 'goods') {
                        continue;
                    }
                    if ($value['name'] == 'goods' && !$value['data']) {
                        continue;
                    }
                    foreach ($value['data'] as $k => $item) {
                        if (!isset($itemData[$item['item_id']])) {
                            continue;
                        }
                        $content[$key]['data'][$k]['item_name'] = $itemData[$item['item_id']]['item_name'];
                        $content[$key]['data'][$k]['img_url'] = $itemData[$item['item_id']]['pics'][0] ?? '';
                        $content[$key]['data'][$k]['price'] = $itemData[$item['item_id']]['price'];
                        $content[$key]['data'][$k]['sales'] = $itemData[$item['item_id']]['sales'];
                        $content[$key]['data'][$k]['favStatus'] = isset($collect[$item['item_id']]) ? true : false;
                        $content[$key]['data'][$k]['itemStatus'] = $itemData[$item['item_id']]['approve_status'] == 'onsale' ? true : false;
                    }
                }
            }
        }
        return $content;
    }

    private function getItemsData($goodsIds, $dataSource= '')
    {
        if ($goodsIds) {
            
            $filter['item_id'] = $goodsIds;
            $cols = ['item_id', 'item_name', 'store', 'brief', 'approve_status', 'price', 'pics', 'sales'];
            if ($dataSource == 'supplier_goods') {
                $itemService = new SupplierItemsService();
                $items = $itemService->repository->lists($filter, $cols, 1, -1);
            } else {
                $itemService = new ItemsService();
                $items = $itemService->itemsRepository->list($filter, [], -1, 1, $cols);
            }
            return array_column($items['list'], null, 'item_id');
        }
        return [];
    }

    private function getUserItemCollect($userId, $itemIds)
    {
        if ($userId && $itemIds) {
            $filter['user_id'] = $userId;
            $filter['item_id'] = $itemIds;
            $memberItemsFavService = new MemberItemsFavService();
            $result = $memberItemsFavService->lists($filter);
            return array_column($result['list'], null, 'item_id');
        }
        return [];
    }

    private function getVideoPicUrl($mediaId, $authorizerAppId = '')
    {
        if ($authorizerAppId) {
            $service = new MaterialService();
            $service = $service->application($authorizerAppId);
            $detail = $service->getMaterial($mediaId);
            if (isset($detail['down_url']) && $detail['down_url']) {
                return $detail['down_url'];
            }
        }
        return '';
    }

    public function updateArticleStatusOrSort($companyId, $params)
    {
        foreach ($params as $value) {
            $inputdata = [];
            if (!isset($value['article_id'])) {
                throw new ResourceException("文章编辑参数有误");
            }
            $filter['article_id'] = $value['article_id'];
            if (isset($value['release_status'])) {
                $inputdata['release_status'] = (!$value['release_status'] || $value['release_status'] === 'false') ? false : true;
                $inputdata['release_time'] = (!$value['release_status'] || $value['release_status'] === 'false') ? 0 : time();
            }
            if (isset($value['sort'])) {
                $inputdata['sort'] = $value['sort'];
            }
            if (!$inputdata) {
                throw new ResourceException("文章编辑参数有误");
            }
            $result[] = $this->entityRepository->updateOneBy($filter, $inputdata);
        }
        return true;
    }
}
