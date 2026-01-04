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

use MembersBundle\Entities\MemberBrowseHistory;
use GoodsBundle\Services\ItemsService;

use Dingo\Api\Exception\ResourceException;

class MemberBrowseHistoryService
{
    public $limit = 10; //每页默认条数

    public $maxNum = 100; //会员最大存储足迹数量

    private $memberBrowseHistoryRepository;

    /**
     * MemberBrowseHistoryService 构造函数.
     */
    public function __construct()
    {
        $this->memberBrowseHistoryRepository = app('registry')->getManager('default')->getRepository(MemberBrowseHistory::class);
    }

    // 获取商品浏览记录
    public function getBrowseHistoryList($params)
    {
        // app('log')->debug('getBrowseHistoryList_params:'.var_export($params, 1));
        if (!isset($params['user_id']) || !isset($params['company_id'])) {
            throw new ResourceException(trans('MembersBundle/Members.get_user_info_failed'));
        }

        $page = isset($params['page']) ? $params['page'] : 1;

        $pageSize = isset($params['pageSize']) ? $params['pageSize'] : $this->limit;

        $orderBy = ['updated' => 'DESC'];

        $filter = ['user_id' => $params['user_id'], 'company_id' => $params['company_id']];

        $result = $this->memberBrowseHistoryRepository->lists($filter, $page, $pageSize, $orderBy);
        // app('log')->debug('getBrowseHistoryList_result:'.var_export($result, 1));

        if (!$result['list']) {
            return $result;
        }

        $itemIds = [];
        for ($i = count($result['list']) - 1; $i >= 0; $itemIds[] = $result['list'][$i]['item_id'], $i--);

        $itemList = [];
        if (count($itemIds) > 0) {
            $itemsService = new ItemsService();

            $itemList = $itemsService->list(['item_id' => $itemIds]);
            $itemList['list'] = array_column($itemList['list'], null, 'item_id');
        }

        foreach ($result['list'] as $key => $val) {
            $result['list'][$key]['itemData'] = [];
            if (isset($itemList['list'][$val['item_id']]) && $itemList['list'][$val['item_id']]) {
                $result['list'][$key]['itemData'] = $itemList['list'][$val['item_id']];
            }
        }

        return $result;
    }

    // 保存商品浏览记录
    public function saveBrowseHistory($params)
    {
        if (!$params['company_id'] || !$params['item_id'] || !$params['user_id']) {
            app('log')->debug("保存浏览记录缺少参数=>\r\n".var_export($params, 1));
            return false;
        }

        $itemsService = new ItemsService();

        //检测商品是否存在
        $itemInfo = $itemsService->itemsRepository->get($params['item_id']);

        if (!$itemInfo) {
            app('log')->debug("足迹商品获取失败=>\r\n".var_export($params, 1));
            return false;
        }

        $filter = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
        ];

        $filter['item_id'] = isset($itemInfo['default_item_id']) ? $itemInfo['default_item_id'] : $itemInfo['item_id'];

        $historyInfo = $this->memberBrowseHistoryRepository->getInfo($filter);
        try {
            if ($historyInfo) {
                // 更新时间
                $result = $this->memberBrowseHistoryRepository->updateOneBy($filter, ['updated' => time()]);
            } else {
                $this->incrUserHistoryItemCount($params['company_id'], $params['user_id']);
                $result = $this->memberBrowseHistoryRepository->create($params);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * 获取用户组最后一次浏览商品数据
     *
     * @param array $userIds 用户组id
     * @return void
     */
    public function getLastBrowseHistory(array $userIds)
    {
        $conn = app('registry')->getConnection('default');
        $qb2 = $conn->createQueryBuilder();
        $temp = $qb2->select("history_id, user_id, MAX(updated) updated")
            ->from('members_browse_history')
            ->andWhere($qb2->expr()->in('user_id', $userIds))
            ->groupBy('user_id');

        $qb = $conn->createQueryBuilder();
        $qb->select("mbh.*")
            ->from('members_browse_history', 'mbh')
            ->rightjoin('mbh', '(' . $temp . ')', 'temp', 'mbh.user_id = temp.user_id and mbh.updated = temp.updated');
        $list = $qb->execute()->fetchAll();
        if ($list) {
            $itemIds = array_column($list, 'item_id');
            $itemsService = new ItemsService();
            $itemListTemp = $itemsService->list(['item_id' => $itemIds]);
            $itemList = array_column($itemListTemp['list'], null, 'item_id');
            foreach ($list as &$v) {
                if (!isset($itemList[$v['item_id']])) {
                    unset($itemList[$v['item_id']]);
                    continue;
                }
                $v['item_name'] = $itemList[$v['item_id']] ? $itemList[$v['item_id']]['item_name'] : [];
            }
        }
        return $list ?: [];
    }

    /**
     * 用户浏览商品数量
     *
     * @param int $companyId
     * @param int $userId
     * @return void
     */
    public function incrUserHistoryItemCount($companyId, $userId)
    {
        $key = $this->getKey($companyId, $userId);
        $count = (int)app('redis')->connection('members')->incr($key);
        if ($count <= 1) {
            $filter = [
                'company_id' => $companyId,
                'user_id' => $userId,
            ];
            $count = $this->memberBrowseHistoryRepository->count($filter);
            app('redis')->connection('members')->set($key, $count);
        }
        return $count;
    }

    /**
     * 用户浏览商品数量
     *
     * @param int $companyId
     * @param int $userId
     * @return void
     */
    public function getUserHistoryItemCount($companyId, $userId)
    {
        $key = $this->getKey($companyId, $userId);
        $count = app('redis')->connection('members')->get($key);
        if (!$count) {
            $filter = [
                'company_id' => $companyId,
                'user_id' => $userId,
            ];
            $count = $this->memberBrowseHistoryRepository->count($filter);
            app('redis')->connection('members')->set($key, $count);
        }
        return $count;
    }

    /**
     * 获取用户历史访问商品数量键名
     *
     * @param string $companyId
     * @param string $userId
     * @return string
     */
    public function getKey($companyId, $userId)
    {
        $key = 'memberHistory:c:' . $companyId . ':u:' . $userId . ':sum';
        return $key;
    }

    // 获取商品浏览记录
    public function geMembertBrowseList($filter, $page, $pageSize, $orderBy = [])
    {
        $result = $this->memberBrowseHistoryRepository->lists($filter, $page, $pageSize, $orderBy);
        if (!$result['list']) {
            return $result;
        }
        $itemIds = [];
        for ($i = count($result['list']) - 1; $i >= 0; $itemIds[] = $result['list'][$i]['item_id'], $i--);

        $itemList = [];
        if (count($itemIds) > 0) {
            $itemsService = new ItemsService();

            $itemList = $itemsService->list(['item_id' => $itemIds]);
            $itemList['list'] = array_column($itemList['list'], null, 'item_id');
            foreach ($result['list'] as $key => $val) {
                $result['list'][$key]['itemData'] = [];
                if (isset($itemList['list'][$val['item_id']]) && $itemList['list'][$val['item_id']]) {
                    $result['list'][$key]['itemData'] = $itemList['list'][$val['item_id']];
                }
            }
            return $result;
        }
        return [];
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
        return $this->memberBrowseHistoryRepository->$method(...$parameters);
    }
}
