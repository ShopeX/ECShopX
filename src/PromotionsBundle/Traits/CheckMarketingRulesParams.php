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

namespace PromotionsBundle\Traits;

use PromotionsBundle\Entities\MarketingActivityItems;
use PromotionsBundle\Services\MarketingActivityService;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Entities\MarketingGiftItems;
use GoodsBundle\Services\ItemsService;

trait CheckMarketingRulesParams
{
    use CheckPromotionsValid;

    public function checkAddPromotionData(&$params)
    {
        $rules = [
            'marketing_name' => ['required', '活动名称必填'],
            'start_time' => ['required', '活动开始时间必填'],
            'end_time' => ['required', '活动结束时间必填'],
            'company_id' => ['required', '企业id必填'],
            'condition_value' => ['required', '活动规则必填'],
            'condition_type' => ['required', '活动规则条件类型必填'],
            'marketing_type' => ['required', '活动类型有误'],
            'marketing_desc' => ['required', '活动详细描述必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if ($params['marketing_type'] == 'plus_price_buy') {
            $rules = [
                'navbar_color' => ['required', '请选择导航栏颜色'],
                'activity_background' => ['required', '请上传活动背景图'],
            ];
            $errorMessage = validator_params($params, $rules);
            if ($errorMessage) {
                throw new ResourceException($errorMessage);
            }
        }

        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);
        $params['item_ids'] = $params['item_ids'] ?? [];

        if (!in_array($params['marketing_type'], ['full_discount', 'full_minus', 'full_gift', 'plus_price_buy', 'single_gift','self_select','full_court_gift', 'member_preference'])) {
            throw new ResourceException('活动类型有误');
        }
        if (!in_array($params['condition_type'], ['totalfee', 'quantity'])) {
            throw new ResourceException('活动规则条件类型有误');
        }
        //验证活动商品必填
        if (!$params['item_ids'] && $params['use_bound'] == 1) {
            throw new ResourceException('活动商品必填');
        }

        if ($params['use_bound'] == 1) {
            $itemsService = new ItemsService();
            if ($itemsService->__checkIsGiftItem($params['company_id'], $params['item_ids'])) {
                throw new ResourceException('存在赠品，请检查后再次提交');
            }
        }

        //验证活动店铺必填
        if ($params['use_shop'] == 1 && !($params['shop_ids'] ?? [])) {
            throw new ResourceException('活动店铺必填');
        }
        //校验促销有效期时间
        //if( $params['start_time'] <= time() ) {
        //    throw new ResourceException('活动生效时间不能小于当前时间！');
        //}
        if ($params['end_time'] <= $params['start_time']) {
            throw new ResourceException('活动结束时间不能小于开始时间！');
        }

        if ($params['marketing_id'] ?? 0) {
            $service = new MarketingActivityService();
            $rs = $service->getInfo(['marketing_id' => $params['marketing_id']]);
            if (!$rs) {
                throw new ResourceException('编辑的活动不存在');
            }

            //if ($rs['status'] == 'ongoing') {
            //    throw new ResourceException('进行中的活动不可编辑');
            //}
        }
        if ($params['marketing_type'] == 'self_select' || $params['marketing_type'] == 'full_gift') {
            //互斥判断已经移到 checkActivityValidByMarketing
            //$this->checkItem($params['company_id'], $params['item_ids'], $params['marketing_type'], $params['start_time'], $params['end_time'], $params['marketing_id']);
        }

        if ($params['marketing_type'] == 'full_discount' || $params['marketing_type'] == 'full_minus') {
            //互斥判断已经移到 checkActivityValidByMarketing
            //$this->checkItem($params['company_id'], $params['item_ids'], $params['marketing_type'], $params['start_time'], $params['end_time'], $params['marketing_id']);
        }

        if ($params['use_bound'] != 0) {
            //检验商品是否参加满赠或加价购的赠品
            //互斥判断已经移到 checkActivityValidByMarketing
            //$this->checkgiftItem($params['company_id'], $params['item_ids'], $params['start_time'], $params['end_time']);
        }
        if ($params['marketing_type'] == 'full_court_gift') {
            //检验该时间段只有一个活动
            $this->checkgiftItem($params['company_id'], '', $params['start_time'], $params['end_time'], $params['marketing_id'] ?? 0, 'full_court_gift');
        }
        // 满减金额的规则检验;类型中需要检查的项;
        switch ($params['marketing_type']) {
            case "full_discount":
                return $this->checkFullDiscount($params);
            case "full_minus":
                return $this->checkFullMinus($params);
            case "full_gift":
            case "single_gift":
                return $this->checkFullGift($params);
            case "plus_price_buy":
                return $this->checkPlusPriceBuy($params);
            case "self_select":
                return $this->checkSelfSelect($params);
            case "member_preference":
                $this->checkItem($params['company_id'], $params['item_ids'], $params['marketing_type'], $params['start_time'], $params['end_time'], $params['marketing_id']);
                return $this->checkMemberPreference($params);
            default:
                return true;
        }
    }

