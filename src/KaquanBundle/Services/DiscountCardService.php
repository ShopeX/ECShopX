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

namespace KaquanBundle\Services;

use DistributionBundle\Services\DistributorService;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsCategory;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsTagsService;
use Illuminate\Support\Arr;
use KaquanBundle\Entities\RelMemberTags;
use KaquanBundle\Events\CouponAddEvent;
use KaquanBundle\Events\CouponDeleteEvent;
use KaquanBundle\Events\CouponEditEvent;
use KaquanBundle\Interfaces\KaquanInterface;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Entities\UserDiscount;
use KaquanBundle\Jobs\UploadWechatCard;
use KaquanBundle\Jobs\CreateKaquanJob;
use Dingo\Api\Exception\ResourceException;
use KaquanBundle\Entities\RelItems;

use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberTagsService;
use PromotionsBundle\Services\DateStatusService;
use CompanysBundle\Ego\CompanysActivationEgo;

class DiscountCardService implements KaquanInterface
{
    public $discountCardRepository;
    public $relItemsRepository;

    public const FOR_ALL_ITEMS = 0;
    public const FOR_ASSIGN_ITEMS = 1;
    public const FOR_CATEGORY_ITEMS = 2;
    public const FOR_TAG_ITEMS = 3;
    public const FOR_BRAND_ITEMS = 4;
    public const FOR_EXCEPT_ASSIGN_ITEMS = 5;

    public const MAX_ITEM_TAGS = 100;//最多处理商品的100个标签

    public function __construct()
    {
        $this->discountCardRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $this->relItemsRepository = app('registry')->getManager('default')->getRepository(RelItems::class);
    }

    /**
     * 优惠券来源类型 - 自营店/总店
     */
    public const SOURCE_TYPE_ADMIN = "admin";

    /**
     * 优惠券来源类型 - 店铺
     * 对应的source_id为店铺id
     */
    public const SOURCE_TYPE_DISTRIBUTOR = "distributor";

    /**
     * 卡券状态 - 正常
     */
    public const KQ_STATUS_NORMAL = 0;

