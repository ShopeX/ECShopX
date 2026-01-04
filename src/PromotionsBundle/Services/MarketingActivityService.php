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

use PromotionsBundle\Entities\MarketingActivity;
use PromotionsBundle\Entities\MarketingActivityCategory;
use PromotionsBundle\Entities\MarketingActivityItems;
use PromotionsBundle\Entities\MarketingGiftItems;
use GoodsBundle\Services\ItemsService;
use GoodsBundle\Services\ItemsTagsService;

use Dingo\Api\Exception\ResourceException;
// use PromotionsBundle\Traits\CheckPromotionsValid;
use KaquanBundle\Services\VipGradeService;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Services\MemberService;
use KaquanBundle\Services\VipGradeOrderService;
use PromotionsBundle\Jobs\SavePromotionItemTag;
use SalespersonBundle\Jobs\SalespersonItemsShelvesJob;
use CompanysBundle\Ego\CompanysActivationEgo;

class MarketingActivityService
{
    // use CheckPromotionsValid;

    public const MAX_ITEM_TAGS = 500;//最多处理商品的500个标签

    /**
     * 营销类型 - 满折
     */
    public const MARKETING_TYPE_FULL_DISCOUNT = "full_discount";

    /**
     * 营销类型 - 满减
     */
    public const MARKETING_TYPE_FULL_MINUS = "full_minus";

    /**
     * 营销类型 - 满赠
     */
    public const MARKETING_TYPE_FULL_GIFT = "full_gift";

    /**
     * 优惠券来源类型 - 店铺
     */
    public const SOURCE_TYPE_DISTRIBUTOR = "distributor";

    /**
     * 审核状态 - 审核通过
     */
    public const CHECK_STATUS_AGREE = "agree";

    public $entityRepository;
    public $entityRelRepository;
    public $entityRelCategoryRepository;
    public $entityGiftRelRepository;

