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

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;
use MembersBundle\Services\MemberService;
use PromotionsBundle\Entities\LimitCategoryPromotions;
use PromotionsBundle\Entities\LimitItemPromotions;
use PromotionsBundle\Entities\LimitPersonPromotions;
use PromotionsBundle\Entities\LimitPromotions;
use PromotionsBundle\Repositories\LimitRepository;
use PromotionsBundle\Traits\CheckPromotionsValid;
use PromotionsBundle\Traits\CheckPromotionsRules;

class LimitService
{
    use CheckPromotionsValid;
    use CheckPromotionsRules;
    /**
     * @var $limitRepository LimitRepository
     */
    private $limitRepository;
    /**
     * limitItemPromotions Repository类
     */
    private $limitItemRepository;

    private $limitCategoryRepository;

    public function __construct()
    {
        $this->limitRepository = app('registry')->getManager('default')->getRepository(LimitPromotions::class);
        $this->limitItemRepository = app('registry')->getManager('default')->getRepository(LimitItemPromotions::class);
        $this->limitCategoryRepository = app('registry')->getManager('default')->getRepository(LimitCategoryPromotions::class);
    }

    /**
     * 获取所有的限购活动活动列表
     * @param int $companyId 公司id
     * @param string $status 活动状态 waiting 等待开启| ongoing 进行中| end 已结束| all 全部活动
     * @param int $page 分页页数
     * @param int $pageSize 分页条数
     * @param array $orderBy 排序字段
     * @return mixed
     */
    public function getLimitList($companyId, $status, $sourceType, $sourceId, $page, $pageSize, $orderBy = ["created" => "DESC"])
    {
        $filter['company_id'] = $companyId;
        switch ($status) {
            case 'waiting':
                $filter['start_time|gt'] = time();
                break;
            case 'ongoing':
                $filter['start_time|lt'] = time();
                $filter['end_time|gt'] = time();
                break;
            case 'end':
                $filter['end_time|lt'] = time();
                break;
            default:
                break;
        }

        if ($sourceId > 0) {
            switch ($sourceType) {
                case 'distributor'://按店铺ID筛选
                    $filter['source_id'] = $sourceId;
                    $filter['source_type'] = $sourceType;
                    break;
            }
        }

        $result = $this->limitRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($result['list'] as &$v) {
            if ($v['start_time'] > time()) {
                $v['status'] = 'waiting';
            } elseif ($v['end_time'] < time()) {
                $v['status'] = 'end';
            } else {
                $v['status'] = 'ongoing';
            }

            if ($v['error_desc']) {
                $v['error_desc'] = count(explode(';', $v['error_desc'])) . '个错误';
            } else {
                $v['error_desc'] = '';
            }
        }
        return $result;
    }