    public function checkValidItem($company_id, $marketing_type, $item_id, $start_time, $end_time, $marketing_id = 0)
    {
        if ($marketing_type == 'self_select' || $marketing_type == 'full_gift') {
            $this->checkItem($company_id, $item_id, $marketing_type, $start_time, $end_time, $marketing_id);
        }

        if ($marketing_type == 'full_discount' || $marketing_type == 'full_minus') {
            $this->checkItem($company_id, $item_id, $marketing_type, $start_time, $end_time, $marketing_id);
        }

        //检验商品是否参加满赠或加价购的赠品
        $this->checkgiftItem($company_id, $item_id, $start_time, $end_time);

        if ($marketing_type == 'full_court_gift') {
            //检验该时间段只有一个活动
            $this->checkgiftItem($company_id, '', $start_time, $end_time, $marketing_id, 'full_court_gift');
        }
    }

    private function checkFullDiscount(&$data)
    {
        $ruleArray = $data['condition_value'];
        $ruleLength = count($ruleArray);
        if ($data['condition_type'] == 'totalfee') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = sprintf('%.2f', floatval($ruleArray[$i]['full']));
                $ruleArray[$i]['discount'] = floatval($ruleArray[$i]['discount']);
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('金额条件必须大于1');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['full'] >= $ruleArray[$i + 1]['full']) {
                    throw new ResourceException('满X元Y折，X元必须依次递增！');
                }
                if ($ruleArray[$i]['discount'] >= 100 || $ruleArray[$i]['discount'] < 1) {
                    throw new ResourceException('折扣必须在区间1%-100%！');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['discount'] >= $ruleArray[$i + 1]['discount']) {
                    throw new ResourceException('给予折扣必须依次递增！');
                }
            }
        } elseif ($data['condition_type'] == 'quantity') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = intval($ruleArray[$i]['full']);
                $ruleArray[$i]['discount'] = floatval($ruleArray[$i]['discount']);
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('件数条件必须大于1');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['full'] >= $ruleArray[$i + 1]['full']) {
                    throw new ResourceException('购X件Y折，X件必须依次递增！');
                }
                if ($ruleArray[$i]['discount'] >= 100 || $ruleArray[$i]['discount'] < 1) {
                    throw new ResourceException('折扣必须在区间1%-100%！');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['discount'] >= $ruleArray[$i + 1]['discount']) {
                    throw new ResourceException('给予折扣必须依次递增！');
                }
            }
        }
        $data['condition_value'] = $ruleArray;
        return true;
    }

    private function checkFullMinus(&$data)
    {
        $ruleArray = $data['condition_value'];
        $ruleLength = count($ruleArray);
        if ($data['condition_type'] == 'totalfee') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = sprintf('%.2f', floatval($ruleArray[$i]['full']));
                $ruleArray[$i]['minus'] = sprintf('%.2f', floatval($ruleArray[$i]['minus']));
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('金额条件必须大于1');
                }
                if ($ruleArray[$i]['full'] <= $ruleArray[$i]['minus']) {
                    throw new ResourceException('满X元减Y元，X必须大于Y！');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['full'] >= $ruleArray[$i + 1]['full']) {
                    throw new ResourceException('满X元减Y元，X元必须依次递增！');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['minus'] >= $ruleArray[$i + 1]['minus']) {
                    throw new ResourceException('满X元减Y元，Y元必须依次递增！');
                }
            }
        } elseif ($data['condition_type'] == 'quantity') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = intval($ruleArray[$i]['full']);
                $ruleArray[$i]['minus'] = sprintf('%.2f', floatval($ruleArray[$i]['minus']));
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('件数条件必须大于1');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['full'] >= $ruleArray[$i + 1]['full']) {
                    throw new ResourceException('购X件减Y元，X件必须依次递增！');
                }
                if ($i < $ruleLength - 1 && $ruleArray[$i]['minus'] >= $ruleArray[$i + 1]['minus']) {
                    throw new ResourceException('购x件减y元，y元必须依次递增！');
                }
            }
        }
        $data['condition_value'] = $ruleArray;
        return true;
    }

    private function checkFullGift(&$data)
    {
        if (isset($data['gifts']) && !is_array($data['gifts'])) {
            $data['gifts'] = json_decode($data['gifts'], true);
        }

        if (!($data['gifts'] ?? [])) {
            throw new \LogicException('赠品不能为空');
        }

        $ruleArray = [];
        foreach ($data['gifts'] as $gift) {
            if ($data['condition_type'] == 'totalfee' && $gift['filter_full'] < 0.01) {
                throw new ResourceException('金额条件必须大于0.01');
            }
            if ($data['condition_type'] == 'quantity' && $gift['filter_full'] < 1) {
                throw new ResourceException('件数条件必须大于1');
            }
            $ruleArray[$gift['filter_full']]['full'] = $gift['filter_full'];
        }
        krsort($ruleArray, SORT_NUMERIC);
        $data['condition_value'] = array_values($ruleArray);

        return true;
    }

    private function checkPlusPriceBuy(&$data)
    {
        $itemsService = new ItemsService();
        if (isset($data['gifts']) && !is_array($data['gifts'])) {
            $data['gifts'] = json_decode($data['gifts'], true);
            foreach ($data['gifts'] as $key => $gifts) {
                $itemInfo = $itemsService->getItem(['item_id' => $gifts['item_id']]);
                if ($itemInfo['price'] < $gifts['price'] * 100) {
                    throw new \LogicException('赠品金额不能低于加价购金额');
                }
                $data['gifts'][$key]['price'] = sprintf('%.2f', floatval($gifts['price']));
            }
        }

        if (!($data['gifts'] ?? [])) {
            throw new \LogicException('赠品不能为空');
        }
        $ruleArray = $data['condition_value'];
        $ruleLength = count($ruleArray);
        if ($data['condition_type'] == 'totalfee') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = sprintf('%.2f', floatval($ruleArray[$i]['full']));
                $ruleArray[$i]['price'] = sprintf('%.2f', floatval($ruleArray[$i]['price']));
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('金额条件必须大于1');
                }
            }
        } elseif ($data['condition_type'] == 'quantity') {
            for ($i = 0; $i < $ruleLength; $i++) {
                $ruleArray[$i]['full'] = intval($ruleArray[$i]['full']);
                $ruleArray[$i]['price'] = sprintf('%.2f', floatval($ruleArray[$i]['price']));
                if ($ruleArray[$i]['full'] < 1) {
                    throw new ResourceException('件数条件必须大于1');
                }
            }
        }
        $data['condition_value'] = $ruleArray;
        return true;
    }
    private function checkSelfSelect($data)
    {
        $ruleArray = reset($data['condition_value']);
        if ($ruleArray['full'] < 1) {
            throw new ResourceException('金额必须大于0');
        }
        if ($ruleArray['num'] < 1) {
            throw new ResourceException('件数必须大于0');
        }
        return true;
    }

    private function checkItem($companyId, $itemIds, $type = null, $beginTime = null, $endTime = null, $activityId = null)
    {
        $filter['company_id'] = $companyId;
        if ($activityId) {
            $filter['marketing_id|neq'] = $activityId;
        }
        $filter['end_time|gte'] = time();
        $filter['item_id'] = $itemIds;

        if ($type) {
            if ($type == 'plus_price_buy') {
                $filter['marketing_type|neq'] = $type;
            } elseif ($type == 'full_discount' || $type == 'full_minus') {
                $filter['marketing_type|neq'] = 'full_gift';
            } else {
                $filter['marketing_type'] = $type;
            }
        }

        // if ($type == 'full_gift' || $type = 'single_gift') {
        //     $filter['marketing_type'] = ['full_discount', 'full_minus', $type];
        // }

        $beginTime = $beginTime ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();

        $marketingRelGoodsRepository = app('registry')->getManager('default')->getRepository(MarketingActivityItems::class);
        //新增的活动开始时间如果包含在以后活动中，需要判断商品是否存在
        $filter['start_time|lte'] = $beginTime;
        $filter['end_time|gt'] = $beginTime;
        $relLists = $marketingRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            $errormsg = str_limit($relLists['list'][0]['item_name'], 20, '...').'('.$relLists['list'][0]['item_spec_desc'].')';
            throw new ResourceException("在相同时段内，同一个商品只能参加一个活动。marketing_id:".$relLists['list'][0]['marketing_id'].'。'.$errormsg);
        }
        unset($filter['start_time|lte'], $filter['end_time|gt']);

        //新增的活动结束时间如果包含在以后活动中，需要判断商品是否存在
        $filter['start_time|lt'] = $endTime;
        $filter['end_time|gte'] = $endTime;
        $relLists = $marketingRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            $errormsg = str_limit($relLists['list'][0]['item_name'], 20, '...').'('.$relLists['list'][0]['item_spec_desc'].')';
            throw new ResourceException("在相同时段内，同一个商品只能参加一个活动。marketing_id:".$relLists['list'][0]['marketing_id'].'。'.$errormsg);
        }
        unset($filter['start_time|lt'], $filter['end_time|gte']);

        //新增的时间 包含原有时间的活动
        $filter['end_time|gte'] = time();
        $filter['start_time|gte'] = $beginTime;
        $filter['end_time|lte'] = $endTime;
        $relLists = $marketingRelGoodsRepository->lists($filter);
        if ($relLists['list']) {
            $errormsg = str_limit($relLists['list'][0]['item_name'], 20, '...').'('.$relLists['list'][0]['item_spec_desc'].')';
            throw new ResourceException("在相同时段内，同一个商品只能参加一个活动。marketing_id:".$relLists['list'][0]['marketing_id'].'。'.$errormsg);
        }
        unset($filter['start_time|gte'], $filter['end_time|lte']);
        return true;
    }
    //参加加价购的赠品不能参加活动
    private function checkgiftItem($companyId, $itemIds = null, $beginTime = null, $endTime = null, $activityId = null, $marketing_type = null)
    {
        $filter['company_id'] = $companyId;
        if ($activityId) {
            $filter['marketing_id|neq'] = $activityId;
        }
        $filter['marketing_type'] = ['plus_price_buy', 'single_gift'];
        if ($marketing_type) {
            $filter['marketing_type'] = $marketing_type;
        }

        $beginTime = $beginTime ? $beginTime : time();
        $endTime = $endTime ? $endTime : time();

        $service = new MarketingActivityService();

        $filter['start_time|lte'] = $endTime;
        $filter['end_time|gte'] = ($beginTime > time()) ? $beginTime : time();

        $relLists = $service->lists($filter);

        if ($relLists['list']) {
            if ($marketing_type == 'full_court_gift') {
                throw new ResourceException("在相同时段内，只能有一个全场赠活动。");
            }
            $newfilter['company_id'] = $companyId;
            $newfilter['item_id'] = $itemIds;
            $newfilter['marketing_id'] = array_column($relLists['list'], 'marketing_id');
            $marketingRelGoodsRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
            $giftlist = $marketingRelGoodsRepository->lists($newfilter);

            if ($giftlist['list']) {
                $errormsg = str_limit($giftlist['list'][0]['item_name'], 20, '...').'('.$giftlist['list'][0]['item_spec_desc'].')';
                throw new ResourceException("在相同时段内,该商品已经参加一个满赠或者加价购活动。".$errormsg);
            }
        }
        return true;
    }

    /**
     * 会员优先购的检查项
     * @param  array $data 提交数据
     * @return bool
     */
    private function checkMemberPreference($data)
    {
        $data['valid_grade'] = $data['valid_grade'] ?? [];
        if (empty($data['valid_grade'])) {
            throw new ResourceException('请至少选择一个会员等级');
        }
        //验证团购商品指定时段是否有活动
        $this->checkGroupActivity($data['company_id'], $data['item_ids'], $data['start_time'], $data['end_time']);
        $this->checkBargainActivity($data['company_id'], $data['item_ids'], $data['start_time'], $data['end_time']);

        return true;
    }
}
