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

class FullDiscount implements MarketingAcivityInterface
{
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        $rulestr = '';
        switch ($filterType) {
        case "quantity":
            foreach ($rulesArr as $value) {
                $rulestr .= '';
                $rulestr .= '购买满'.$value['full'].'件，减'.$value['discount']."%优惠;";
            }
            break;
        case "totalfee":
            foreach ($rulesArr as $value) {
                $rulestr .= '消费满'.$value['full'].'元，减'.$value['discount']."%优惠;";
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
        * @return integer
     */
    public function applyActivityQuantity(array $activity, int $totalNum, int $totalFee)
    {
        $rules = $activity['condition_value'];
        foreach ($rules as $k => $rule) {
            $ruleArray['full'][$k] = $rule['full'];
            $ruleArray['discount'][$k] = $rule['discount']; //给予的优惠优惠
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';

        if ($totalNum >= $ruleArray['full'][$ruleLength - 1]) {
            $rulePercent = $ruleArray['discount'][$ruleLength - 1];
            $discountPrice = bcmul($totalFee, ($rulePercent / 100));
            $discountDesc = "消费满".$ruleArray['full'][$ruleLength - 1]."件，给予".$ruleArray['discount'][$ruleLength - 1]."%优惠";
        } elseif ($totalNum < $ruleArray['full'][0]) {
            $discountPrice = 0;
        } else {
            $discountPrice = 0;
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalNum >= $ruleArray['full'][$i] && $totalNum < $ruleArray['full'][$i + 1]) {
                    $rulePercent = $ruleArray['discount'][$i];
                    $discountPrice = bcmul($totalFee, ($rulePercent / 100));
                    $discountDesc = "消费满".$ruleArray['full'][$i]."件，给予".$ruleArray['discount'][$i]."%优惠";
                    break;
                }
            }
        }
        if ($discountPrice < 0 || $totalFee < $discountPrice) {
            $discountPrice = 0;
        }

        $discountInfo = [
            'type' => 'full_discount',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];
        $result['discount_fee'] = $discountPrice;
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];
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
            $ruleArray['discount'][$k] = $rule['discount'];  //给予的优惠优惠
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';

        if ($totalFee >= $ruleArray['full'][$ruleLength - 1]) {
            $rulePercent = $ruleArray['discount'][$ruleLength - 1];
            $discountPrice = bcmul($totalFee, ($rulePercent / 100));
            $discountDesc = "消费满".($ruleArray['full'][$ruleLength - 1] / 100)."元，给予".$ruleArray['discount'][$ruleLength - 1]."%优惠";
        } elseif ($totalFee < $ruleArray['full'][0]) {
            $discountPrice = 0;
        } else {
            $discountPrice = 0;
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalFee >= $ruleArray['full'][$i] && $totalFee < $ruleArray['full'][$i + 1]) {
                    $rulePercent = $ruleArray['discount'][$i];
                    $discountPrice = bcmul($totalFee, ($rulePercent / 100));
                    $discountDesc = "消费满".($ruleArray['full'][$i] / 100)."元，给予".$ruleArray['discount'][$i]."%优惠";
                    break;
                }
            }
        }
        if ($discountPrice < 0) {
            $discountPrice = 0;
        }
        $discountInfo = [
            'type' => 'full_discount',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];

        $result['discount_fee'] = $discountPrice;
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];
        return $result;
    }
}