    /**
     * 创建限时特惠活动
     * @param array $params 商品参数
     * @return mixed
     * @throws \Exception
     */
    public function createLimitPromotions(array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->_convertArray($params);
            $this->checkActivity($params);
            //$this->checkActivityValid($params['company_id'], $params['items'], $params['start_time'], $params['end_time']);
            $data = [
                'company_id' => $params['company_id'],
                'limit_name' => $params['limit_name'],
                'limit_type' => $params['limit_type'],
                'valid_grade' => implode(',', $params['valid_grade']),
                'rule' => json_encode(['day' => $params['day'], 'limit' => $params['limit'],]),
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
                'source_id' => $params['source_id'],
                'source_type' => $params['source_type'],
            ];
            $this->checkParams($params, $data);
            //不判断活动冲突
            $this->checkActivityValidByLimit($data, $params['items']);
            $result = $this->limitRepository->create($data);
            if ($result && $data['use_bound'] == 2 && isset($params['item_category']) && is_array($params['item_category'])) {
                foreach ($params['item_category'] as $k => $categoryId) {
                    $category_data['category_id'] = $categoryId;
                    $category_data['company_id'] = $result['company_id'];
                    $category_data['limit_id'] = $result['limit_id'];
                    $this->limitCategoryRepository->create($category_data);
                }
            }

            $result['items'] = [];
            if ($result && $params['limit_type'] == 'global') {
                $result['items'] = $this->createLimitPromotionsItemRel($result, $params);
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * 校验组合活动参数
     * @param $params
     * @return bool
     */
    private function checkActivity($params)
    {
        if (!isset($params['items']) || !is_array($params['items'])) {
            if ($params['use_bound'] == 'goods') {
                throw new ResourceException(trans('PromotionsBundle.no_activity_products_please_add'));
            }
        }

        $itemsService = new ItemsService();
        //todo 这里需要改成检测标签/分类/品牌下的商品是否有赠品
        if ($params['use_bound'] == 'goods' && $itemsService->__checkIsGiftItem($params['company_id'], $params['items'])) {
            throw new ResourceException(trans('PromotionsBundle.gift_exists_please_check'));
        }
        if (!$params['start_time'] || !$params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_time_required'));
        }

        if ($params['start_time'] > $params['end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_end_time'));
        }

        if (!isset($params['valid_grade']) || !$params['valid_grade']) {
            throw new ResourceException(trans('PromotionsBundle.please_select_applicable_members'));
        }

        return true;
    }

    /**
     * 创建限购活动关联商品
     * @param array $limit 限购活动主表信息
     * @param array $params 限购活动参数
     * @return array
     */
    private function createLimitPromotionsItemRel(array $limit, array $params)
    {
        $result = [];
        $items = [];
        //如果是标签，品牌，类目，不需要关联 items
        if ($params['use_bound'] != 'goods') {
            switch ($params['use_bound']) {
                case 'category': $items = $params['item_category']; break;
                case 'tag': $items = $params['tag_ids']; break;
                case 'brand': $items = $params['brand_ids']; break;
            }
            foreach ($items as $v) {
                $data['limit_id'] = $limit['limit_id'];
                $data['limit_num'] = $params['limit'] ?? 0;
                $data['distributor_id'] = 0;
                $data['item_id'] = $v;
                $data['item_type'] = $params['use_bound'];
                $data['item_name'] = '';
                $data['company_id'] = $limit['company_id'];
                $data['item_spec_desc'] = '';
                $data['pics'] = '';
                $data['price'] = 0;
                $data['start_time'] = $limit['start_time'];
                $data['end_time'] = $limit['end_time'];
                $result[] = $this->limitItemRepository->create($data);
            }
            return $result;
        }

        $itemService = new ItemsService();
        $itemIds = $params['items'];
        $filter = [
            'company_id' => $params['company_id'],
            'item_id' => $itemIds,
        ];
        $items = $itemService->getSkuItemsList($filter);
        $items = array_column($items['list'], null, 'item_id');
        foreach ($items as $v) {

            //时间存在交集，相同店铺下，不允许出现相同的商品
            $rsLimitItemCount = $this->limitItemRepository->count([
                'company_id' => $limit['company_id'],
                'distributor_id' => 0,
                'item_id' => $v['item_id'],
                'start_time|lte' => $limit['end_time'],
                'end_time|gte' => $limit['start_time'],
            ]);
            if ($rsLimitItemCount > 0) {
                throw new ResourceException(trans('PromotionsBundle.product_already_in_limit') . $v['item_name']);
            }

            $data['limit_id'] = $limit['limit_id'];
            $data['limit_num'] = $params['limit'] ?? 0;
            $data['distributor_id'] = 0;
            $data['item_id'] = $v['item_id'];
            $data['item_name'] = $v['item_name'];
            $data['company_id'] = $limit['company_id'];
            $data['item_spec_desc'] = $items[$v['item_id']]['item_spec_desc'] ?? '';
            $data['pics'] = isset($items[$v['item_id']]['pics']) && !empty($items[$v['item_id']]['pics']) ? $items[$v['item_id']]['pics'][0] : '';
            $data['price'] = $items[$v['item_id']]['price'] ?? '';
            $data['start_time'] = $limit['start_time'];
            $data['end_time'] = $limit['end_time'];
            $result[] = $this->limitItemRepository->create($data);
        }
        return $result;
    }

    /**
     * 修改限时特惠活动
     * @param int $limitId 限购活动id
     * @param array $params 商品参数
     * @return mixed
     * @throws \Exception
     */
    public function updateLimitPromotions($limitId, array $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->_convertArray($params);
            $this->checkActivity($params);

            //$this->checkActivityValid($params['company_id'], $params['items'], $params['start_time'], $params['end_time'], $limitId);
            $data = [
                'company_id' => $params['company_id'],
                'limit_name' => $params['limit_name'],
                'limit_type' => $params['limit_type'] ?? 'global',
                'valid_grade' => implode(',', $params['valid_grade']),
                'rule' => json_encode(['day' => $params['day'], 'limit' => $params['limit'],]),
                'start_time' => $params['start_time'],
                'end_time' => $params['end_time'],
            ];
            if ($params['limit_type'] == 'global') {
                $data['error_desc'] = '';
                $data['total_item_num'] = 0;
                $data['valid_item_num'] = 0;
            }
            $this->checkParams($params, $data);
            //不判断活动冲突
            $this->checkActivityValidByLimit($data, $params['items'], $limitId);
            $result = $this->limitRepository->updateOneBy(['limit_id' => $limitId], $data);

            $this->limitCategoryRepository->deleteBy(['company_id' => $result['company_id'], 'limit_id' => $result['limit_id']]);
            if ($result && $data['use_bound'] == 2 && isset($params['item_category']) && is_array($params['item_category'])) {
                foreach ($params['item_category'] as $k => $categoryId) {
                    $category_data['category_id'] = $categoryId;
                    $category_data['company_id'] = $result['company_id'];
                    $category_data['limit_id'] = $result['limit_id'];
                    $this->limitCategoryRepository->create($category_data);
                }
            }

            $result['items'] = [];
            if ($result && $params['limit_type'] == 'global') {
                $result['items'] = $this->updateLimitPromotionsItemRel($result, $params);
            }
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException($e->getMessage());
        }
    }