    /**
     * add discountCard
     *
     * @param Datainfo $dataInfo
     * @return
     */
    public function createKaquan(array $dataInfo, $appId = '')
    {
        $this->__setParams($dataInfo);
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //根据分类，标签，品牌获取商品
            $dataInfo = $this->getItemIds($dataInfo);//这里不返回商品id
            $result = $this->discountCardRepository->create($dataInfo);
            if (!$result) {
                throw new ResourceException(trans('KaquanBundle.data_create_failed_retry'));
            }

            $dataInfo['card_id'] = $result['card_id'] ?? 0;

            if (isset($dataInfo['rel_item_ids']) && is_array($dataInfo['rel_item_ids'])) {
                //$isShow = false;
                $insertData = [];
                $batchInsertNum = 1000; //每个批次写入
                foreach ($dataInfo['rel_item_ids'] as $k => $itemId) {
                    $isShow = (count($dataInfo['rel_item_ids']) - 1 == $k) ? 1 : 0;
                    $insertData[] = [
                        'item_id' => $itemId,
                        'is_show' => $isShow,
                        'company_id' => $result['company_id'],
                        'card_id' => $result['card_id'],
                        'item_type' => isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal',
                    ];
                    //$this->relItemsRepository->create($data);
                    if (count($insertData) >= $batchInsertNum) {
                        $this->relItemsRepository->createQuick($insertData);
                        $insertData = [];
                    }
                }

                if ($insertData) {
                    $this->relItemsRepository->createQuick($insertData);
                }
            } elseif ($dataInfo['use_all_items'] == 'true') {
                $isShow = true;
                $data['item_id'] = 0;
                $data['is_show'] = $isShow;
                $data['company_id'] = $result['company_id'];
                $data['card_id'] = $result['card_id'];
                $data['item_type'] = isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal';
                $this->relItemsRepository->create($data);
            }

            if ($dataInfo['use_all_items'] == 'category' && isset($dataInfo['item_category']) && is_array($dataInfo['item_category'])) {
                $this->saveRelItems($dataInfo['item_category'], $dataInfo['use_all_items'], $result);
            }
            if ($dataInfo['use_all_items'] == 'tag' && isset($dataInfo['tag_ids'])) {
                $tag_ids = array_filter(explode(',', $dataInfo['tag_ids']));
                $this->saveRelItems($tag_ids, $dataInfo['use_all_items'], $result);
            }

            if ($dataInfo['use_all_items'] == 'brand') {
                $brand_ids = array_filter(explode(',', $dataInfo['brand_ids']));
                $this->saveRelItems($brand_ids, $dataInfo['use_all_items'], $result);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        //异步创建关联商品，这里不需要保存商品明细
        if ($dataInfo['create_queue']) {
            //$gotoJob = (new CreateKaquanJob($dataInfo))->onQueue('slow');
            //app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        //触发事件
        $eventData = [
            'card_id' => $result['card_id'],
            'company_id' => $result['company_id']
        ];
        event(new CouponAddEvent($eventData));

        return $result;
    }

    /**
     * update discountCard
     *
     * @param data $dataInfo
     * @return filter
     */
    public function updateKaquan($dataInfo, $appId = '')
    {
        $this->__setParams($dataInfo);
        $filter['card_id'] = $dataInfo['card_id'];
        $filter['company_id'] = $dataInfo['company_id'];
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            //根据分类，标签，品牌获取商品
            $dataInfo = $this->getItemIds($dataInfo);//这里不返回商品ID
            $result = $this->discountCardRepository->update($dataInfo, $filter);
            if (!$result) {
                throw new ResourceException(trans('KaquanBundle.data_update_failed_retry'));
            }

            //$this->relItemsRepository->deleteBy($filter);
            $this->relItemsRepository->deleteQuick($filter);
            if (isset($dataInfo['rel_item_ids']) && is_array($dataInfo['rel_item_ids'])) {
                $insertData = [];
                $batchInsertNum = 1000;
                foreach ($dataInfo['rel_item_ids'] as $k => $itemId) {
                    $isShow = (count($dataInfo['rel_item_ids']) - 1 == $k) ? 1 : 0;
                    $insertData[] = [
                        'item_id' => $itemId,
                        'is_show' => $isShow,
                        'card_id' => $result['card_id'],
                        'company_id' => $result['company_id'],
                        'item_type' => isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal',
                    ];
                    //$this->relItemsRepository->create($data);
                    if (count($insertData) >= $batchInsertNum) {
                        $this->relItemsRepository->createQuick($insertData);
                        $insertData = [];
                    }
                }
                if ($insertData) {
                    $this->relItemsRepository->createQuick($insertData);
                }
            } elseif ($dataInfo['use_all_items'] == 'true') {
                $isShow = true;
                $data['item_id'] = 0;
                $data['is_show'] = $isShow;
                $data['company_id'] = $result['company_id'];
                $data['card_id'] = $result['card_id'];
                $data['item_type'] = isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal';
                $this->relItemsRepository->create($data);
            }

            if ($dataInfo['use_all_items'] == 'category' && isset($dataInfo['item_category']) && is_array($dataInfo['item_category'])) {
                $this->saveRelItems($dataInfo['item_category'], $dataInfo['use_all_items'], $result);
            }

            if ($dataInfo['use_all_items'] == 'tag' && isset($dataInfo['tag_ids'])) {
                $tag_ids = array_filter(explode(',', $dataInfo['tag_ids']));
                $this->saveRelItems($tag_ids, $dataInfo['use_all_items'], $result);
            }

            if ($dataInfo['use_all_items'] == 'brand') {
                $brand_ids = array_filter(explode(',', $dataInfo['brand_ids']));
                $this->saveRelItems($brand_ids, $dataInfo['use_all_items'], $result);
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }

        //异步创建关联商品。这里不需要创建商品明细
        if ($dataInfo['create_queue']) {
            //$job = (new CreateKaquanJob($dataInfo));
            //app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }

        //触发事件
        $eventData = [
            'card_id' => $result['card_id'],
            'company_id' => $result['company_id']
        ];
        event(new CouponEditEvent($eventData));

        return $result;
    }

    // 保存优惠券的商品关联关系
    public function saveRelItems($items, $item_type, $cardRes)
    {
        $relItems = [];
        foreach ($items as $item_id) {
            $relItems[] = [
                'is_show' => 1,
                'item_id' => $item_id,
                'company_id' => $cardRes['company_id'],
                'card_id' => $cardRes['card_id'],
                'item_type' => $item_type,
            ];
        }
        if ($relItems) {
            $this->relItemsRepository->createQuick($relItems);
        }
    }

    /**
     * delete discountCard
     *
     * @param filter $filter
     * @return
     */
    public function deleteKaquan($filter, $appId = '', $isCheckUserCard = true)
    {
        if ($isCheckUserCard) {
            $userDiscountRepository = app('registry')->getManager('default')->getRepository(UserDiscount::class);
            $userDiscount = $userDiscountRepository->get(['card_id' => $filter['card_id']]);
            if ($userDiscount) {
                throw new ResourceException(trans('KaquanBundle.delete_coupon_failed_members_received'));
            }
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->discountCardRepository->delete($filter);
            if ($result) {
                $this->relItemsRepository->deleteBy($filter);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        if ($result && $appId) {
            //检测是否更新微信端
            $wechatService = new WechatCardService($filter['company_id'], $appId);
            $wechatService->deleteWechatCard($filter);
        }

        //触发事件
        $eventData = [
            'card_id' => $filter['card_id'],
            'company_id' => $filter['company_id']
        ];
        event(new CouponDeleteEvent($eventData));

        return true;
    }

    /**
     * 根据卡券Id获取卡券的定义，购物车结算的时候调用
     *
     * @param $companyId
     * @param $cardId
     * @param array $itemIds
     * @return array
     */
    public function getCardInfoById($companyId = 0, $cardId = 0, $itemIds = [], $defaultItemIds = [])
    {
        if (!$cardId) {
            return [];
        }
        $detail = $this->discountCardRepository->getInfo(['card_id' => $cardId]);
        if (!$detail) {
            return [];
        }

        $filter = [
            'company_id' => $companyId,
            'card_id' => $cardId,
        ];

        $relItemIds = [];
        switch ($detail['use_bound']) {
            case '0':
                break;

            case '1'://指定商品
                $filter['item_type'] = 'normal';
                if ($itemIds) {
                    $filter['item_id'] = $itemIds;
                }
                $relItems = $this->relItemsRepository->lists($filter);
                if ($relItems) {
                    $relItemIds = array_filter(array_column($relItems, 'item_id'));
                    // 顺便获取各商品的限制数量
                    $items = array_map(function ($i) {
                        return Arr::only($i, ['item_id', 'use_limit']);
                    }, $relItems);
                }
                break;

            case '2'://指定主类目
                $filter['item_type'] = 'category';
                $relCategory = $this->relItemsRepository->lists($filter);
                $itemCategory = array_filter(array_column($relCategory, 'item_id'));
                $itemFilter = ['item_category' => $itemCategory, 'company_id' => $companyId];
                if ($itemIds) {
                    $itemFilter['item_id'] = $itemIds;
                }

                $itemsService = new ItemsService();
                $relItemIds = $itemsService->getItemIds($itemFilter);
                break;

            case '3'://指定标签
                $itemFilter = ['tag_id' => $detail['tag_ids'], 'company_id' => $companyId];

                $itemsService = new ItemsService();
                $res = $itemsService->getItemsIdByTags($itemFilter, $defaultItemIds);//标签只和主商品关联
                foreach ($defaultItemIds as $itemId => $defaultItemId) {
                    if (in_array($defaultItemId, $res)) {
                        $relItemIds[] = $itemId;//主商品id还原成商品id
                    }
                }
                break;

            case '4'://指定品牌
                $itemFilter = ['brand_id' => $detail['brand_ids'], 'company_id' => $companyId];
                if ($itemIds) {
                    $itemFilter['item_id'] = $itemIds;
                }

                $itemsService = new ItemsService();
                $relItemIds = $itemsService->getItemIds($itemFilter);
                break;
            case '5':// 指定商品不可选
                $filter['item_type'] = 'normal';
                if (!empty($itemIds)) {
                    // 不可选的商品
                    $relItemList = $this->relItemsRepository->lists(['company_id' => $detail['company_id'], 'card_id' => $detail['card_id']]);
                    $itemIdList = array_filter(array_column($relItemList, 'item_id'));
                    $relItemIds = array_diff($itemIds, $itemIdList);
                }
                break;
        }

        if ($detail['use_bound'] > 0 && !$relItemIds) {
            $relItemIds[] = -1;//没有任何商品适用
        }
        $detail['rel_item_ids'] = implode(',', $relItemIds);
        $detail['rel_items'] = $items ?? [];
        return $detail;
    }

    /**
     * get discoutCard
     *
     * @param filter $filter
     * @return array
     */
    public function getKaquanDetail($filter, $isGetItemData = true, $isGetMemberTags = false)
    {
        if (!$filter) {
            return array();
        }
        $detail = $this->discountCardRepository->get($filter);
        if (!$detail) {
            throw new ResourceException(trans('KaquanBundle.coupon_invalid'));
        }
        $detail = reset($detail);
        if ($detail['text_image_list']) {
            $detail['text_image_list'] = unserialize($detail['text_image_list']);
        }
        if ($detail['time_limit']) {
            $detail['time_limit'] = unserialize($detail['time_limit']);
        }
        $detail['can_share'] = ($detail['can_share'] == 1) ? true : false;
        $detail['can_give_friend'] = ($detail['can_give_friend'] == 1) ? true : false;
        $detail['can_use_with_other_discount'] = ($detail['can_use_with_other_discount'] == 1) ? 'true' : 'false';

        $relShopsIds = explode(',', $detail['rel_shops_ids']);
        if (array_filter($relShopsIds)) {
            unset($detail['rel_shops_ids']);
            foreach ($relShopsIds as $value) {
                if ($value) {
                    $detail['rel_shops_ids'][] = $value;
                }
            }
        } else {
            $detail['rel_shops_ids'] = [];
        }
        $distributorIds = explode(',', $detail['distributor_id']);
        if (count($distributorIds) > 0) {
            unset($detail['distributor_id']);
            foreach ($distributorIds as $value) {
                if (!is_null($value) && is_numeric($value)) {
                    $detail['rel_distributor_ids'][] = $value;
                }
            }
        } else {
            $detail['rel_distributor_ids'] = [];
        }
        $detail['use_all_shops'] = ($detail['use_all_shops'] == 1) ? 'true' : 'false';
        $detail['distributor_info'] = [];
        if ($detail['rel_distributor_ids'] ?? []) {
            $distributorService = new DistributorService();
            $distributorTempList = $distributorService->lists(['distributor_id' => $detail['rel_distributor_ids'], 'company_id' => $filter['company_id']], ['created' => 'desc'], -1);
            $distributorList = array_column($distributorTempList['list'], null, 'distributor_id');
            foreach ($distributorIds as $v) {
                if (isset($distributorList[$v])) {
                    $detail['distributor_info'][] = $distributorList[$v];
                }
            }
            if (!($detail['distributor_info'] ?? [])) {
                $detail['distributor_info'][] = [];
            }
        }
        $detail['use_all_distributor'] = $detail['distributor_info'] ?? [] ? false : true;

        if ($isGetItemData) {
            $itemIds = [];
            $filter = ['company_id' => $detail['company_id'], 'card_id' => $detail['card_id'], 'item_type' => 'normal'];
            $relItem = $this->relItemsRepository->lists($filter);
            if ($relItem) {
                $itemIds = array_filter(array_column($relItem, 'item_id'));
                $detail['rel_item_ids'] = $itemIds;
                $itemMap = [];
                foreach ($relItem as $it) {
                    $itemMap[$it['item_id']] = $it['use_limit'];
                }
                array_map(function ($item) {
                    return Arr::only($item, ['item_id', 'use_limit']);
                }, $relItem);
                if(!empty($itemIds)){                
                    $filter = ['company_id' => $detail['company_id'], 'item_id' => $itemIds];
                    $itemService = new ItemsService();
                    $itemsList = $itemService->getSkuItemsList($filter);
                    $itemdata = array_column($itemsList['list'], null, 'item_id');
                    $itemdata = array_map(function ($item) use ($itemMap) {
                        $item['use_limit'] = $itemMap[$item['item_id']] ?? 0;
                        return $item;
                    }, $itemdata);
                    $detail['itemTreeLists'] = $itemService->formatItemsList($itemdata);
                }
            }
            $detail['use_all_items'] = $itemIds ? 'false' : 'true';
        }
        if ($isGetMemberTags) {
            $filter = ['company_id' => $detail['company_id'], 'card_id' => $detail['card_id']];
            $relMemberTagRepository = app('registry')->getManager('default')->getRepository(RelMemberTags::class);
            $relMemberTags = $relMemberTagRepository->lists($filter);
            $detail['user_tag_ids'] = array_values(array_column($relMemberTags, 'tag_id'));
        }
        //获取分类
        $categoryIds = [];
        $relCategory = $this->relItemsRepository->lists(['company_id' => $detail['company_id'], 'card_id' => $detail['card_id'], 'item_type' => 'category']);
        if ($relCategory) {
            $categoryIds = array_filter(array_column($relCategory, 'item_id'));
            $detail['rel_category_ids'] = $categoryIds;
            $detail['item_category'] = $categoryIds;
        }
        $detail['use_all_items'] = $categoryIds ? 'category' : $detail['use_all_items'];

        //获取标签
        $detail['use_all_items'] = $detail['use_bound'] == self::FOR_TAG_ITEMS ? 'tag' : $detail['use_all_items'];

        //获取品牌
        $detail['use_all_items'] = $detail['use_bound'] == self::FOR_BRAND_ITEMS ? 'brand' : $detail['use_all_items'];

        if (count(explode(',', $detail['use_scenes'])) > 1) {
            $detail['use_scenes'] = "QUICK";
        }

        // receive 字段在数据库中存储为整数 0 或 1，转换为布尔值返回
        $detail['receive'] = ($detail['receive'] == '1' || $detail['receive'] == 1) ? true : false;
        $detail['tag_ids'] = $detail['rel_tag_ids'] = array_filter(explode(',', $detail['tag_ids']));

        //获取商品标签
        $itemsTagService = new ItemsTagsService();
        $tagFilter['tag_id'] = $detail['tag_ids'];
        $tagFilter['company_id'] = $filter['company_id'];
        $tagList = $itemsTagService->getListTags($tagFilter);
        $detail['tag_list'] = $tagList['list'];

        $detail['brand_ids'] = $detail['rel_brand_ids'] = array_filter(explode(',', $detail['brand_ids']));
        //获取品牌
        $itemsAttributesService = new ItemsAttributesService();
        $brandFilter['attribute_id'] = $detail['brand_ids'];
        $brandFilter['company_id'] = $filter['company_id'];
        $brandFilter['attribute_type'] = 'brand';

        $brandList = $itemsAttributesService->lists($brandFilter, 1, -1);
        $detail['brand_list'] = $brandList['list'];

        // 卡券是否已经在活动中的标识
        $detail['is_active'] = false;
        if ($detail['card_type'] == 'new_gift') {
            if ($detail['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                $detail['is_active'] = $detail['send_begin_time'] + 3600 * 24 * intval($detail['begin_date']) < time();
            } elseif ($detail['date_type'] == DiscountNewGiftCardService::DATE_TYPE_SHORT) {
                $detail['is_active'] = intval($detail['begin_date']) < time();
            }
        }
        $detail['is_active'] = $detail['is_active'] && $detail['kq_status'] != DiscountNewGiftCardService::STATUS_INIT; // 未初始化卡券未开启活动
        $detail['grade_ids'] = $detail['grade_ids'] ? explode(',', trim($detail['grade_ids'], ',')) : [];
        $detail['vip_grade_ids'] = $detail['vip_grade_ids'] ? explode(',', trim($detail['vip_grade_ids'], ',')) : [];
        return $detail;
    }


    /**
     * [getKaquanItems 获取优惠券的商品列表]
     * @param array $filter
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getKaquanItems($filter, $withInfo = false)
    {
        if (empty($filter)) {
            return [];
        }
        $cardFilter['card_id'] = $filter['card_id'];
        $cardFilter['company_id'] = $filter['company_id'];
        $cardInfo = $this->discountCardRepository->get($cardFilter);
        if (empty($cardInfo)) {
            return [];
        }
        $cardInfo = reset($cardInfo);
        //卡券类型，可选值有，discount:折扣券;cash:代金券;gift:兑换券
        if (!in_array($cardInfo['card_type'], ['discount','cash', 'new_gift'])) {
            return [];
        }
        $params = [];
        if ($withInfo) {
            $params['card_info'] = $cardInfo;
        }
        //0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用,5:指定商品不可用
        switch ($cardInfo['use_bound']) {
            case '0':
                break;
            case '1':
                $relItem = $this->relItemsRepository->lists(['company_id' => $cardInfo['company_id'], 'card_id' => $cardInfo['card_id']]);
                $params['item_id'] = array_filter(array_column($relItem, 'item_id'));
                break;
            case '2':
                $relCategory = $this->relItemsRepository->lists(['company_id' => $cardInfo['company_id'], 'card_id' => $cardInfo['card_id'], 'item_type' => 'category']);
                $params['item_category'] = array_filter(array_column($relCategory, 'item_id'));
                break;
            case '3':
                $params['tag_id'] = array_filter(explode(',', $cardInfo['tag_ids']));
                break;
            case '4':
                $params['brand_id'] = array_filter(explode(',', $cardInfo['brand_ids']));
                break;
            case '5':
                $relItem = $this->relItemsRepository->lists(['company_id' => $cardInfo['company_id'], 'card_id' => $cardInfo['card_id']]);
                $tmp = array_filter(array_column($relItem, 'item_id'));
                if (!empty($tmp)) {
                    $params['item_id|notIn'] = $tmp;
                }
                break;
            default:
                break;
        }
        return $params;
    }

    /**
     * 获取有效优惠券
     * @param int $page
     * @param int $limit
     * @param array $filter
     * @return mixed
     */
    public function getEffectiveKaquanLists($page = 1, $limit = 50, $filter = [])
    {
        $orderBy = ["created" => "DESC"];
        $lists = $this->discountCardRepository->effectiveLists($filter, $orderBy, $limit, $page);
        return $lists;
    }

    /**
     *  Kaquan discountCard
     *
     * @param offset $offset
     * @param limit $limit
     * @param filter $filter
     * @return array
     */
    public function getKaquanList($page = 1, $limit = 500, $filter = [])
    {
        $count = $this->discountCardRepository->totalNum($filter);
        $lists = [];
        $listData = [];
        $listData['list'] = $lists;
        $listData['pagers']['total'] = $count;
        $listData['total_count'] = $count;
        if ($count) {
            $row = ['*'];
            $lists = $this->discountCardRepository->getList($row, $filter, $page, $limit);
            if (!$lists) {
                return $listData;
            }

            $card_ids = array_column($lists, 'card_id');
            $card_ids = implode(',', $card_ids);
            if (!$card_ids) {
                return $listData;
            }

            $get_nums = app('registry')->getConnection('default')->fetchAll("SELECT card_id, count(*) as num FROM kaquan_user_discount WHERE company_id={$filter['company_id']} AND card_id IN({$card_ids}) GROUP BY card_id");
            if ($get_nums) {
                $get_nums = array_bind_key($get_nums, 'card_id');
            }

            $use_nums = app('registry')->getConnection('default')->fetchAll("SELECT card_id, count(*) as num FROM kaquan_user_discount WHERE company_id={$filter['company_id']} AND status=2 AND card_id in({$card_ids}) GROUP BY card_id");
            if ($use_nums) {
                $use_nums = array_bind_key($use_nums, 'card_id');
            }
            foreach ($lists as &$detail) {
                $detail['get_num'] = intval($get_nums[$detail['card_id']]['num'] ?? 0);
                $detail['use_num'] = intval($use_nums[$detail['card_id']]['num'] ?? 0);
                if ($detail['time_limit']) {
                    $detail['time_limit'] = unserialize($detail['time_limit']);
                }
                //处理返回卡券时间
                if ($detail['date_type'] == "DATE_TYPE_FIX_TERM" || $detail['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                    $detail['begin_day_type'] = $detail['begin_date'];
                    $detail['begin_date'] = time();
                    if (intval($detail['end_date']) <= 0) {
                        $detail['end_date'] = time() + 3600 * 24 * $detail['fixed_term'];
                    }
                }
                // 优惠券的状态
                $detail["date_status"] = DateStatusService::getDateStatus((string)$detail['begin_date'], (string)$detail['end_date']);
            }
        }

        $listData['list'] = $lists;
        $listData['pagers']['total'] = $count;
        $listData['total_count'] = $count;
        return $listData;
    }

    //根据商品属性找到对应的卡券
    public function getCardIds($filter)
    {
        $cardIds = [];
        $relItem = $this->relItemsRepository->lists($filter);
        if ($relItem) {
            $cardIds = array_column($relItem, 'card_id');
        }
        return $cardIds;
    }

    public function getUserCardIds($filter)
    {
        switch ($filter['item_type']) {
            case 'all':
                $useBound = 0;
                break;
            case 'normal':
                $useBound = 1;
                break;
            case 'category':
                $useBound = 2;
                break;
            case 'tag':
                $useBound = 3;
                break;
            case 'brand':
                $useBound = 4;
                break;
            case 'normal_neq':
                $useBound = 5;
                break;
        }

        if ($useBound > 0 && !$filter['item_id']) {
            return [];
        }

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();

        if ($useBound == 0) {
            $criteria = $criteria->from('kaquan_user_discount')
                                 ->andWhere($criteria->expr()->eq('company_id', $filter['company_id']))
                                 ->andWhere($criteria->expr()->eq('user_id', $filter['user_id']))
                                 ->andWhere($criteria->expr()->eq('use_bound', 0));
        } elseif ($useBound == 5) {
            $criteria = $criteria->from('kaquan_user_discount')
                                 ->andWhere($criteria->expr()->eq('company_id', $filter['company_id']))
                                 ->andWhere($criteria->expr()->eq('user_id', $filter['user_id']))
                                 ->andWhere(
                                    $criteria->expr()->eq('use_bound', $useBound),
                                    $criteria->expr()->orX(
                                        ...array_map(function($id) use ($criteria) {
                                            return $criteria->expr()->notLike('rel_item_ids', $criteria->expr()->literal('%,'.$id.',%'));
                                        }, $filter['item_id'])
                                    )
                                );
        } else {
            $criteria = $criteria->from('kaquan_user_discount')
                                 ->andWhere($criteria->expr()->eq('company_id', $filter['company_id']))
                                 ->andWhere($criteria->expr()->eq('user_id', $filter['user_id']))
                                 ->andWhere(
                                    $criteria->expr()->eq('use_bound', $useBound),
                                    $criteria->expr()->orX(
                                        ...array_map(function($id) use ($criteria) {
                                            return $criteria->expr()->like('rel_item_ids', $criteria->expr()->literal('%,'.$id.',%'));
                                        }, $filter['item_id'])
                                    )
                                );
        }
        if (isset($filter['card_id']) && $filter['card_id']) {
            $criteria = $criteria->andWhere($criteria->expr()->eq('card_id', $filter['card_id']));
        }
        $result = $criteria->select('id')->execute()->fetchAll();

        return array_column($result, 'id');
    }

    /**
     * 检查商品的品牌和类目信息是否包含
     *
     * @return void
     */
    public function checkGoodsInfo($itemFilter)
    {
        //商品详情页已经有主类目和品牌，所以不需要查询
        if (isset($itemFilter['item_main_cat_id'])
            && isset($itemFilter['brand_id'])
            && isset($itemFilter['default_item_id'])
        ) {
            return $itemFilter;
        }

        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemFilter['item_id'], $itemFilter['company_id']);
        if (!$items) {
            return $itemFilter;
        }

        foreach ($items as $item) {
            $itemFilter['default_item_id'][] = $item['default_item_id'];
            $itemFilter['item_main_cat_id'][] = $item['item_main_cat_id'];
            $itemFilter['brand_id'][] = $item['brand_id'];
        }

        return $itemFilter;
    }

    /**
     * 根据商品获取可用的优惠券ID
     *
     * @param array $itemFilter 商品筛选条件
     * @return array $cardIds 卡券ID数组
     */
    public function getCardIdsByGoods($itemFilter = [])
    {
        $cardIds = [];
        if (!empty($itemFilter['default_item_id'])) {
            $itemsService = new ItemsService();
            $itemIds = $itemsService->getItemIds(['company_id' => $itemFilter['company_id'],'default_item_id' => $itemFilter['default_item_id']]);
            $itemFilter['item_id'] = array_merge([0], $itemIds);
            $filter = [
                'item_id' => $itemFilter['item_id'],
                'item_type' => 'normal',
                'company_id' => $itemFilter['company_id'],
            ];
            $cardIds = array_merge($cardIds, $this->getCardIds($filter));
        }

        $itemFilter = $this->checkGoodsInfo($itemFilter);

        //查询商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $itemFilter['company_id'],
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getListTags($tagFilter, 1, -1, null, false);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_column($tagList['list'], 'tag_id');
            if (count($itemFilter['tag_ids']) > self::MAX_ITEM_TAGS) {
                $itemFilter['tag_ids'] = array_slice($itemFilter['tag_ids'], 0, self::MAX_ITEM_TAGS);
            }
        }

        //商品标签搜索
        if (isset($itemFilter['tag_ids'])) {
            $filter = [
                'item_id' => $itemFilter['tag_ids'],
                'item_type' => 'tag',
                'company_id' => $itemFilter['company_id'],
            ];
            $cardIds = array_merge($cardIds, $this->getCardIds($filter));
        }

        //指定商品搜索
        if (isset($itemFilter['item_id'])) {
            if (is_array($itemFilter['item_id'])) {
                $itemFilter['item_id'][] = 0;//0表示适用任意商品
            } else {
                $itemFilter['item_id'] = [0, $itemFilter['item_id']];
            }
            $filter = [
                'item_id' => $itemFilter['item_id'],
                'item_type' => 'normal',
                'company_id' => $itemFilter['company_id'],
            ];
            $cardIds = array_merge($cardIds, $this->getCardIds($filter));
            $cardIds = $this->removeAntiSelection($itemFilter['company_id'], $cardIds);
        }

        //商品主类目搜索
        if (isset($itemFilter['item_main_cat_id'])) {
            $filter = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
                'company_id' => $itemFilter['company_id'],
            ];

            $cardIds = array_merge($cardIds, $this->getCardIds($filter));
        }

        //商品品牌搜索
        if (isset($itemFilter['brand_id'])) {
            $filter = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
                'company_id' => $itemFilter['company_id'],
            ];
            $cardIds = array_merge($cardIds, $this->getCardIds($filter));
        }

        return array_unique($cardIds);
    }

    /**
     * 根据商品获取用户可用的优惠券ID
     *
     * @param array $itemFilter 商品筛选条件
     * @return array $userCardIds 卡券ID数组
     */
    public function getUserCardIdsByGoods($itemFilter = [])
    {
        //全部商品
        $filter = [
            'item_type' => 'all',
            'company_id' => $itemFilter['company_id'],
            'user_id' => $itemFilter['user_id'],
            'card_id' => $itemFilter['card_id'] ?? null,
        ];
        $userCardIds = $this->getUserCardIds($filter);

        //指定商品搜索
        if (isset($itemFilter['item_id'])) {
            if (!is_array($itemFilter['item_id'])) {
                $itemFilter['item_id'] = [$itemFilter['item_id']];
            }
            $filter = [
                'item_id' => $itemFilter['item_id'],
                'item_type' => 'normal',
                'company_id' => $itemFilter['company_id'],
                'user_id' => $itemFilter['user_id'],
            ];
            $userCardIds = array_merge($userCardIds, $this->getUserCardIds($filter));

            $filter['item_type'] = 'normal_neq';
            $userCardIds = array_merge($userCardIds, $this->getUserCardIds($filter));
        }

        $itemFilter = $this->checkGoodsInfo($itemFilter);

        //商品主类目搜索
        if (isset($itemFilter['item_main_cat_id'])) {
            $filter = [
                'item_id' => $itemFilter['item_main_cat_id'],
                'item_type' => 'category',
                'company_id' => $itemFilter['company_id'],
                'user_id' => $itemFilter['user_id'],
            ];

            $userCardIds = array_merge($userCardIds, $this->getUserCardIds($filter));
        }

        //查询商品的标签
        $itemFilter['tag_ids'] = [];
        $tagFilter = [
            'item_id' => $itemFilter['default_item_id'],//商品标签只关联到主商品
            'company_id' => $itemFilter['company_id'],
        ];
        $itemsTagsService = new ItemsTagsService();
        $tagList = $itemsTagsService->getListTags($tagFilter, 1, -1, null, false);
        if ($tagList) {
            $itemFilter['tag_ids'] = array_column($tagList['list'], 'tag_id');
            if (count($itemFilter['tag_ids']) > self::MAX_ITEM_TAGS) {
                $itemFilter['tag_ids'] = array_slice($itemFilter['tag_ids'], 0, self::MAX_ITEM_TAGS);
            }
        }
        //商品标签搜索
        if (isset($itemFilter['tag_ids'])) {
            $filter = [
                'item_id' => $itemFilter['tag_ids'],
                'item_type' => 'tag',
                'company_id' => $itemFilter['company_id'],
                'user_id' => $itemFilter['user_id'],
            ];
            $userCardIds = array_merge($userCardIds, $this->getUserCardIds($filter));
        }

        //商品品牌搜索
        if (isset($itemFilter['brand_id'])) {
            $filter = [
                'item_id' => $itemFilter['brand_id'],
                'item_type' => 'brand',
                'company_id' => $itemFilter['company_id'],
                'user_id' => $itemFilter['user_id'],
            ];
            $userCardIds = array_merge($userCardIds, $this->getUserCardIds($filter));
        }

        return array_unique($userCardIds);
    }

    /**
     * 去除反选商品卡券
     *
     * @param $companyId
     * @param array $cardIds
     * @return array
     */
    public function removeAntiSelection($companyId, array $cardIds)
    {
        if (!empty($cardIds)) {
            $filter = [
                'card_id' => $cardIds,
                'use_bound' => 5,
                'company_id' => $companyId
            ];
            $list = $this->discountCardRepository->getLists($filter, 'card_id,use_bound');

            $hasCardIdList = [];
            if (!empty($list)) {
                $hasCardIdList = array_column($list, 'card_id');
                $cardIds = array_diff($cardIds, $hasCardIdList);
            }
        }

        $filter = [
          'use_bound' => 5,
          'company_id' => $companyId
        ];

        if (!empty($hasCardIdList)) {
            $filter['card_id|notIn'] = $hasCardIdList;
        }

        $list = $this->discountCardRepository->getLists($filter, 'card_id,use_bound');
        if (empty($list)) {
            return $cardIds;
        }

        $mergeCardIdList = array_column($list, 'card_id');

        return array_unique(array_merge($cardIds, $mergeCardIdList));
    }

    /**
     * 根据商品id获取优惠券列表
     * @param $filter
     * @param int $page
     * @param int $pageSize
     * @param array $orderBy 排序方式
     * @return mixed
     */
    public function getKaquanListByItemId($filter, $page = 1, $pageSize = 10, array $orderBy = [])
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('kaquan_discount_cards', 'kdc');
        // 增加排序
        foreach ($orderBy as $column => $option) {
            $criteria->addOrderBy(sprintf("kdc.%s", $column), $option);
        }
        $criteria->where($criteria->expr()->eq('kdc.company_id', $criteria->expr()->literal($filter['company_id'])));

        if (isset($filter['item_id']) && $filter['item_id']) {
            //$criteria->leftjoin('kdc', 'kaquan_rel_items', 'kri', 'kdc.card_id = kri.card_id');
            $filterCardIds = $this->getCardIdsByGoods($filter);
            if ($filterCardIds) {
                $criteria->andWhere($criteria->expr()->in('kdc.card_id', $filterCardIds));
            } else {
                return ['total_count' => 0,'pagers' => ['total' => 0], 'list' => []]; //没有符合条件的卡券
            }
        }
        // 根据card_id查询
        if (isset($filter['card_id']) && $filter['card_id']) {
            $criteria->andWhere($criteria->expr()->eq('kdc.card_id', $filter['card_id']));
        }
        //dd($criteria->getSQL());

        // if (isset($filter['item_id']) && $filter['item_id']) {
        //$criteria->andWhere($criteria->expr()->in('kri.item_id', [0, $filter['item_id']]));
        // }

        $orX = [
            $criteria->expr()->andX(
                $criteria->expr()->eq('kdc.grade_ids', $criteria->expr()->literal('')),
                $criteria->expr()->eq('kdc.vip_grade_ids', $criteria->expr()->literal(''))
            )
        ];
        if ($user = app('auth')->user()) {
            // 会员等级限制
            if ($user->get('grade_id')) {
                $orX[] = $criteria->expr()->like('kdc.grade_ids', $criteria->expr()->literal('%,'.$user->get('grade_id').',%'));
            }
            // vip会员等级限制
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($filter['company_id'], $user->get('user_id'));
            if (isset($vipgrade['is_open']) && $vipgrade['is_open']) {
                $orX[] = $criteria->expr()->like('kdc.vip_grade_ids', $criteria->expr()->literal('%,'.$vipgrade['vip_grade_id'].',%'));
            }

            /*            $andX = [ ];
                        // 查询用户标签
                        $memberTagsService = new MemberTagsService();
                        $tagIds = $memberTagsService->getTagIdsByUserId($filter['company_id'], $user->get('user_id'));
                        if ($tagIds) {
                            $criteria = $criteria->leftJoin('kdc', 'kaquan_rel_member_tags', 't', 'kdc.card_id = t.card_id')
                                ->andWhere($criteria->expr()->orX(
                                    $criteria->expr()->in('t.tag_id', $tagIds),
                                    $criteria->expr()->isnull('t.tag_id')
                                ));
                        }
                        $criteria = $criteria->andWhere($criteria->expr()->andX(...$andX));*/
        }
        $criteria = $criteria->andWhere($criteria->expr()->orX(...$orX));

        $criteria->andWhere($criteria->expr()->gte('kdc.quantity', $criteria->expr()->literal(0)));
        if (isset($filter['receive'])) {
            $criteria->andWhere($criteria->expr()->eq('kdc.receive', $criteria->expr()->literal($filter['receive'])));
        }
        /*
        if (isset($filter['distributor_id']) && is_numeric($filter['distributor_id'])) {
            if (intval($filter['distributor_id']) >= 0) {
                $distributorId = '%,'.$filter['distributor_id'].',%';
                $criteria = $criteria->andWhere($criteria->expr()->orX(
                    $criteria->expr()->orX(
                        $criteria->expr()->eq('kdc.distributor_id', $criteria->expr()->literal(','))
                    ),$criteria->expr()->orX(
                        $criteria->expr()->like('kdc.distributor_id', $criteria->expr()->literal($distributorId))
                    )
                ));
            }
        }
        */
        //店铺和平台的优惠券不能通用
        if (isset($filter['distributor_id'])) {
            $distributor_id = $filter['distributor_id'];
            if (intval($distributor_id)) {
                $distributorId = '%,'.$distributor_id.',%';
                $company = (new CompanysActivationEgo())->check($filter['company_id']);
                if ($company['product_model'] == 'platform') {
                    $criteria->andWhere($criteria->expr()->like('kdc.distributor_id', $criteria->expr()->literal($distributorId)));
                } else {
                    $criteria->andWhere(
                        $criteria->expr()->orX(
                            $criteria->expr()->eq('kdc.use_all_shops', $criteria->expr()->literal('1')),
                            $criteria->expr()->like('kdc.distributor_id', $criteria->expr()->literal($distributorId))
                        )
                    );
                }
            } else {
                $criteria->andWhere($criteria->expr()->eq('kdc.distributor_id', $criteria->expr()->literal(',')));
            }
        }

        $criteria = $criteria->andWhere($criteria->expr()->orX(
            $criteria->expr()->andX(
                $criteria->expr()->eq('kdc.card_type', $criteria->expr()->literal('new_gift')),
                $criteria->expr()->eq('kdc.kq_status', DiscountNewGiftCardService::STATUS_NORMAL),
                $criteria->expr()->gte('kdc.send_end_time', time()),
                $criteria->expr()->lte('kdc.send_begin_time', time())
            ),
            $criteria->expr()->andX(
                $criteria->expr()->eq('kdc.card_type', $criteria->expr()->literal('new_gift')),
                $criteria->expr()->lte('kdc.send_begin_time', time()),
                $criteria->expr()->gt('kdc.fixed_term', 0),
                $criteria->expr()->eq('kdc.send_end_time', 0),
                $criteria->expr()->eq('kdc.end_date', 0),
                $criteria->expr()->eq('kdc.kq_status', DiscountNewGiftCardService::STATUS_NORMAL)
            ),
            $criteria->expr()->andX(
                $criteria->expr()->neq('kdc.card_type', $criteria->expr()->literal('new_gift')),
                $criteria->expr()->gte('kdc.end_date', $criteria->expr()->literal(time()))
//                $criteria->expr()->lte('kdc.begin_date', $criteria->expr()->literal(time()))
            ),
            $criteria->expr()->andX(
                $criteria->expr()->neq('kdc.card_type', $criteria->expr()->literal('new_gift')),
                $criteria->expr()->gt('kdc.fixed_term', 0),
                $criteria->expr()->eq('kdc.end_date', 0)
                // $criteria->expr()->eq('kdc.begin_date', 0)
            )
        ));
        if (!isset($filter['card_type']) || !$filter['card_type']) {
            $filter['card_type'] = ['cash', 'discount', 'new_gift', 'money'];
        }
        $cardType = (array)$filter['card_type'];
        array_walk($cardType, function (&$colVal) use ($criteria) {
            $colVal = $criteria->expr()->literal($colVal);
        });
        $criteria->andWhere($criteria->expr()->in('card_type', $cardType));

        // 获取总数量
        $count = $criteria->select('count(*) as count')->execute()->fetch();
        $res['total_count'] = $count['count'];
        $res['pagers']['total'] = $count['count'];
        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }

        if (!isset($filter['item_id']) && $user) {
            // YDSC-2883 领券中心需要根据状态作为第一优先级排序
            $joinKud = $conn->createQueryBuilder();
            $joinKud = $joinKud->from('kaquan_user_discount', 'kud');
            $joinKud->where($joinKud->expr()->eq('kud.user_id', $user->get('user_id')));
            $joinKud->groupBy('card_id')->select('count(card_id) as total, card_id');

            $criteria->leftJoin('kdc', '('.$joinKud->getSQL().')', 'kud', 'kud.card_id = kdc.card_id');
            // 未开始条件
            $endBuilder = $conn->createQueryBuilder();
            $endSql = $endBuilder->expr()->lte('kdc.end_date', time()).' AND '.$endBuilder->expr()->neq('kdc.end_date', 0);
            // 1: 未开始, 2: 已领取, 3: 未领取
            $orderCase = "CASE WHEN $endSql THEN 1 WHEN (kud.total IS NOT NULL AND kud.total >= kdc.get_limit) THEN 2 ELSE 3 END";
            $criteria->addOrderBy('lv', 'DESC');
            $criteria->addOrderBy('kdc.created', 'DESC');
            $res['list'] = $criteria->select("kdc.*, {$orderCase} AS lv")->execute()->fetchAll();
        } else {
            $criteria->orderby('kdc.created', 'DESC');
            // 获取列表数据
            $res['list'] = $criteria->select('kdc.*')->execute()->fetchAll();
        }

        $userDiscountService = new UserDiscountService();
        if ($count['count'] > 0) {
            foreach ($res['list'] as &$detail) {
                $detail['get_num'] = $userDiscountService->getCardGetNum($detail['card_id'], $detail['company_id']);
                $detail['use_num'] = $userDiscountService->getCardUsedNum($detail['card_id'], $detail['company_id']);
                if ($detail['time_limit']) {
                    $detail['time_limit'] = unserialize($detail['time_limit']);
                }
                //处理返回卡券时间
                if ($detail['date_type'] == "DATE_TYPE_FIX_TERM" || $detail['date_type'] == DiscountNewGiftCardService::DATE_TYPE_LONG) {
                    $detail['begin_date'] = strtotime(date('Y-m-d H:i:s', time() + 3600 * 24 * $detail['begin_date']));
                    if (intval($detail['end_date']) <= 0) {
                        $detail['end_date'] = strtotime(date('Y-m-d H:i:s', $detail['begin_date'] + 3600 * 24 * $detail['fixed_term']));
                    }
                }

                // 优惠券的状态
                $detail["date_status"] = DateStatusService::getDateStatus((string)$detail['begin_date'], (string)$detail['end_date']);
            }
        }

        return $res;
    }

    public function getKaquanListByParams($filter, $page = 1, $pageSize = 10)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->from('kaquan_discount_cards', 'kdc');
        $criteria->leftjoin('kdc', 'kaquan_rel_items', 'krc', "kdc.card_id = krc.card_id and krc.item_type='category'");
        $criteria->leftjoin('kdc', 'kaquan_rel_items', 'krt', "kdc.card_id = krt.card_id and krt.item_type='tag'");

        $criteria->where($criteria->expr()->eq('kdc.company_id', $criteria->expr()->literal($filter['company_id'])));

        $criteria = $criteria->andWhere($criteria->expr()->orX(
            $criteria->expr()->orX(
                $criteria->expr()->eq('krc.item_id', $criteria->expr()->literal($filter['category_id']))
            ),
            $criteria->expr()->orX(
                $criteria->expr()->like('kdc.brand_ids', $criteria->expr()->literal('%,'.$filter['brand_id'].',%'))
            ),
            $criteria->expr()->orX(
                $criteria->expr()->in('krt.item_id', $filter['tag_id'])
            )
        ));

        $criteria->andWhere($criteria->expr()->gte('kdc.quantity', $criteria->expr()->literal(0)));
        if (isset($filter['receive'])) {
            $criteria->andWhere($criteria->expr()->eq('kdc.receive', $criteria->expr()->literal($filter['receive'])));
        }

        $criteria = $criteria->andWhere($criteria->expr()->orX(
            $criteria->expr()->andX(
                $criteria->expr()->gte('kdc.end_date', $criteria->expr()->literal(time())),
                $criteria->expr()->lte('kdc.begin_date', $criteria->expr()->literal(time()))
            ),
            $criteria->expr()->andX(
                $criteria->expr()->gt('kdc.fixed_term', 0),
                $criteria->expr()->eq('kdc.end_date', 0),
                $criteria->expr()->eq('kdc.begin_date', 0)
            )
        ));
        if (isset($filter['card_type']) && $filter['card_type']) {
            $cardType = (array)$filter['card_type'];
            array_walk($cardType, function (&$colVal) use ($criteria) {
                $colVal = $criteria->expr()->literal($colVal);
            });
            $criteria->andWhere($criteria->expr()->in('card_type', $cardType));
        }

        // 获取总数量
        $count = $criteria->select('count(*) as count')->execute()->fetch();
        $res['total_count'] = $count['count'];
        $res['pagers']['total'] = $count['count'];
        if ($pageSize > 0) {
            $criteria->setFirstResult(($page - 1) * $pageSize)->setMaxResults($pageSize);
        }
        $criteria->orderby('kdc.created', 'DESC');
        // 获取列表数据
        $res['list'] = $criteria->select('kdc.*')->execute()->fetchAll();

        return $res;
    }