    public $activityArr = [
        'full_discount' => \PromotionsBundle\Services\PromotionActivity\FullDiscount::class,
        'full_minus' => \PromotionsBundle\Services\PromotionActivity\FullMinus::class,
        'full_gift' => \PromotionsBundle\Services\PromotionActivity\FullGift::class,
        'plus_price_buy' => \PromotionsBundle\Services\PromotionActivity\PlusPriceBuy::class,
        'member_preference' => \PromotionsBundle\Services\PromotionActivity\MemberPreference::class,
    ];

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(MarketingActivity::class);
        $this->entityRelRepository = app('registry')->getManager('default')->getRepository(MarketingActivityItems::class);
        $this->entityRelCategoryRepository = app('registry')->getManager('default')->getRepository(MarketingActivityCategory::class);
        $this->entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
    }

    public function create($params)
    {
        // ShopEx EcShopX Core Module
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->create($params);
            if ($result && $params['use_bound'] == 2 && isset($params['item_category']) && is_array($params['item_category'])) {
                foreach ($params['item_category'] as $k => $categoryId) {
                    $data['category_id'] = $categoryId;
                    $data['company_id'] = $result['company_id'];
                    $data['marketing_id'] = $result['marketing_id'];
                    $data['marketing_type'] = $result['marketing_type'];
                    $this->entityRelCategoryRepository->create($data);
                }
            }
            $rs = $this->createMarketingItemRel($result, $params);
            if (!$rs) {
                throw new ResourceException(trans('PromotionsBundle.related_activity_product_error'));
            }
            $rs = $this->createMarketingGiftItemRel($result, $params);
            if (!$rs) {
                throw new ResourceException(trans('PromotionsBundle.gift_save_error'));
            }
            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['marketing_id'], $params['marketing_type']));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function updateActivity($filter, $params)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $this->entityRelCategoryRepository->deleteBy(['company_id' => $params['company_id'], 'marketing_id' => $filter['marketing_id']]);
            $result = $this->entityRepository->updateOneBy($filter, $params);
            if ($result && $params['use_bound'] == 2 && isset($params['item_category']) && is_array($params['item_category'])) {
                foreach ($params['item_category'] as $k => $categoryId) {
                    $data['category_id'] = $categoryId;
                    $data['company_id'] = $result['company_id'];
                    $data['marketing_id'] = $result['marketing_id'];
                    $data['marketing_type'] = $result['marketing_type'];
                    $this->entityRelCategoryRepository->create($data);
                }
            }
            $rs = $this->createMarketingItemRel($result, $params);
            if (!$rs) {
                throw new ResourceException(trans('PromotionsBundle.related_activity_product_error'));
            }
            $rs = $this->createMarketingGiftItemRel($result, $params);
            if (!$rs) {
                throw new ResourceException(trans('PromotionsBundle.gift_save_error'));
            }
            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($params['company_id'], $result['marketing_id'], $params['marketing_type']));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 保存活动关联的商品
     */
    private function createMarketingItemRel($activity, $params)
    {
        // FIXME: check performance
        $filter = ['company_id' => $activity['company_id'], 'marketing_id' => $activity['marketing_id']];
        $this->entityRelRepository->deleteBy($filter);
        $itemIds = $params['item_ids'] ?? [];

        $itemTypeConf = [
            2 => ['param' => 'item_category', 'type' => 'category', 'name' => '主分类'],
            3 => ['param' => 'tag_ids', 'type' => 'tag', 'name' => '标签'],
            4 => ['param' => 'brand_ids', 'type' => 'brand', 'name' => '品牌'],
        ];

        //商品标签，品牌，主类目
        if ($params['use_bound'] > 1) {
            $use_bound = $params['use_bound'];
            $itemIds = $params[$itemTypeConf[$use_bound]['param']] ?? [];
            $params['item_type'] = $itemTypeConf[$use_bound]['type'];
            $defaultItemName = $itemTypeConf[$use_bound]['name'];
        }

        //指定商品，获取商品信息
        if ($params['use_bound'] == 1 && $itemIds) {
            $itemService = new ItemsService();
            $filter = [
                'company_id' => $params['company_id'],
                'item_id' => $itemIds,
            ];
            $items = $itemService->getSkuItemsList($filter);
            $items = array_column($items['list'], null, 'item_id');
        }

        if ($activity && $params['use_bound'] != 0 && $itemIds) {
            foreach ($itemIds as $itemId) {
                $isShow = true;
                if ($params['use_bound'] == 1) {
//                    if(isset($isShowIds[$items[$itemId]['default_item_id']])){
//                        $isShow = false;
//                    }
                }
                $data['is_show'] = $isShow;
                $data['marketing_id'] = $activity['marketing_id'];
                $data['company_id'] = $activity['company_id'];
                $data['start_time'] = $activity['start_time'];
                $data['end_time'] = $activity['end_time'];
                $data['promotion_tag'] = $params['promotion_tag'];
                $data['marketing_type'] = $params['marketing_type'];
                $data['item_type'] = $params['item_type'];
                $data['item_id'] = $itemId;
                $data['item_rel_id'] = 0;
                $data['item_name'] = $items[$itemId]['itemName'] ?? $defaultItemName;
                $data['price'] = $items[$itemId]['price'] ?? '';
                $data['pics'] = $items[$itemId]['pics'] ?? '';
                $data['goods_id'] = $items[$itemId]['goods_id'] ?? 0;
                $data['item_spec_desc'] = $items[$itemId]['item_spec_desc'] ?? '';
                $result = $this->entityRelRepository->create($data);
                if (!$result) {
                    return false;
                }
            }
        }
        if ($activity) {
            $itemData = [
                'item_type' => $params['item_type'],
                'item_ids' => $itemIds
            ];
            $gotoJob = (new SavePromotionItemTag($activity['company_id'], $activity['marketing_id'], $params['marketing_type'], $activity['start_time'], $activity['end_time'], $itemData));//->onQueue('slow')
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        return true;
    }

    /**
     * 保存满赠活动关联的赠品
     */
    private function createMarketingGiftItemRel($activity, $params)
    {
        $filter = ['company_id' => $activity['company_id'], 'marketing_id' => $activity['marketing_id']];
        $this->entityGiftRelRepository->deleteBy($filter);
        if (in_array($params['marketing_type'], ['full_gift', 'plus_price_buy']) && ($params['gifts'] ?? [])) {
            $giftsData = is_array($params['gifts']) ? $params['gifts'] : json_decode($params['gifts'], true);
            $itemIds = array_column($giftsData, 'item_id');

            // todo 先保持和编辑保存一样的互斥逻辑，后面要把整个营销的互斥逻辑重新整理一下
            if ($params['marketing_type'] == 'plus_price_buy' && array_intersect($params['item_ids'], $itemIds)) {
                throw new ResourceException(trans('PromotionsBundle.plus_price_main_product_cannot_be_plus_product'));
            }

            $itemService = new ItemsService();
            $filter = [
                'company_id' => $params['company_id'],
                'item_id' => $itemIds,
            ];
            $items = $itemService->getSkuItemsList($filter);
            $items = array_column($items['list'], null, 'item_id');

            foreach ($giftsData as $data) {
                if (!($items[$data['item_id']] ?? [])) {
                    throw new ResourceException(trans('PromotionsBundle.product_not_exist'));
                }
                $data['marketing_id'] = $activity['marketing_id'];
                $data['company_id'] = $activity['company_id'];
                $data['item_spec_desc'] = $items[$data['item_id']]['item_spec_desc'] ?? '';
                $data['condition_type'] = $activity['condition_type'];

                $result = $this->entityGiftRelRepository->create($data);
                if (!$result) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 删除未开始的活动
     */
    public function deleteMarketingActivity($filter)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->getInfo($filter);
                    if ($result['status'] != 'waiting') {
            throw new ResourceException(trans('PromotionsBundle.cannot_delete_activity'));
        }
            $this->entityRepository->deleteBy($filter);
            $this->entityRelRepository->deleteBy($filter);
            $this->entityGiftRelRepository->deleteBy($filter);

            $promotionItemTagService = new PromotionItemTagService();
            $promotionItemTagService->deleteBy(['promotion_id' => $filter['marketing_id'], 'company_id' => $filter['company_id'], 'tag_type' => $result['marketing_type']]);

            $conn->commit();
            $job = (new SalespersonItemsShelvesJob($filter['company_id'], $filter['marketing_id'], $result['marketing_type']));
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return $result;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 获取会员普通等级 和 付费会员等级数组合集
     */
    public function getMemberGrade($companyId)
    {
        $vipGradeService = new VipGradeService();
        $vipGrade = $vipGradeService->lists(['company_id' => $companyId, 'is_disabled' => false]);
        $vipGrade = array_column($vipGrade, null, 'lv_type');

        $kaquanService = new MemberCardService();
        $grade = $kaquanService->getGradeListByCompanyId($companyId, false);
        $grade = array_column($grade, null, 'grade_id');
        if ($vipGrade && $grade) {
            // $result = array_merge($vipGrade, $grade);
            $result = $vipGrade + $grade;
        } elseif ($vipGrade) {
            $result = $vipGrade;
        } elseif ($grade) {
            $result = $grade;
        }
        return $result ?? [];
    }

    public function getMarketingLists($filter, $page = 1, $pageSize = -1, $orderBy = [], $getItemList = true)
    {
        $lists = $this->entityRepository->lists($filter, $page, $pageSize, $orderBy);
        $marketingIds = array_column($lists['list'], 'marketing_id');
        //获取参加活动的商品
        $relFilter = isset($filter['company_id']) ? ['marketing_id' => $marketingIds, 'company_id' => $filter['company_id']] : ['marketing_id' => $marketingIds];
        //获取活动绑定的赠品
        $relGiftsList = $this->entityGiftRelRepository->lists($relFilter);
        $itemIds = array_column($relGiftsList['list'], 'item_id');

        //获取活动包含的商品的所有明细
        $itemService = new ItemsService();
        $filter = isset($filter['company_id']) ? ['company_id' => $filter['company_id'], 'item_id' => $itemIds] : ['item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($filter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        $relGiftGoods = [];
        $relGiftItems = [];
        foreach ($relGiftsList['list'] as $value) {
            $price = $value['price'] ? bcdiv($value['price'], 100, 2) : 0;
            if (!($itemdata[$value['item_id']] ?? [])) {
                $value['status'] = 'invalid';
            } else {
                $value['status'] = 'valid';
                $itemdata[$value['item_id']]['gift_num'] = $value['gift_num'] ?? 0;
                $itemdata[$value['item_id']]['without_return'] = $value['without_return'] ?? false;
                $relGiftItems[$value['marketing_id']][$price][] = $itemdata[$value['item_id']];
            }
            $relGiftGoods[$value['marketing_id']][$price][] = $value;
        }

        foreach ($lists['list'] as &$list) {
            switch ($list['marketing_type']) {
            case "full_gift":
                if (isset($relGiftGoods[$list['marketing_id']])) {
                    $list['gifts'] = $relGiftGoods[$list['marketing_id']][0];
                }
                if (isset($relGiftItems[$list['marketing_id']])) {
                    $list['giftsItemLists'] = $relGiftItems[$list['marketing_id']][0];
                }
                break;
            case "plus_price_buy":
                if (isset($relGiftGoods[$list['marketing_id']]) && isset($relGiftItems[$list['marketing_id']])) {
                    foreach ($relGiftGoods[$list['marketing_id']] as $price => $giftItem) {
                        $list['gifts'][] = ['price' => $price, 'gift_item' => $giftItem];
                        $list['giftsItemLists'][] = ['price' => $price, 'gift_item' => $relGiftItems[$list['marketing_id']][$price]];
                    }
                }
                break;
            default:
                $list['gifts'] = [];
                $list['giftsItemLists'] = [];
                break;
            }
        }

        if ($lists['list'] && $getItemList) {
            $relLists = $this->entityRelRepository->lists($relFilter);
            $itemIds = array_column($relLists['list'], 'item_id');

            //获取活动包含的商品的所有明细
            $filter = isset($filter['company_id']) ? ['company_id' => $filter['company_id'], 'item_id' => $itemIds] : ['item_id' => $itemIds];
            $itemsList = $itemService->getSkuItemsList($filter);
            $itemdata = array_column($itemsList['list'], null, 'item_id');

            $relGoods = [];
            $relItems = [];
            foreach ($relLists['list'] as $value) {
                if (!($itemdata[$value['item_id']] ?? [])) {
                    $value['status'] = 'invalid';
                } else {
                    $value['status'] = 'valid';
                    $relItems[$value['marketing_id']][] = $itemdata[$value['item_id']];
                }
                $relGoods[$value['marketing_id']][] = $value;
            }

            foreach ($lists['list'] as &$list) {
                $list['items'] = $relGoods[$list['marketing_id']] ?? [];
                $list['itemTreeLists'] = $relItems[$list['marketing_id']] ?? [];
                if ($list['itemTreeLists']) {
                    $list['itemTreeLists'] = $itemService->formatItemsList($list['itemTreeLists']);
                }
            }
        }
        return $lists;
    }

    /**
     * 获取指定当前或者指定时间范围内有效的活动（商品详情页应用了）
     */
    public function getValidMarketingActivity($companyId, $itemId = null, $userId = null, $marketingId = null, $shopId = -1, $marketingType = null, $goodsId = null)
    {
        $distributorId = $shopId;
        //获取当前有效的活动列表
        $activityList = $this->getValidActivitys($companyId, $marketingId);
        //获取有效活动的所有商品列表
        $marketingIds = array_column($activityList, 'marketing_id');
        $relItemArr = $this->getValidActivityRelItems($companyId, $itemId, $marketingIds, '', '', $marketingType, $goodsId);
        //获取活动赠品
        $relGiftArr = $this->getGiftItem($companyId, $marketingIds);
        //获取指定会员的等级信息
        $userGrade = $this->getUserGrade($userId, $companyId);
        //系统所有的会员等级信息
        $memberGrade = $this->getMemberGrade($companyId);

        foreach ($activityList as $value) {
            if (in_array($value['marketing_type'], ['member_preference'])) {
                continue;
            }

            //检测指定商品是否包含在该活动中，未指定商品的活动不检测
            if ($value['use_bound'] > 0 && !isset($relItemArr[$value['marketing_id']])) {
                continue;
            }

//            if ($goodsId && $value['use_bound']>0){
//                $goodsIdArr = ($relItemArr[$value['marketing_id']] ?? []) ? array_column($relItemArr[$value['marketing_id']], 'goods_id') : [];
//                if (!in_array($goodsId, $goodsIdArr)){
//                    continue;
//                }
//            }
//            if ($itemId && !is_array($itemId) && $value['use_bound'] == 1 && !($relItemArr[$value['marketing_id']][$itemId] ?? [])) {
//                continue;
//            }
            //检测指定的店铺是否包含在活动中，未指定店铺的活动不检测
            $value['shop_ids'] = array_filter(explode(',', $value['shop_ids']));
            //$value['use_shop']，这个值前端传的不准确
            if (($shopId > 0) && $value['shop_ids'] && !in_array($shopId, $value['shop_ids'])) {
                continue;
            }

            $company = (new CompanysActivationEgo())->check($companyId);
            //店铺和平台只能使用各自的活动
            if ($company['product_model'] == 'platform' && $distributorId >= 0 && $distributorId != $value['source_id']) {
                continue;
            }

            //检测指定的会员是否包含在活动指定的会员登记中
            $value['valid_grade'] = json_decode($value['valid_grade'], true);
            if ($value['valid_grade'] && $userGrade && !in_array($userGrade, $value['valid_grade'])) {
                continue;
            }
            if ($value['valid_grade']) {
                foreach ($value['valid_grade'] as $k => $key) {
                    if (isset($memberGrade[$key])) {
                        $value['member_grade'][$k] = $memberGrade[$key]['grade_name'];
                    }
                }
            }

            if ($userId) {
                //验证参与次数
                $usedCount = $this->getMarketingJoinNumByUser($companyId, $userId, $value['marketing_id']);
                $value['usedCount'] = $usedCount;
            }

            $activityOwnService = new $this->activityArr[$value['marketing_type']]();

            if ($value['marketing_type'] == 'full_gift') {
                $value['gifts'] = $relGiftArr[$value['marketing_id']][0] ?? [];
            }

            if ($value['marketing_type'] === 'plus_price_buy') {
                $value['gifts'] = [];
                foreach (($relGiftArr[$value['marketing_id']] ?? []) as  $v) {
                    $value['plusitems'] = array_merge($value['gifts'], $v);
                }
            }

            $value['items'] = $relItemArr[$value['marketing_id']] ?? [];
            $value['condition_value'] = json_decode($value['condition_value'], true);
            $value['condition_rules'] = $activityOwnService->getFullProRules($value['condition_type'], $value['condition_value']);
            $value['start_date'] = date('Y-m-d H:i:s', $value['start_time']);
            $value['end_date'] = date('Y-m-d H:i:s', $value['end_time']);
            $nowTime = time();
            if ($nowTime >= $value['end_time']) {
                $value['status'] = 'end';    //已结束
            } elseif ($nowTime >= $value['start_time'] && $nowTime < $value['end_time']) {
                $value['status'] = 'ongoing';         //进行中
                $value['last_seconds'] = ($value['end_time'] - $nowTime) > 0 ? ($value['end_time'] - $nowTime) : 0;
            } elseif ($nowTime < $value['start_time']) {
                $value['status'] = 'waiting';   //未开始
            }
            $resultList[] = $value;
        }
        return $resultList ?? null;
    }

    /**
     * 获取指定当前或者指定时间范围内有效的会员优先购活动（商品详情页应用了）
     * @param $companyId
     * @param null $itemId
     * @param null $userId
     * @param null $marketingId
     * @param null $goodsId
     * @return null
     */
    public function getValidMemberpreferenceActivity($companyId, $itemId = null, $userId = null, $marketingId = null, $goodsId = null)
    {
        $marketingType = ['member_preference'];
        //获取当前有效的活动列表
        $activityList = $this->getValidActivitys($companyId, $marketingId, '', '', $marketingType);
        //获取有效活动的所有商品列表
        $marketingIds = array_column($activityList, 'marketing_id');
        $relItemArr = $this->getValidActivityRelItems($companyId, $itemId, $marketingIds, '', '', $marketingType, $goodsId);
        //获取指定会员的等级信息
        $userGrade = $this->getUserGrade($userId, $companyId);
        //系统所有的会员等级信息
        $memberGrade = $this->getMemberGrade($companyId);

        foreach ($activityList as $value) {
            $_value = [
                'marketing_desc' => $value['marketing_desc'],
                'marketing_name' => $value['marketing_name'],
            ];
            //检测指定商品是否包含在该活动中，未指定商品的活动不检测
            if ($goodsId && $value['use_bound'] > 0) {
                $goodsIdArr = ($relItemArr[$value['marketing_id']] ?? []) ? array_column($relItemArr[$value['marketing_id']], 'goods_id') : [];
                if (!in_array($goodsId, $goodsIdArr)) {
                    continue;
                }
            }
            if ($itemId && !is_array($itemId) && $value['use_bound'] == 1 && !($relItemArr[$value['marketing_id']][$itemId] ?? [])) {
                continue;
            }
            //检测指定的会员是否包含在活动指定的会员登记中
            $value['valid_grade'] = json_decode($value['valid_grade'], true);
            if ($value['valid_grade'] && $userGrade && !in_array($userGrade, $value['valid_grade'])) {
                $_value['user_grade_valid'] = false;
            } else {
                $_value['user_grade_valid'] = true;
            }
            if ($value['valid_grade']) {
                foreach ($value['valid_grade'] as $k => $key) {
                    if (isset($memberGrade[$key])) {
                        $_value['member_grade'][$k] = $memberGrade[$key]['grade_name'];
                    }
                }
            }
            $nowTime = time();
            if ($nowTime >= $value['end_time']) {
                $_value['status'] = 'end';    //已结束
            } elseif ($nowTime >= $value['start_time'] && $nowTime < $value['end_time']) {
                $_value['status'] = 'ongoing';         //进行中
                // $value['last_seconds'] = ($value['end_time']-$nowTime) > 0 ? ($value['end_time']-$nowTime) : 0;
            } elseif ($nowTime < $value['start_time']) {
                $_value['status'] = 'waiting';   //未开始
            }
            $resultList[] = $_value;
        }
        return $resultList ?? null;
    }

    public function getGiftItem($companyId, $marketingIds)
    {
        $result = [];
        $filter = ['company_id' => $companyId, 'marketing_id' => $marketingIds];
        $giftLists = $this->entityGiftRelRepository->lists($filter)['list'];
        if (empty($giftLists)) {
            return $result;
        }
        $itemIds = array_column($giftLists, 'item_id');
        $itemService = new ItemsService();
        $filter = ['company_id' => $companyId, 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($filter);
        $itemdata = array_column($itemsList['list'], null, 'itemId');

        foreach ($giftLists as $gift) {
            if (isset($itemdata[$gift['item_id']])) {
                $item = $itemdata[$gift['item_id']];
                $item['gift'] = $gift;
                $result[$gift['marketing_id']][$gift['price']][] = $item;
            }
        }
        return $result;
    }

    /**
     *  获取当前有效的活动列表
     */
    public function getValidActivitys($companyId, $marketingId = null, $startTime = null, $endTime = null, $marketingType = null)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('*')
          ->from('promotions_marketing_activity');
        $criteria->andWhere($criteria->expr()->eq('company_id', $criteria->expr()->literal($companyId)));
        if ($marketingType) {
            array_walk($marketingType, function (&$value) use ($criteria) {
                $value = $criteria->expr()->literal($value);
            });
            $criteria->andWhere($criteria->expr()->in('marketing_type', $marketingType));
        }
        if ($marketingId) {
            $marketingIds = (array)$marketingId;
            $criteria->andWhere($criteria->expr()->in('marketing_id', $marketingIds));
        } else {
            $startTime = $startTime ?: time();
            $endTime = $endTime ?: time();
            $criteria = $criteria->andWhere($criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $startTime),
                    $criteria->expr()->gte('end_time', $startTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $endTime),
                    $criteria->expr()->gt('end_time', $endTime)
                )
            ));
        }
        $criteria->orderBy("start_time", "DESC");
        $activityList = $criteria->execute()->fetchAll();
        return $activityList;
    }

    /**
     *  获取当前有效的活动列表
     */
    public function getValidActivityRelItems($companyId, $itemId = null, $marketingIds = null, $startTime = null, $endTime = null, $marketingType = null, $goodsId = null)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('*')
          ->from('promotions_marketing_activity_items');
        $criteria->andWhere($criteria->expr()->eq('company_id', $criteria->expr()->literal($companyId)));
        $rsActivity = [];
        if ($itemId) {
            $itemIds = (array)$itemId;
            //$criteria->andWhere($criteria->expr()->in('item_id', $itemIds));
            $rsActivity = $this->getMarketingIdByItems($itemIds, $companyId, 'item_id');
            if (!$rsActivity) {
                return [];//商品不符合满减
            } else {
                $tmpMarketingIds = array_keys($rsActivity);
            }
            $criteria->andWhere($criteria->expr()->in('marketing_id', $tmpMarketingIds));
        }
        if ($goodsId) {
            $goodsIds = (array)$goodsId;
            //$goodsIds[] = 0;//查找适用全部商品的规则
            //$criteria->andWhere($criteria->expr()->in('goods_id', $goodsIds));
            $rsActivity = $this->getMarketingIdByItems($goodsIds, $companyId, 'goods_id');
            if (!$rsActivity) {
                return [];//商品不符合满减
            } else {
                $tmpMarketingIds = array_keys($rsActivity);
            }
            $criteria->andWhere($criteria->expr()->in('marketing_id', $tmpMarketingIds));
        }
        if ($marketingType) {
            array_walk($marketingType, function (&$value) use ($criteria) {
                $value = $criteria->expr()->literal($value);
            });
            $criteria->andWhere($criteria->expr()->in('marketing_type', $marketingType));
        }

        if ($marketingIds) {
            $marketingIds = (array)$marketingIds;
            $criteria->andWhere($criteria->expr()->in('marketing_id', $marketingIds));
        } else {
            $startTime = $startTime ?: time();
            $endTime = $endTime ?: time();
            $criteria = $criteria->andWhere($criteria->expr()->orX(
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $startTime),
                    $criteria->expr()->gte('end_time', $startTime)
                ),
                $criteria->expr()->andX(
                    $criteria->expr()->lte('start_time', $endTime),
                    $criteria->expr()->gt('end_time', $endTime)
                )
            ));
        }
        $criteria->addOrderBy("marketing_id", "ASC");
        $criteria->addOrderBy("item_id", "ASC");
        $criteria->addOrderBy("start_time", "DESC");
        //dd($criteria->getSQL());
        $activityItemList = $criteria->execute()->fetchAll();
        $relItemArr = [];
        if ($activityItemList) {
            foreach ($activityItemList as $k => $val) {
                if (!isset($rsActivity[$val['marketing_id']])) {
                    $relItemArr[$val['marketing_id']][$val['item_id']] = $val;
                    continue;
                }
                foreach ($rsActivity[$val['marketing_id']]['rel_item_ids'] as $item_id) {
                    $relItemArr[$val['marketing_id']][$item_id] = $val;
                }
            }
        }
        return $relItemArr;
    }

    //根据商品分类查询活动ID
    public function getMarketingIdByItemCategory($itemCategorys = [], $params = [])
    {
        $marketingIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('a.item_id', $itemCategorys))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.brand_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定标签
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items', 'b', 'a.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('b.item_category', "'".implode("','", $itemCategorys)."'"))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        return $marketingIds;
    }

    public function getMarketingIdByItemTags($tagIds = [], $params = [])
    {
        $marketingIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定tag
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items_rel_tags', 'd', 'c.item_id = d.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('d.tag_id', $tagIds))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.brand_id')
            ->leftJoin('b', 'items_rel_tags', 'c', 'b.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_category')
            ->leftJoin('b', 'items_rel_tags', 'c', 'b.item_id = c.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('c.tag_id', $tagIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        return $marketingIds;
    }

    public function getMarketingIdByItemBrand($brandIds = [], $params = [])
    {
        $marketingIds = [];
        $companyId = $params['company_id'];
        $conn = app('registry')->getConnection('default');

        $params['start_time'] = max($params['start_time'], time());//不判断过期活动

        //指定品牌
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('brand')))
            ->andWhere($criteria->expr()->in('a.item_id', $brandIds));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定商品
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('normal')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定标签
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items_rel_tags', 'c', 'a.item_id = c.tag_id')
            ->leftJoin('c', 'items', 'b', 'c.item_id = b.item_id')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('tag')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        //指定类目
        $criteria = $conn->createQueryBuilder();
        $criteria->select('DISTINCT(a.marketing_id)')
            ->from('promotions_marketing_activity_items', 'a')
            ->leftJoin('a', 'items', 'b', 'a.item_id = b.item_category')
            ->where($criteria->expr()->eq('a.item_type', $criteria->expr()->literal('category')))
            ->andWhere($criteria->expr()->in('b.brand_id', $brandIds))
            ->andWhere($criteria->expr()->lte('a.start_time', $params['end_time']))
            ->andWhere($criteria->expr()->gte('a.end_time', $params['start_time']));
        //dd($criteria->getSQL());
        $list = $criteria->execute()->fetchAll();
        $marketingIds = array_merge($marketingIds, array_column($list, 'marketing_id'));

        return $marketingIds;
    }

    //查询符合商品条件的满减，满折
    public function getMarketingIdByItems($itemIds = [], $companyId = 0, $filterType = 'item_id')
    {
        $itemFilter = [];
        $filters = [];
        $itemInfo = [];//商品ID和商品属性(品牌，标签，分类)的关系

        //商品id转换成主商品ID
        $itemsService = new ItemsService();
        $items = $itemsService->getItems($itemIds, $companyId, null, $filterType);
        if (!$items) {
            return false;
        }

        if ($filterType == 'goods_id') {
            $itemIds = array_column($items, 'item_id');
        }


        $itemFilter['default_item_id'] = [];
        $itemFilter['item_main_cat_id'] = [];
        $itemFilter['brand_id'] = [];
        //用来统计每个活动对应的商品
        foreach ($items as $v) {
            if (!in_array($v['default_item_id'], $itemFilter['default_item_id'])) {
                $itemFilter['default_item_id'][] = $v['default_item_id'];
            }
            if (($v['item_main_cat_id'] ?? 0) && !in_array($v['item_main_cat_id'], $itemFilter['item_main_cat_id'])) {
                $itemFilter['item_main_cat_id'][] = $v['item_main_cat_id'];
            }
            if (($v['brand_id'] ?? 0) && !in_array($v['brand_id'], $itemFilter['brand_id'])) {
                $itemFilter['brand_id'][] = $v['brand_id'];
            }

            $itemInfo['normal'][$v['item_id']][] = $v['item_id'];
            $itemInfo['default_item'][$v['default_item_id']][] = $v['item_id'];
            $itemInfo['category'][$v['item_main_cat_id']][] = $v['item_id'];
            $itemInfo['brand'][$v['brand_id']][] = $v['item_id'];
        }

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
            //用来统计每个活动标签对应的商品
            foreach ($tagList['list'] as $v) {
                if (!isset($itemInfo['default_item'][$v['item_id']])) {
                    continue;
                }
                foreach ($itemInfo['default_item'][$v['item_id']] as $item_id) {
                    $itemInfo['tag'][$v['tag_id']][] = $item_id;//主ID转换成Sku id
                }
            }
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

        $marketingArr = $this->getMarketingIds($filters, $companyId);
        if (!$marketingArr) {
            return false;
        }//没找到，默认不可用

        //将活动关联ID替换成商品ID
        $res = [];
        foreach ($marketingArr as $k => $v) {
            if (!isset($itemInfo[$v['item_type']][$v['item_id']])) {
                continue;
            }
            if (!isset($res[$v['marketing_id']])) {
                $res[$v['marketing_id']] = $v;
            }
            foreach ($itemInfo[$v['item_type']][$v['item_id']] as $item_id) {
                $res[$v['marketing_id']]['rel_item_ids'][] = $item_id;
            }
        }

        return $res;
    }

    /**
     * 获取所有符合商品条件的满减/满折活动
     *
     * @param $companyId
     * @return mixed
     */
    public function getMarketingIdsByCompanyId($companyId)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('marketing_id,item_id,item_type')->from('promotions_marketing_activity_items');
        $criteria = $criteria->orWhere(
            $criteria->expr()->andX(
                $criteria->expr()->eq('company_id', $companyId),
                $criteria->expr()->gte('end_time', time())
            )
        );
        return $criteria->execute()->fetchAll();
    }

    /**
     * 获取所有符合商品条件的满减/满折活动
     *
     * @param array $filters
     * @param int $companyId
     * @return array
     */
    public function getMarketingIds($filters = [], $companyId = 0)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('marketing_id,item_id,item_type')->from('promotions_marketing_activity_items');
        foreach ($filters as $filter) {
            $criteria = $criteria->orWhere(
                $criteria->expr()->andX(
                    $criteria->expr()->in('item_id', $filter['item_id']),
                    $criteria->expr()->eq('item_type', $criteria->expr()->literal($filter['item_type'])),
                    $criteria->expr()->eq('company_id', $companyId),
                    //$criteria->expr()->lte('start_time', time()),
                    $criteria->expr()->gte('end_time', time())
                )
            );
        }
        $relItemArr = $criteria->execute()->fetchAll();
        return $relItemArr;
        //return $relItemArr ? array_column($relItemArr, null, 'marketing_id') : [];
    }

    /**
     *  获取指定会员当前的会员等级值（普通会员等级id，付费会员类型)
     */
    public function getUserGrade($userId, $companyId)
    {
        $userGrade = '';
        if ($userId) {
            $vipGradeService = new VipGradeOrderService();
            $vipgrade = $vipGradeService->userVipGradeGet($companyId, $userId);
            if (($vipgrade['valid'] ?? 0) && ($vipgrade['is_vip'] ?? 0)) {
                $userGrade = $vipgrade['vip_type'];
            // $userGrade['id'] = $vipgrade['vip_type'];
                // $userGrade['name'] = $vipgrade['grade_name'];
            } else {
                $memberService = new MemberService();
                $filter = [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                ];
                $memberInfo = $memberService->getMemberInfo($filter);
                $memberCardService = new MemberCardService();
                if ($memberInfo['grade_id'] ?? 0) {
                    $userGrade = $memberInfo['grade_id'];
                    // $gradeInfo = $memberCardService->getGradeByGradeId($memberInfo['grade_id']);
                    // $userGrade['id'] = $memberInfo['grade_id'];
                    // $userGrade['name'] = $gradeInfo['grade_name'];
                }
            }
        }
        return $userGrade;
    }

    /**
     * 获取指定活动的商品列表
     */
    public function getActivityItemList($filter, $page = 1, $pageSize = -1, $orderBy = [], $groupBy = [])
    {
        $itemService = new ItemsService();
        $infoFilter = ['company_id' => $filter['company_id'], 'marketing_id' => $filter['marketing_id']];
        $activityInfo = $this->entityRepository->getinfo($infoFilter);
        $shopIds = array_filter($activityInfo['shop_ids'] ?? []);
        if (!$shopIds) {
            $shopIds = 0;
        }
        //活动商品全选
        if ($activityInfo['use_bound'] == 0) {
            $itemType = 'all';
            $itemFilter = [
                'company_id' => $filter['company_id'],
                'distributor_id' => $shopIds,
            ];
            $relLists = $itemService->getItemListData($itemFilter, $page, $pageSize, $orderBy);
        } else {
            //商品部分选择
            $relItemInfo = $this->entityRelRepository->getinfo($filter);
            $itemType = $relItemInfo['item_type'];
            if (in_array($itemType, ['tag', 'brand', 'category'])) {
                //获取全部的关联信息
                $relLists = $this->entityRelRepository->lists($filter, $page, -1, $orderBy, $groupBy);
            } else {
                //获取关联商品
                $relLists = $this->entityRelRepository->lists($filter, $page, $pageSize, $orderBy, $groupBy);
            }
        }
        $itemIds = array_column($relLists['list'], 'item_id');

        //商品查询的基础条件
        $itemFilter = [
            'company_id' => $filter['company_id'],
            'distributor_id' => $shopIds,
        ];

        //主类目
        if ($itemType == 'category') {
            $itemFilter['item_category'] = $itemIds;
            $itemIds = $itemService->getItemIds($itemFilter, $page, $pageSize);
            $relLists['total_count'] = $itemService->getItemCount($itemFilter);
        }

        //标签
        if ($itemType == 'tag') {
            $itemsTagsService = new ItemsTagsService();
            $itemFilter['tag_id'] = $itemIds;
            unset($itemFilter['distributor_id']);
            $itemIds = $itemsTagsService->getItemIdsByTagids($itemFilter, $page, $pageSize);
            $relLists['total_count'] = $itemsTagsService->getRelCount($itemFilter);
        }

        //品牌
        if ($itemType == 'brand') {
            $itemFilter['brand_id'] = $itemIds;
            $itemIds = $itemService->getItemIds($itemFilter, $page, $pageSize);
            $relLists['total_count'] = $itemService->getItemCount($itemFilter);
        }

        $itemFilter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($itemFilter);
        $relLists['list'] = [];
        foreach ($itemsList['list'] as $item) {
            $relLists['list'][] = [
                'marketing_id' => $activityInfo['marketing_id'],
                'item_id' => $item['item_id'],
                'goods_id' => $item['goods_id'],
                'is_show' => true,
                'store' => $item['store'] ?? 0,
                'item_spec_desc' => $item['item_spec_desc'] ?? '',
                'marketing_type' => $activityInfo['marketing_type'],
                'item_type' => $item['item_type'],
                'item_name' => $item['item_name'],
                'price' => $item['price'],
                'item_brief' => $item['item_name'] ?? '',
                'pics' => $item['pics'],
                'promotion_tag' => $activityInfo['promotion_tag'],
                'start_time' => $activityInfo['start_time'],
                'end_time' => $activityInfo['end_time'],
                'status' => true,
                'company_id' => $activityInfo['company_id'],
            ];
        }

//        $itemData = array_column($itemsList['list'], null, 'item_id');
//        foreach ($relLists['list'] as &$value) {
//            if ($itemData[$value['item_id']] ?? []) {
//                $value = array_merge($value, $itemData[$value['item_id']]);
//            }
//        }

        $relLists['activity'] = $activityInfo;
        return $relLists;
    }

    //手动结束活动
    public function endActivity($companyId, $activityId)
    {
        $filter = [
            'company_id' => $companyId,
            'marketing_id' => $activityId
        ];
        $params['end_time'] = time();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $result = $this->entityRepository->updateOneBy($filter, $params);
            if ($result) {
                $this->entityRelRepository->updateBySimpleFilter($filter, $params);
            }
            if ($result) {
                $promotionItemTagService = new PromotionItemTagService();
                $promotionItemTagService->deleteBy(['promotion_id' => $activityId, 'company_id' => $companyId, 'tag_type' => $result['marketing_type']]);
                $job = (new SalespersonItemsShelvesJob($filter['company_id'], $filter['marketing_id'], $result['marketing_type']));
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
                $conn->commit();
                return true;
            } else {
                $conn->rollback();
                return false;
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
        * @brief 满折满减规则应用
        *
        * @param $companyId 企业id
        * @param $marketingId 活动id
        * @param $cartParams 购物车总数量 和 购物车总金额
        *
        * @return
     */
    public function applyActivityRules($companyId, $marketingId, $userId, $cartParams)
    {
        if (!($cartParams['total_price'] ?? 0)) {
            return [];
        }
        //获取活动详情
        $filter['company_id'] = $companyId;
        $filter['marketing_id'] = $marketingId;
        $detail = $this->entityRepository->getInfo($filter);
        //验证活动有效性
        if (!$detail || $detail['status'] != 'ongoing') {
            return [];
        }
        //验证会员权限
        $gradId = $this->getUserGrade($userId, $companyId);
        if ($detail['valid_grade'] && $gradId && !in_array($gradId, $detail['valid_grade'])) {
            return [];
        }
        //验证参与次数
        $usedCount = $this->getMarketingJoinNumByUser($companyId, $userId, $marketingId);
        if ($detail['join_limit'] > 0 && $usedCount > 0 && $usedCount >= $detail['join_limit']) {
            return [];
        }
        //根据规则获取优惠金额
        $activityOwnService = new $this->activityArr[$detail['marketing_type']]();
        $discountFee = [];
        switch ($detail['condition_type']) {
        case "quantity":
            if (!($cartParams['total_num'] ?? 0)) {
                return [];
            }
            $discountFee = $activityOwnService->applyActivityQuantity($detail, intval($cartParams['total_num']), intval($cartParams['total_price']));
            break;
        case "totalfee":
            $discountFee = $activityOwnService->applyActivityTotalfee($detail, intval($cartParams['total_price']));
            break;
        }
        return $discountFee;
    }

    /**
        * @brief 检测用户是否满足该促销
        *
        * @param $companyId
        * @param $marketingId
        * @param $userId
        *
        * @return
     */
    public function checkUserApplyActivityRules($companyId, $marketingId, $userId, $itemId = null, $shopId = null)
    {
        //获取活动详情
        $filter['company_id'] = $companyId;
        $filter['marketing_id'] = $marketingId;
        $detail = $this->entityRepository->getInfo($filter);
        //检测指定商品是否包含在该活动中，未指定商品的活动不检测
        $relItemArr = $this->getValidActivityRelItems($companyId, $itemId, $marketingId);
        if ($itemId && !is_array($itemId) && $detail['use_bound'] == 1 && !($relItemArr[$marketingId][$itemId] ?? [])) {
            throw new ResourceException(trans('PromotionsBundle.activity_not_applicable_product'));
        }
        //检测指定的店铺是否包含在活动中，未指定店铺的活动不检测
        if ($shopId && $detail['use_shop'] == 1 && !in_array($shopId, $detail['shop_ids'])) {
            throw new ResourceException(trans('PromotionsBundle.activity_not_applicable_store'));
        }
        //验证活动有效性
        if (!$detail || $detail['status'] != 'ongoing') {
            throw new ResourceException(trans('PromotionsBundle.activity_not_started_or_expired'));
        }
        //验证会员权限
        $gradId = $this->getUserGrade($userId, $companyId);
        if ($detail['valid_grade'] && $gradId && !in_array($gradId, $detail['valid_grade'])) {
            throw new ResourceException(trans('PromotionsBundle.member_grade_cannot_join_activity'));
        }
        //验证参与次数
        $usedCount = $this->getMarketingJoinNumByUser($companyId, $userId, $marketingId);
        if ($detail['join_limit'] > 0 && $usedCount > 0 && $usedCount >= $detail['join_limit']) {
            throw new ResourceException(trans('PromotionsBundle.reached_participation_limit'));
        }
        return true;
    }

    public function getPlusPriceBuyItem($companyId, $activityId, $page = 1, $pageSize = -1, $orderBy = ['price' => 'ASC', 'item_id' => 'desc'])
    {
        $filter = [
            'company_id' => $companyId,
            'marketing_id' => $activityId,
        ];
        $info = $this->entityRepository->getInfo($filter);
        if ($info['status'] == 'ongoing' && $info['marketing_type'] == 'plus_price_buy') {
            return $info;
        }
        return [];
    }

    public function saveMarketingJoinNumByUser($companyId, $userId, $marketingId, $isPlus = true)
    {
        if ($isPlus) {
            return app('redis')->hincrby($this->_key($companyId, $marketingId), "user_".$userId, 1);
        } else {
            return app('redis')->hincrby($this->_key($companyId, $marketingId), "user_".$userId, -1);
        }
    }

    public function getMarketingJoinNumByUser($companyId, $userId, $marketingId)
    {
        $result = app('redis')->hget($this->_key($companyId, $marketingId), "user_".$userId);
        return intval($result);
    }

    // 设置活动库存的存储有效期
    public function setExpireat($activityId, $companyId, $activityEndTime)
    {
        return app('redis')->expireat($this->_key($companyId, $activityId), $activityEndTime + 86400); // 冗余一天
    }

    private function _key($companyId, $marketingId)
    {
        $key = 'MarketingUserJoinNum:'.$companyId.':'.$marketingId;
        return $key;
    }

    public function lessUserJoinMarketingNum($companyId, $userId, $activityIds)
    {
        foreach ((array)$activityIds as $activityId) {
            $this->saveMarketingJoinNumByUser($companyId, $userId, $activityId, false);
        }
        return true;
    }

    public function getMarketingActivityByGoodsId($goodsIds, $userId, $companyId)
    {
        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $criteria->select('promotion_tag,  marketing_id, item_id, goods_id, start_time, end_time')
          ->from('promotions_marketing_activity_items');
        $criteria->andWhere($criteria->expr()->eq('company_id', $criteria->expr()->literal($companyId)));
        if ($goodsIds) {
            $goodsIds = (array)$goodsIds;
            $criteria->andWhere($criteria->expr()->in('goods_id', $goodsIds));
        }
        $startTime = time();
        $endTime = time();
        $criteria = $criteria->andWhere($criteria->expr()->orX(
            $criteria->expr()->andX(
                $criteria->expr()->lte('start_time', $startTime),
                $criteria->expr()->gte('end_time', $startTime)
            ),
            $criteria->expr()->andX(
                $criteria->expr()->lte('start_time', $endTime),
                $criteria->expr()->gt('end_time', $endTime)
            )
        ));
        $activityItemList = $criteria->execute()->fetchAll();
        $relItemArr = [];
        if ($activityItemList) {
            foreach ($activityItemList as $k => $val) {
                $relItemArr[$val['goods_id']][$val['marketing_id']] = $val;
            }
        }
        return $relItemArr;
    }

    /**
     * 获取指定当前或者指定时间范围内有效的会员优先购活动（购物车应用了）
     */
    public function getValiMemberpreferenceByItemId($companyId, $itemId, $userId = null, $marketingId = null, $goodsId = null, &$msg)
    {
        $marketingType = ['member_preference'];
        //获取当前有效的活动列表
        $activityList = $this->getValidActivitys($companyId, $marketingId, '', '', $marketingType);
        //获取有效活动的所有商品列表
        $marketingIds = array_column($activityList, 'marketing_id');
        $relItemArr = $this->getValidActivityRelItems($companyId, $itemId, $marketingIds, '', '', $marketingType, $goodsId);
        if (!$relItemArr) {
            return true;
        }
        if (intval($userId) == 0) {
            $msg = trans('PromotionsBundle.members_only_specific_purchase');
            return false;
        }
        $curMarketingIds = array_keys($relItemArr);
        //获取指定会员的等级信息
        $userGrade = $this->getUserGrade($userId, $companyId);
        //系统所有的会员等级信息
        $memberGrade = $this->getMemberGrade($companyId);
        $resultList = [];
        foreach ($activityList as $value) {
            if (!in_array($value['marketing_id'], $curMarketingIds)) {
                continue;
            }
            if ($itemId && !is_array($itemId) && $value['use_bound'] == 1 && !($relItemArr[$value['marketing_id']][$itemId] ?? [])) {
                $msg = trans('PromotionsBundle.activity_product_error');
                return false;
            }
            //检测指定的会员是否包含在活动指定的会员登记中
            $value['valid_grade'] = json_decode($value['valid_grade'], true);
            if ($value['valid_grade'] && $userGrade && !in_array($userGrade, $value['valid_grade'])) {
                $msg = trans('PromotionsBundle.members_only_specific_purchase');
                return false;
            }
            $resultList[] = $value;
        }
        return $resultList;
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
}
