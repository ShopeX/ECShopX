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

namespace PromotionsBundle\Services\PromotionActivity;

use PromotionsBundle\Interfaces\MarketingAcivityInterface;
use PromotionsBundle\Entities\MarketingGiftItems;
use GoodsBundle\Services\ItemsService;

class MemberPreference implements MarketingAcivityInterface
{
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        return '';
//        $rulestr = '';
//        switch ($filterType) {
//        case "quantity":
//            foreach ($rulesArr as $value) {
//                $rulestr .= '';
//                $rulestr .= '购买满'.$value['full'].'件，加价'.$value['price'].'元换购商品';
//            }
//            break;
//        case "totalfee":
//            foreach ($rulesArr as $value) {
//                $rulestr .= '消费满'.$value['full'].'元，加价'.$value['price'].'元换购商品';
//            }
//            break;
//        default:
//            $rulestr = '';
//            break;
//        }
//        return $rulestr;
    }

    /**
     * @brief 应用满X件(Y折/Y元)
     *
     * @param $params
     *
     * @return integer
     */
    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee)
    {
        return true;
//        $maxLimit = $activity['join_limit'];
//        $rules = $activity['condition_value'];
//        foreach ($rules as $k =>$rule) {
//            $ruleArray['full'][$k] = $rule['full'];
//            $ruleArray['price'][$k] = $rule['price'];
//        }
//        $ruleLength = count($ruleArray['full']);
//        $discountDesc = '';
//        $activityId = $activity['marketing_id'];
//        $companyId = $activity['company_id'];
//        $plusPrice = 0;
//        if ($totalNum >= $ruleArray['full'][$ruleLength-1]) {
//            $discountDesc = "消费满".$ruleArray['full'][$ruleLength-1]."件，加价".$ruleArray['price'][$ruleLength-1]."元换购商品";
//            $plusPrice = $ruleArray['price'][$ruleLength-1];
//            $giftItem = $this->getGiftItem($companyId,$activityId);
//        } else if ($totalNum < $ruleArray['full'][0]) {
//            $giftItem = [];
//        } else {
//            for ($i = 0; $i < $ruleLength-1; $i++) {
//                if ($totalNum >= $ruleArray['full'][$i] && $totalNum < $ruleArray['full'][$i+1]) {
//                    $discountDesc = "消费满".$ruleArray['full'][$i]."件，加价".$ruleArray['price'][$i]."元换购商品";
//                    $plusPrice = $ruleArray['price'][$i];
//                    $giftItem = $this->getGiftItem($companyId,$activityId);
//                    break;
//                }
//            }
//        }
//        if (!$giftItem) {
//            $activityId = 0;
//        }
//        $result['discount_desc'] = [
//            'type' => 'plus_price_buy',
//            'id' => $activity['marketing_id'],
//            'rule' => $discountDesc,
//            'info' => $activity['marketing_name'],
//            'discount_fee' => 0,
//            'max_limit' => $maxLimit,
//            'plus_price' => $plusPrice,
//        ];
//        $result['activity_id'] = $activityId;
//        $result['plus_buy_items'] = $giftItem;
//        return $result;
    }

    /**
     * @brief  应用满X元(Y折/Y元)
     *
     * @param $params
     *
     * @return
     */
    public function applyActivityTotalfee(array $activity, int $totalFee)
    {
        return true;
//        $maxLimit = $activity['join_limit'];
//        $rules = $activity['condition_value'];
//        foreach ($rules as $k =>$rule) {
//            $ruleArray['full'][$k] = bcmul($rule['full'], 100);
//            $ruleArray['price'][$k] = $rule['price'];
//        }
//        $ruleLength = count($ruleArray['full']);
//        $discountDesc = '';
//        $activityId = $activity['marketing_id'];
//        $companyId = $activity['company_id'];
//        $plusPrice = 0;
//        if ($totalFee >= $ruleArray['full'][$ruleLength-1]) {
//            $discountDesc = "消费满".($ruleArray['full'][$ruleLength-1]/100)."元，加价".$ruleArray['price'][$ruleLength-1]."元换购商品";
//            $plusPrice = $ruleArray['price'][$ruleLength-1];
//            $giftItem = $this->getGiftItem($companyId,$activityId);
//        } elseif ($totalFee < $ruleArray['full'][0]) {
//            $giftItem = [];
//        } else {
//            for ($i = 0; $i < $ruleLength-1; $i++) {
//                if ($totalFee >= $ruleArray['full'][$i] && $totalFee < $ruleArray['full'][$i+1]) {
//                    $discountDesc = "消费满".($ruleArray['full'][$i]/100)."元，加价".$ruleArray['price'][$i]."元换购商品";
//                    $plusPrice = $ruleArray['price'][$i];
//                    $giftItem = $this->getGiftItem($companyId,$activityId);
//                    break;
//                }
//            }
//        }
//        if (!$giftItem) {
//            $activityId = 0;
//        }
//
//        $result['discount_desc'] = [
//            'type' => 'plus_price_buy',
//            'id' => $activity['marketing_id'],
//            'rule' => $discountDesc,
//            'info' => $activity['marketing_name'],
//            'discount_fee' => 0,
//            'max_limit' => $maxLimit,
//            'plus_price' => $plusPrice,
//        ];
//        $result['activity_id'] = $activityId;
//        $result['plus_buy_items'] = $giftItem;
//        return $result;
    }
    private function getGiftItem($companyId, $activityId)
    {
        return true;
//        $filter = ['company_id' => $companyId, 'marketing_id' => $activityId];
//        $entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
//        $plus_items = $entityGiftRelRepository->lists($filter)['list'];
//
//        $itemIds = array_column($plus_items, 'item_id');
//        $itemService = new ItemsService();
//        $itemFilter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
//        $itemsList = $itemService->getSkuItemsList($itemFilter);
//        $itemdata = array_column($itemsList['list'], null, 'item_id');
//
//        foreach ($plus_items as &$value) {
//            if ($itemdata[$value['item_id']] ?? []) {
//                $value['plus_price'] = $value['price'];
//                $value = array_merge($value, $itemdata[$value['item_id']]);
//            }
//        }
//
//        return array_column($plus_items, null,'item_id');
    }
}