    public function checkParams(&$params = [], &$data = [])
    {
        if ($params['use_bound'] == 'goods') {
            if (!$params['items']) {
                throw new ResourceException(trans('PromotionsBundle.please_select_products'));
            }
            $data['use_bound'] = 1;
            $data['tag_ids'] = [];
            $data['brand_ids'] = [];
        }

        if ($params['use_bound'] == 'category') {
            if (empty($params['item_category'])) {
                throw new ResourceException(trans('PromotionsBundle.please_select_main_category'));
            }
            $data['use_bound'] = 2;
            $data['item_category'] = $params['item_category'];
            $data['tag_ids'] = [];
            $data['brand_ids'] = [];
        }

        if ($params['use_bound'] == 'tag') {
            if (!isset($params['tag_ids']) || empty($params['tag_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.please_select_tags'));
            }
            $data['tag_ids'] = $params['tag_ids'];
            $data['use_bound'] = 3;
            $data['brand_ids'] = [];
        }

        if ($params['use_bound'] == 'brand') {
            if (!isset($params['brand_ids']) || empty($params['brand_ids'])) {
                throw new ResourceException(trans('PromotionsBundle.please_select_brand'));
            }
            $data['use_bound'] = 4;
            $data['brand_ids'] = $params['brand_ids'];
            $data['tag_ids'] = [];
        }
    }

    /**
     * 修改限购活动关联商品
     * @param array $limit 限购活动主表信息
     * @param array $params 限购活动参数
     * @param int $limitTotalPrice 限购活动总价格
     * @return array
     */
    private function updateLimitPromotionsItemRel($limit, $params)
    {
        $this->deleteLimitPromotionsItemRel($limit['company_id'], $limit['limit_id']);
        $result = $this->createLimitPromotionsItemRel($limit, $params);
        return $result;
    }

    /**
     * 删除限购活动关联商品
     * @param int $companyId 公司id
     * @param int $limitId 限购活动活动id
     * @return array
     */
    public function deleteLimitPromotionsItemRel($companyId, $limitId)
    {
        $filter = [
            'limit_id' => $limitId,
            'company_id' => $companyId,
        ];
        return $this->limitItemRepository->deleteBy($filter);
    }