    public function createRelItems($dataInfo, $itemIds, $cardId)
    {
        foreach ($itemIds as $k => $itemId) {
            $data['item_id'] = $itemId;
            $data['company_id'] = $dataInfo['company_id'];
            $data['card_id'] = $cardId;
            $data['item_type'] = isset($dataInfo['item_type']) ? $dataInfo['item_type'] : 'normal';
            //检测是否存在
            if ($this->relItemsRepository->count($data)) {
                continue;
            }
            $this->relItemsRepository->create($data);
        }
    }

    public function updateRelItems($company_id, $card_id, $items)
    {
        $filter['card_id'] = $card_id;
        $filter['company_id'] = $company_id;
        $this->relItemsRepository->deleteBy($filter);
        if ($items) {
            $isShow = false;
            foreach ($items as $k => $itemId) {
                $isShow = (count($items) - 1 == $k) ? true : false;
                $data['item_id'] = $itemId;
                $data['is_show'] = $isShow;
                $data['card_id'] = $card_id;
                $data['company_id'] = $company_id;
                $data['item_type'] = 'normal';
                $this->relItemsRepository->create($data);
            }
        } else {
            $isShow = true;
            $data['item_id'] = -1;
            $data['is_show'] = $isShow;
            $data['company_id'] = $company_id;
            $data['card_id'] = $card_id;
            $data['item_type'] = 'normal';
            $this->relItemsRepository->create($data);
        }
    }

