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

class FullGift implements MarketingAcivityInterface
{
    private $totalDiscountFee = 0;
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        $rulestr = '';
        switch ($filterType) {
        case "quantity":
            foreach ($rulesArr as $value) {
                $rulestr .= '';
                $rulestr .= '购买满'.$value['full'].'件，送赠品;';
            }
            break;
        case "totalfee":
            foreach ($rulesArr as $value) {
                $rulestr .= '消费满'.$value['full'].'元，送赠品;';
            }
            break;
        default:
            $rulestr = '';
            break;
        }
        return $rulestr;
    }

    /**
     * @brief 应用满X件(Y折/Y元)
     *
     * @param $params
     *
     * @return array
     */
    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee)
    {
        $rules = $activity['condition_value'];
        foreach ($rules as $k => $rule) {
            $ruleArray['full'][$k] = $rule['full'];
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';
        $activityId = $activity['marketing_id'];
        $companyId = $activity['company_id'];

        $giftItem = [];
        foreach ($ruleArray['full'] as $full) {
            if ($totalNum >= $full) {
                $giftItem = $this->getGiftItem($companyId, $activityId, $full);
                if($activity['in_proportion']) {
                    $multiple = intval(bcdiv($totalNum, $full));
                    foreach ($giftItem as &$value) {
                        $value['gift_num'] = $value['gift_num'] * $multiple;
                    }
                }
                $discountDesc = "消费满".$full."件，送赠品：";
                foreach ($giftItem as $gift) {
                    $discountDesc .= $gift['item_name']." x ".$gift['gift_num']."；";
                }
                break;
            }
        }

        if (!$giftItem) {
            $activityId = 0;
        }
        $result['discount_desc'] = [
            'type' => 'full_gift',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $this->totalDiscountFee,
            'max_limit' => $activity['join_limit'] == 0 ? PHP_INT_MAX : $activity['join_limit'],
        ];
        $result['activity_id'] = $activityId;
        $result['gifts'] = $giftItem;
        $result['discount_fee'] = $this->totalDiscountFee;
        return $result;
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
        $rules = $activity['condition_value'];
        foreach ($rules as $k => $rule) {
            $ruleArray['full'][$k] = bcmul($rule['full'], 100);
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';
        $activityId = $activity['marketing_id'];
        $companyId = $activity['company_id'];

        $giftItem = [];
        foreach ($ruleArray['full'] as $full) {
            if ($totalFee >= $full) {
                $giftItem = $this->getGiftItem($companyId, $activityId, $full);
                if($activity['in_proportion']) {
                    $multiple = intval(bcdiv($totalFee, $full));
                    foreach ($giftItem as &$value) {
                        $value['gift_num'] = $value['gift_num'] * $multiple;
                    }
                }
                $discountDesc = "消费满".($full / 100)."元，送赠品：";
                foreach ($giftItem as $gift) {
                    $discountDesc .= $gift['item_name']." x ".$gift['gift_num']."；";
                }
                break;
            }
        }

        if (!$giftItem) {
            $activityId = 0;
        }
        $result['discount_desc'] = [
            'type' => 'full_gift',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $this->totalDiscountFee,
            'max_limit' => $activity['join_limit'] == 0 ? PHP_INT_MAX : $activity['join_limit'],
        ];
        $result['activity_id'] = $activityId;
        $result['gifts'] = $giftItem;
        $result['discount_fee'] = $this->totalDiscountFee;
        return $result;
    }

    private function getGiftItem($companyId, $activityId, $full)
    {
        $filter = ['company_id' => $companyId, 'marketing_id' => $activityId, 'filter_full' => $full];
        $entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
        $giftLists = $entityGiftRelRepository->lists($filter)['list'];

        $itemIds = array_column($giftLists, 'item_id');
        $itemService = new ItemsService();
        $itemFilter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($itemFilter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        foreach ($giftLists as $key => &$value) {
            if (!isset($itemdata[$value['item_id']])) {
                unset($giftLists[$key]);
                continue;
            }
            $value = array_merge($value, $itemdata[$value['item_id']]);
            $this->totalDiscountFee += $value['price'];
        }

        return $giftLists;
    }
}