    /**
     * 取消限购活动
     * @param int $limitId 限购活动id
     * @return mixed
     * @throws \Exception
     */
    public function cancelLimitPromotions($limitId, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'company_id' => $companyId,
                'limit_id' => $limitId,
            ];
            $params = [
                'end_time' => time() - 1
            ];
            $result = $this->limitRepository->updateOneBy($filter, $params);
            $this->limitItemRepository->updateBy($filter, $params);
            $conn->commit();
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw new ResourceException(trans('PromotionsBundle.cancel_failed'));
        }
    }

    /**
     * 创建用户限购记录
     * @param $params
     * @return bool
     */
    public function createLimitPerson($params)
    {
        $limitPersonRepository = app('registry')->getManager('default')->getRepository(LimitPersonPromotions::class);
        $filterPerson = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'item_id' => $params['item_id'],
            'limit_id' => $params['limit_id'],
            'distributor_id' => $params['distributor_id'],
        ];
        $filterPerson['start_time|lt'] = time();
        $filterPerson['end_time|gt'] = time();
        $limitItemInfo = $this->getLimitPersonInfo($filterPerson);
        if (!$limitItemInfo) {
            $limitParams = [
                'limit_id' => $params['limit_id'],
                'company_id' => $params['company_id'],
            ];
            $limitInfo = $this->limitRepository->getInfo($limitParams);

            $data = [
                'limit_id' => $params['limit_id'],
                'user_id' => $params['user_id'],
                'item_id' => $params['item_id'],
                'company_id' => $params['company_id'],
                'number' => $params['number'],
                'distributor_id' => $params['distributor_id'],
            ];
            if ($params['day'] == 0) {
                $data['start_time'] = $limitInfo['start_time'];
                $data['end_time'] = $limitInfo['end_time'];
            } else {
                $data['start_time'] = strtotime(date('Y-m-d'));
                $data['end_time'] = strtotime(date('Y-m-d')) + $params['day'] * 24 * 3600;
            }
            $result = $limitPersonRepository->create($data);
        } else {
            $filterPerson = [
                'limit_id' => $params['limit_id'],
                'item_id' => $params['item_id'],
                'user_id' => $params['user_id'],
                'company_id' => $params['company_id'],
                'distributor_id' => $params['distributor_id'],
            ];
            $data = [
                'number' => $params['number'] + $limitItemInfo['number'],
            ];
            $result = $limitPersonRepository->updateOneBy($filterPerson, $data);
        }

        return $result;
    }

    /**
     * 创建删除限购
     * @param $params
     * @return bool
     */
    public function reduceLimitPerson($params)
    {
        $limitPersonRepository = app('registry')->getManager('default')->getRepository(LimitPersonPromotions::class);
        $filterPerson = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'item_id' => $params['item_id'],
        ];
        $filterPerson['start_time|lt'] = time();
        $filterPerson['end_time|gt'] = time();
        $limitItemInfo = $this->getLimitPersonInfo($filterPerson);
        if ($limitItemInfo) {
            if ($limitItemInfo['number'] > $params['number']) {
                $data = [
                    'number' => $limitItemInfo['number'] - $params['number'],
                ];
            } else {
                $data = [
                    'number' => 0,
                ];
            }
            $filterPerson = ['id' => $limitItemInfo['id']];
            $limitPersonRepository->updateOneBy($filterPerson, $data);
            return true;
        }
        return true;
    }

    /**
     * 获取商品限购信息
     * @param $params
     * @return mixed
     */
    public function getLimitItemInfo($params)
    {
        $limitItemInfo = [];
        $info = $this->limitItemRepository->lists($params, '*', 1, 100, ["created" => "DESC"]);
        if ($info['total_count'] > 0) {
            //找到最小的限购数量，防止出现重复的多条记录
            foreach ($info['list'] as $v) {
                if (!$limitItemInfo or $limitItemInfo['limit_num'] > $v['limit_num']) {
                    $limitItemInfo = $v;
                }
            }
        }
        return $limitItemInfo;
    }

    //更新商品限购数量
    public function updateLimitItem($companyId, $distributorId, $itemId, $limitNum, $limitId)
    {
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'item_id' => $itemId,
            'limit_id' => $limitId,
        ];
        $data = [
            'limit_num' => $limitNum,
        ];
        return $this->limitItemRepository->updateOneBy($filter, $data);
    }

    //删除单个商品限购
    public function deleteLimitItem($companyId, $distributorId, $itemId)
    {
        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'item_id' => $itemId,
        ];
        return $this->limitItemRepository->deleteBy($filter);
    }

    public function deleteLimitItemBy($filter)
    {
        return $this->limitItemRepository->deleteBy($filter);
    }

    public function updateLimitItemTime($filter, $params)
    {
        $data = [
            'start_time' => $params['start_time'],
            'end_time' => $params['end_time'],
        ];
        return $this->limitItemRepository->updateBy($filter, $data);
    }

    public function getLimitItemList($companyId, $limitId, $itemType, $itemBn = '', $page = 1, $pageSize = -1)
    {
        $itemsService = new ItemsService();
        $filter = [
            'company_id' => $companyId,
            'limit_id' => $limitId,
            'item_type' => $itemType,
            'distributor_id|gt' => 0,
        ];

        if ($itemBn) {
            $itemIds = $itemsService->getItemIds(['item_bn|contains' => $itemBn], 1, 100);
            if ($itemIds) {
                $filter['item_id'] = $itemIds;
            } else {
                return ['total_count' => 0, 'list' => []];
            }
        }

        $info = $this->limitItemRepository->lists($filter, 'limit_id,item_id,limit_num,distributor_id', $page, $pageSize, ["item_id" => "ASC"]);
        $limitItems = $info['list'];
        if ($limitItems) {

            //转换店铺名称
            $distributorInfo = [];
            $distributorIds = array_column($limitItems, 'distributor_id');
            if ($distributorIds) {
                $distributorService = new DistributorService();
                $filter = [
                    'company_id' => $companyId,
                    'distributor_id' => $distributorIds,
                ];
                $distributors = $distributorService->getDistributorOriginalList($filter);

                if ($distributors['list']) {
                    $distributorInfo = array_column($distributors['list'], null, 'distributor_id');
                }
            }

            //转换商品名称
            $itemInfo = [];
            $itemIds = array_column($limitItems, 'item_id');
            if ($itemIds) {
                $items = $itemsService->getItems($itemIds, $companyId, ['item_name', 'item_id', 'item_bn']);
                if ($items) {
                    $itemInfo = array_column($items, null, 'item_id');
                }
            }

            $no = $info['total_count'] - ($page - 1) * $pageSize;
            foreach ($info['list'] as &$v) {
                $v['no'] = $no;
                $v['item_name'] = $itemInfo[$v['item_id']]['item_name'] ?? trans('PromotionsBundle.unknown_product');
                $v['item_bn'] = $itemInfo[$v['item_id']]['item_bn'] ?? '';
                $v['shop_code'] = $distributorInfo[$v['distributor_id']]['shop_code'] ?? '';
                $v['shop_name'] = $distributorInfo[$v['distributor_id']]['name'] ?? '';
                $no--;
            }
        }
        return $info;
    }

    /**
     * 获取商品限购信息
     * @param $params
     * @return mixed
     */
    public function getLimitItemInfoNew($params)
    {
        $itemFilter = [];
        $filters = [];
        $itemInfo = [];//商品ID和商品属性(品牌，标签，分类)的关系

        $itemIds = $params['item_id'];
        $companyId = $params['company_id'];
        $filterType = 'item_id';

        //商品id转换成主商品ID
        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemIds, $companyId, null, $filterType);
        if (!$items) {
            return false;
        }

        $itemFilter['default_item_id'] = array_column($items, 'default_item_id');
        $itemFilter['item_main_cat_id'] = array_column($items, 'item_main_cat_id');
        $itemFilter['brand_id'] = array_column($items, 'brand_id');

        //获取商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $companyId,
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getItemsByTagidsLimit($tagFilter, 1, -1);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_unique(array_column($tagList['list'], 'tag_id'));
        }

        //指定商品查询
        if ($itemIds) {
            $filters[] = [
                'item_id' => $itemIds,
                'item_type' => 'normal',
            ];
        }

        //根据标签查询
        if ($itemFilter['tag_ids']) {
            $filters[] = [
                'item_id' => $itemFilter['tag_ids'],
                'item_type' => 'tag',
            ];
        }

        //根据品牌查询
        if ($itemFilter['brand_id']) {
            $filters[] = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
            ];
        }

        //根据主类目查询
        if ($itemFilter['item_main_cat_id']) {
            $filters[] = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
            ];
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('limit_id,item_id,item_type,limit_num')->from('promotions_limit_item');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    $criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relItemArr = $criteria->execute()->fetchAll();
        return $relItemArr ? $relItemArr[0] : [];
    }

    /**
     * 获取限购活动信息
     * @param int $companyId 公司id
     * @param int $limitId 限购活动id
     * @param int $distributorId 店铺ID
     * @return mixed
     */
    public function getLimitInfo($companyId, $limitId, $distributorId = -1)
    {
        $limitParams = [
            'limit_id' => $limitId,
            'company_id' => $companyId,
        ];
        $info = $this->limitRepository->getInfo($limitParams);
        $info['start_time'] = date('Y-m-d H:i:s', $info['start_time']);
        $info['end_time'] = date('Y-m-d H:i:s', $info['end_time']);
        $limitItemParams = [
            'limit_id' => $limitId,
            'company_id' => $companyId,
            'item_type' => 'normal',//只查询指定的商品。不查询分类，标签等。
        ];
        if ($distributorId >= 0) {
            $limitItemParams['distributor_id'] = $distributorId;//店铺id=0，说明是全局限购
        }
        $relLists = $this->limitItemRepository->lists($limitItemParams, '*', 1, 1000, ["created" => "DESC"]);
        $itemIds = array_column($relLists['list'], 'item_id');

        //获取组合活动包含的商品的所有明细
        $itemService = new ItemsService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($filter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        $relItems = [];
        foreach ($relLists['list'] as $value) {
            if (!($itemdata[$value['item_id']] ?? [])) {
                $value['status'] = 'invalid';
            } else {
                $value['status'] = 'valid';
                $relItems[] = $itemdata[$value['item_id']];
            }
        }

        $info['items'] = $itemsList['list'];
        $info['itemTreeLists'] = $relItems ?? [];
        if ($info['itemTreeLists']) {
            $info['itemTreeLists'] = $itemService->formatItemsList($info['itemTreeLists']);
        }

        //获取分类
        $relCategory = $this->limitCategoryRepository->lists(['company_id' => $companyId, 'limit_id' => $limitId]);
        if ($relCategory) {
            $categoryIds = array_filter(array_column($relCategory['list'], 'category_id'));
            $info['rel_category_ids'] = $categoryIds;
            $info['item_category'] = $categoryIds;
        }

        $info['rel_tag_ids'] = $info['tag_ids'];

        //获取商品标签
        $itemsTagService = new ItemsTagsService();
        $tagFilter['tag_id'] = $info['tag_ids'];
        $tagFilter['company_id'] = $filter['company_id'];
        $tagList = $itemsTagService->getListTags($tagFilter);
        $info['tag_list'] = $tagList['list'];

        $info['rel_brand_ids'] = $info['brand_ids'];
        //获取品牌
        $itemsAttributesService = new ItemsAttributesService();
        $brandFilter['attribute_id'] = $info['brand_ids'];
        $brandFilter['company_id'] = $filter['company_id'];
        $brandFilter['attribute_type'] = 'brand';

        $brandList = $itemsAttributesService->lists($brandFilter, 1, -1);
        $info['brand_list'] = $brandList['list'];

        return $info;
    }

    /**
     * 获取用户限购信息
     * @param $params
     * @return mixed
     */
    public function getLimitPersonInfo($params)
    {
        $maxDistributorNum = 300;//最多支持300家店铺
        $limitPersonRepository = app('registry')->getManager('default')->getRepository(LimitPersonPromotions::class);
        $info = $limitPersonRepository->lists($params, '*', 1, $maxDistributorNum, ["created" => "DESC"]);
        if (!$info['list']) {
            return [];
        }
        $limitIds = array_column($info['list'], 'limit_id');
        $limitFilter = [
            'limit_id' => $limitIds,
        ];
        $limitList = $this->limitRepository->lists($limitFilter, 'limit_id,start_time,end_time');
        $newLimitList = array_bind_key($limitList['list'], 'limit_id');
        $number = 0;
        foreach ($info['list'] as $k => $v) {
            if (($newLimitList[$v['limit_id']]['start_time'] < time())
                && ($newLimitList[$v['limit_id']]['end_time'] > time())
            ) {
                $number += $v['number']; // 累加多个店铺的购买数量
            }
        }
        $result['number'] = $number;
        $result['id'] = $info['list'][0]['id'];
        $result['limit_id'] = $info['list'][0]['limit_id'];
        return $result;
    }

    public function countLimitItem($filter)
    {
        return $this->limitItemRepository->count($filter);
    }

    //这里没有任何地方调用
    /*
    public function createLimitGoods($params)
    {
        $limitService = new  LimitService();
        $filter = [
            'item_id' => $params['item_id'],
            'company_id' => $params['company_id'],
            'start_time|lt' => time(),
            'end_time|gt' => time(),
        ];
        $limitItemInfo = $limitService->getLimitItemInfo($filter);
        // 判断商品是否存在限购活动
        if (!$limitItemInfo) return true;
        $limitInfo = $limitService->getLimitInfo($params['company_id'], $limitItemInfo['limit_id']);

        $memberService = new MemberService();
        $isHaveVip = $memberService->isHaveVip($params['user_id'], $params['company_id'], $limitInfo['valid_grade']);

        if (!$isHaveVip) return true;

        $rule = json_decode($limitInfo['rule']);

        $filterPerson = [
            'company_id' => $params['company_id'],
            'user_id' => $params['user_id'],
            'item_id' => $params['item_id'],
        ];
        $filterPerson['start_time|lt'] = time();
        $filterPerson['end_time|gt'] = time();
        $limitItemInfo = $limitService->getLimitPersonInfo($filterPerson);
        $num = $params['num'] + $limitItemInfo['number'] ?? 0;
        if ($num > $rule['limit']) throw new \Exception("商品{$limitItemInfo['item_name']}库存不足!");

        return true;
    }
    */

    //根据商品分类查询活动ID
    public function getLimitIdByItemCategory($itemCategorys = [], $params = [])
    {
        $limitIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('a.item_id', $itemCategorys))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.brand_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定标签
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items', 'b', 'a.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        return $limitIds;
    }

    public function getLimitIdByItemTags($tagIds = [], $params = [])
    {
        $limitIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定tag
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items_rel_tags', 'd', 'c.item_id = d.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('d.tag_id', $tagIds))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.brand_id')
            ->leftJoin('b', 'items_rel_tags', 'c', 'b.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_category')
            ->leftJoin('b', 'items_rel_tags', 'c', 'b.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        return $limitIds;
    }

    public function getLimitIdByItemBrand($brandIds = [], $params = [])
    {
        $limitIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('a.item_id', $brandIds));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定标签
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items', 'b', 'c.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.limit_id)')
            ->from('promotions_limit_item', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_category')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $limitIds = array_merge($limitIds, array_column($list, 'limit_id'));

        return $limitIds;
    }

    //根据商品ID获取所有的限购活动
    public function getLimitItemsByItemIds($itemIds = [], $companyId = 0, $filter = [])
    {
        $limitItems = [];
        $limitItemInfo = [];

        //商品id转换成主商品ID
        $itemsService = new ItemsService();
        $fields = ['item_id', 'default_item_id', 'item_category', 'brand_id'];
        $items = $itemsService->getItems($itemIds, $companyId, $fields);
        if (!$items) {
            return false;//没有冲突
        }

        $itemFilter = [];
        $itemFilter['default_item_id'] = array_column($items, 'default_item_id');
        $itemFilter['item_main_cat_id'] = array_column($items, 'item_main_cat_id');
        $itemFilter['brand_id'] = array_column($items, 'brand_id');

        //获取商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $companyId,
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getItemsByTagidsLimit($tagFilter, 1, -1);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_unique(array_column($tagList['list'], 'tag_id'));
        }

        //指定商品查询
        if ($itemIds) {
            $filter['item_type'] = 'normal';
            $filter['item_id'] = $itemIds;
            $rs = $this->limitItemRepository->lists($filter);
            if ($rs['list']) {
                $limitItems = array_merge($limitItems, $rs['list']);
            }
        }

        //根据主类目查询
        if ($itemFilter['item_main_cat_id']) {
            $filter['item_type'] = 'category';
            $filter['item_id'] = $itemFilter['item_main_cat_id'];
            $rs = $this->limitItemRepository->lists($filter);
            if ($rs['list']) {
                $limitItems = array_merge($limitItems, $rs['list']);
            }
        }

        //根据标签查询
        if ($itemFilter['tag_ids']) {
            $filter['item_type'] = 'tag';
            $filter['item_id'] = $itemFilter['tag_ids'];
            $rs = $this->limitItemRepository->lists($filter);
            if ($rs['list']) {
                $limitItems = array_merge($limitItems, $rs['list']);
            }
        }

        //根据品牌查询
        if ($itemFilter['brand_id']) {
            $filter['item_type'] = 'brand';
            $filter['item_id'] = $itemFilter['brand_id'];
            $rs = $this->limitItemRepository->lists($filter);
            if ($rs['list']) {
                $limitItems = array_merge($limitItems, $rs['list']);
            }
        }

        //找到最小的限购数量，防止出现重复的多条记录
        foreach ($limitItems as $v) {
            if ($v['item_type'] == 'normal') {
                $key = $v['item_id'];//单个商品的限购数量
            } else {
                $key = 'special';//分类，标签等，统一合并成一个限购数量
            }
            if (!isset($limitItemInfo[$key]) or $limitItemInfo[$key]['limit_num'] > $v['limit_num']) {
                $limitItemInfo[$key] = $v;
            }
        }

        return $limitItemInfo;
    }

    public function getLimitIdByItems($itemIds = [], $limitData = [], $companyId = 0, $filterType = 'item_id')
    {
        $res = [];
        $limitIds = array_column($limitData, 'limit_id');

        $itemFilter = [];
        $filters = [];
        $itemInfo = [];//商品ID和商品属性(品牌，标签，分类)的关系

        //商品id转换成主商品ID
        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemIds, $companyId, null, $filterType);
        if (!$items) {
            return false;//没有冲突
        }

        $itemFilter['default_item_id'] = array_column($items, 'default_item_id');
        $itemFilter['item_main_cat_id'] = array_column($items, 'item_main_cat_id');
        $itemFilter['brand_id'] = array_column($items, 'brand_id');

        //获取商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $companyId,
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getItemsByTagidsLimit($tagFilter, 1, -1);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_unique(array_column($tagList['list'], 'tag_id'));
        }

        //指定商品查询
        if ($itemIds) {
            $rs = $this->limitItemRepository->lists(['limit_id' => $limitIds, 'item_id' => $itemIds, 'item_type' => 'normal']);
            if ($rs['list']) {
                foreach ($rs['list'] as $v) {
                    $res[] = $v['limit_id'];
                }
            }
        }

        //根据主类目查询
        if ($itemFilter['item_main_cat_id']) {
            //根据 $limitData 查 promotions_limit_category
            $rs = $this->limitCategoryRepository->lists(['limit_id' => $limitIds, 'category_id' => $itemFilter['item_main_cat_id']]);
            if ($rs['list']) {
                $limitInfo = array_column($limitData, 'use_bound', 'limit_id');
                foreach ($rs['list'] as $v) {
                    if ($limitInfo[$v['limit_id']] != 2) {
                        continue;
                    }
                    $res[] = $v['limit_id'];
                }
            }
        }

        //根据标签查询
        if ($itemFilter['tag_ids']) {
            foreach ($limitData as $v) {
                if ($v['use_bound'] != 3) {
                    continue;
                }
                $v['tag_ids'] = json_decode($v['tag_ids'], true);
                if (array_intersect($v['tag_ids'], $itemFilter['tag_ids'])) {
                    $res[] = $v['limit_id'];
                }
            }
        }

        //根据品牌查询
        if ($itemFilter['brand_id']) {
            foreach ($limitData as $v) {
                if ($v['use_bound'] != 4) {
                    continue;
                }
                $v['brand_ids'] = json_decode($v['brand_ids'], true);
                if (array_intersect($v['brand_ids'], $itemFilter['brand_id'])) {
                    $res[] = $v['limit_id'];
                }
            }
        }

        return $res;
    }

    //兼容前端的逗号分隔参数
    private function _convertArray(&$params)
    {
        if (isset($params['valid_grade']) && !is_array($params['valid_grade'])) {
            $params['valid_grade'] = explode(',', $params['valid_grade']);
        }

        if (isset($params['items']) && !is_array($params['items'])) {
            $params['items'] = explode(',', $params['items']);
        }

        if (isset($params['item_category']) && !is_array($params['item_category'])) {
            $params['item_category'] = explode(',', $params['item_category']);
        }

        if (isset($params['tag_ids']) && !is_array($params['tag_ids'])) {
            $params['tag_ids'] = explode(',', $params['tag_ids']);
        }

        if (isset($params['brand_ids']) && !is_array($params['brand_ids'])) {
            $params['brand_ids'] = explode(',', $params['brand_ids']);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->limitRepository->$method(...$parameters);
    }
}