    /**
     * 修改卡券库存
     */
    public function updateStock($type, $cardId, $store, $companyId, $appId)
    {
        $result = $this->discountCardRepository->updateStore($cardId, $companyId, $store, $type);
        if ($result) {
            $wechatService = new WechatCardService($companyId, $appId);
            $wechatService->updateWechatCardStore($cardId, $type, $store);
        }
        return $result;
    }

    /**
     * 优惠券上传至微信（先加入队列）
     */
    public function uploadCard($appId, $companyId, $cardId)
    {
        $gotoJob = (new UploadWechatCard($appId, $companyId, $cardId));
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        return true;
    }

    private function __setParams(&$dataInfo)
    {
        //时间戳为标准0点时间
        if ($dataInfo['date_type'] == "DATE_TYPE_FIX_TERM") {
            $dataInfo['fixed_term'] = $dataInfo['days'];
            $dataInfo['begin_date'] = $dataInfo['begin_time'];
            $dataInfo['end_date'] = isset($dataInfo['end_time']) ? ($dataInfo['end_time']) : "";
        } elseif ($dataInfo['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
            $dataInfo['fixed_term'] = 0;
            $dataInfo['begin_date'] = $dataInfo['begin_time'];
            $dataInfo['end_date'] = $dataInfo['end_time'];
        } elseif ($dataInfo['date_type'] == 'DATE_TYPE_FIX_MONTH') {
            $dataInfo['fixed_term'] = 0;
            $dataInfo['begin_date'] = $dataInfo['begin_time'];
            $dataInfo['end_date'] = 0;
        }
        unset($dataInfo['days'], $dataInfo['begin_time'], $dataInfo['end_time']);

        if (isset($dataInfo['time_limit']) && !$dataInfo['time_limit']) {
            $dataInfo['time_limit'] = [
                ['type' => 'MONDAY'],
                ['type' => 'TUESDAY'],
                ['type' => 'WEDNESDAY'],
                ['type' => 'THURSDAY'],
                ['type' => 'FRIDAY'],
                ['type' => 'SUNDAY'],
                ['type' => 'SATURDAY'],
            ];
        }

        if (isset($dataInfo['rel_item_ids']) && $dataInfo['use_all_items'] == 'false') {
            if (isset($dataInfo['use_bound']) && $dataInfo['use_bound'] == self::FOR_EXCEPT_ASSIGN_ITEMS) {
                $dataInfo['use_bound'] = self::FOR_EXCEPT_ASSIGN_ITEMS;
            } else {
                $dataInfo['use_bound'] = self::FOR_ASSIGN_ITEMS;
            }
            $dataInfo['rel_item_ids'] = json_decode($dataInfo['rel_item_ids'], 1);
            $dataInfo['tag_ids'] = '';
            $dataInfo['brand_ids'] = '';
            if (empty($dataInfo['rel_item_ids'])) {
                unset($dataInfo['rel_item_ids']);
            }
        }

        if ($dataInfo['use_all_items'] == 'true') {
            $dataInfo['use_bound'] = self::FOR_ALL_ITEMS;
        }

        $dataInfo['create_queue'] = false;//是否执行异步创建

        $filterType = $dataInfo['use_all_items'];//商品过滤条件
        //商品分类
        if (isset($dataInfo['item_category']) && $filterType == 'category') {
            $dataInfo['create_queue'] = true;
            $dataInfo['use_bound'] = self::FOR_CATEGORY_ITEMS;
            $dataInfo['item_category'] = json_decode($dataInfo['item_category'], 1);
            $dataInfo['tag_ids'] = '';
            $dataInfo['brand_ids'] = '';
            if (empty($dataInfo['item_category'])) {
                throw new ResourceException(trans('KaquanBundle.please_select_main_category'));
            }
        }
        //商品标签
        if ($filterType == 'tag') {
            if (!isset($dataInfo['tag_ids']) || empty($dataInfo['tag_ids'])) {
                throw new ResourceException(trans('KaquanBundle.please_select_tags'));
            }
            if (is_string($dataInfo['tag_ids'])) {
                $dataInfo['tag_ids'] = json_decode($dataInfo['tag_ids'], true);
            }
            $dataInfo['tag_ids'] = ','.implode(',', $dataInfo['tag_ids']).',';
            $dataInfo['use_bound'] = self::FOR_TAG_ITEMS;
            $dataInfo['brand_ids'] = '';
            $dataInfo['create_queue'] = true;
        }
        //商品品牌
        if ($filterType == 'brand') {
            if (!isset($dataInfo['brand_ids']) || empty($dataInfo['brand_ids'])) {
                throw new ResourceException(trans('KaquanBundle.please_select_brand'));
            }
            if (is_string($dataInfo['brand_ids'])) {
                $dataInfo['brand_ids'] = json_decode($dataInfo['brand_ids'], true);
            }
            $dataInfo['brand_ids'] = ','.implode(',', $dataInfo['brand_ids']).',';
            $dataInfo['use_bound'] = self::FOR_BRAND_ITEMS;
            $dataInfo['tag_ids'] = '';
            $dataInfo['create_queue'] = true;
        }

        // 处理 receive 字段，统一转换为整数 0 或 1
        if (isset($dataInfo['receive'])) {
            $dataInfo['receive'] = $dataInfo['receive'] == true ? 1 : 0;
        }
    }

    //这里不再返回 item_id
    public function getItemIds($dataInfo)
    {
        $params = [
            'company_id' => $dataInfo['company_id'],
            'item_type' => 'normal',
            'special_type' => ['normal', 'drug'],
            'is_gift' => false,
        ];
        if (isset($dataInfo['is_distributor']) && $dataInfo['is_distributor'] == 'true') {
            $params['distributor_id'] = $dataInfo['distributor_id'][0];
        }
        $isQuery = false;
        if ($dataInfo['use_bound'] == 2) {
            $isQuery = true;
            $filter['main_cat_id'] = $dataInfo['item_category'];
        }
        if ($dataInfo['use_bound'] == 3) {
            $isQuery = true;
            $filter['tag_id'] = array_filter(explode(',', $dataInfo['tag_ids']));
        }
        if ($dataInfo['use_bound'] == 4) {
            $isQuery = true;
            $filter['brand_id'] = array_filter(explode(',', $dataInfo['brand_ids']));
        }

        $dataInfo['apply_scope'] = '';

        //指定商品，直接返回
        if (!$isQuery) {
            $dataInfo['rel_item_ids'] = $dataInfo['rel_item_ids'] ?? null;
            return $dataInfo;
        }

        //分类
        if (isset($filter['main_cat_id']) && $filter['main_cat_id']) {
            $params['item_category'] = $filter['main_cat_id'];
            //获取分类名称
            $itemsCategoryRepository = app('registry')->getManager('default')->getRepository(ItemsCategory::class);
            $itemsCategoryInfo = $itemsCategoryRepository->lists(['category_id' => $dataInfo['item_category']], ["created" => "DESC"], -1, 1);
            $dataInfo['apply_scope'] = implode(',', array_column($itemsCategoryInfo['list'], 'category_name'));
        }

        //标签
        if (isset($filter['tag_id']) && $filter['tag_id']) {
            $tagFilter = ['company_id' => $params['company_id'], 'tag_id' => $filter['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $tagFilter['item_id'] = $params['item_id'];
            }

            $itemsTagsService = new ItemsTagsService();
            //$itemIds = $itemsTagsService->getItemIdsByTagids($tagFilter);
            $itemIds = $itemsTagsService->getRelCount($tagFilter);
            if (!$itemIds) {
                throw new ResourceException(trans('KaquanBundle.no_products_under_option_reselect'));
            }

            $itemsTagService = new ItemsTagsService();
            $tagList = $itemsTagService->getListTags($tagFilter);
            $dataInfo['apply_scope'] = implode(',', array_column($tagList['list'], 'tag_name'));

            //$params['item_id'] = $itemIds;
        }

        //品牌
        if ($filter['brand_id'] ?? 0) {
            $params["brand_id"] = $filter['brand_id'];
            $itemsAttributesService = new ItemsAttributesService();
            $brandFilter['attribute_id'] = $filter['brand_id'];
            $brandFilter['company_id'] = $params['company_id'];
            $brandFilter['attribute_type'] = 'brand';

            $brandList = $itemsAttributesService->lists($brandFilter, 1, -1);
            $dataInfo['apply_scope'] = implode(',', array_column($brandList['list'], 'attribute_name'));
        }

        $params['is_default'] = true;
        $itemsService = new ItemsService();

        //获取总条数
        $totalCount = $itemsService->count($params);
        if ($totalCount <= 0) {
            throw new ResourceException(trans('KaquanBundle.no_products_under_option_reselect'));
        }

        //这里设置成空，异步处理
        $itemIds = [];
        /*
        $pageSize = 200;
        $totalPage = ceil($totalCount/$pageSize);//计算多少页
        for ($page = 1; $page <= $totalPage; $page ++) {
            $result = $itemsService->getItemsList($params, $page, $pageSize);
            foreach ($result['list'] as $v) {
                $itemIds[] = $v['item_id'];
            }
        }
        */
        $dataInfo['rel_item_ids'] = $itemIds;
        return $dataInfo;
    }

    public function getDistributorIds($filter)
    {
        if (!$filter) {
            return [];
        }
        $detail = $this->discountCardRepository->getInfo($filter);
        if (!$detail) {
            return [];
        }
        $ids = trim($detail['distributor_id'], ',');
        if (!$ids) {
            return [];
        }
        return array_values(explode(',', $ids));
    }

    /**
     * Dynamically call the discountCardRepository instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->discountCardRepository->$method(...$parameters);
    }

    /**
     * 追加店铺id
     * @param int $companyId
     * @param array $discountCardList
     * @return void
     */
    public function appendDistributorId(int $companyId, array &$discountCardList): void
    {
        if ($companyId < 1 || empty($discountCardList)) {
            return;
        }

        // 遍历优惠券信息
        foreach ($discountCardList as &$item) {
            // 优惠券的渠道类型
            $sourceType = $item["source_type"] ?? null;
            switch ($sourceType) {
                // 店铺优惠券
                case self::SOURCE_TYPE_DISTRIBUTOR:
                    $item["distributor_id"] = $item["source_id"] ?? null;
                    break;
                // 平台优惠券
                case self::SOURCE_TYPE_ADMIN:
                    $item["distributor_id"] = 0;
                    break;
                // 其他渠道
                default:
                    $item["distributor_id"] = null;
            }
        }
    }
}
