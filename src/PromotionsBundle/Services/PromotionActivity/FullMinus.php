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

class FullMinus implements MarketingAcivityInterface
{
    public function getFullProRules(string $filterType, array $rulesArr)
    {
        $rulestr = '';
        switch ($filterType) {
        case "quantity":
            foreach ($rulesArr as $value) {
                $rulestr .= '购买满'.$value['full'].'件，减'.$value['minus']."元;";
            }
            break;
        case "totalfee":
            foreach ($rulesArr as $value) {
                $rulestr .= '消费满'.$value['full'].'元，减'.$value['minus']."元;";
            }
            break;
        default:
            $rulestr = '';
            break;
        }
        return $rulestr;
    }

    /**
     * @brief 应用满X件Y折
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
            $ruleArray['minus'][$k] = bcmul($rule['minus'], 100);
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';

        if ($totalNum >= $ruleArray['full'][$ruleLength - 1]) {
            if ($activity['canjoin_repeat'] == 1) {
                if ($ruleArray['full'][$ruleLength - 1] > 0) {
                    $multiple = floor(bcdiv($totalNum, $ruleArray['full'][$ruleLength - 1], 3));
                    $discountPrice = bcmul($ruleArray['minus'][$ruleLength - 1], $multiple);
                    $discountDesc = "消费满".$ruleArray['full'][$ruleLength - 1]."件，减".($ruleArray['minus'][$ruleLength - 1] / 100)."元,且上不封顶";
                }
            } else {
                $discountPrice = $ruleArray['minus'][$ruleLength - 1];
                $discountDesc = "消费满".$ruleArray['full'][$ruleLength - 1]."件，减".($ruleArray['minus'][$ruleLength - 1] / 100)."元";
            }
        } elseif ($totalNum < $ruleArray['full'][0]) {
            $discountPrice = 0;
        } else {
            $discountPrice = 0;
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalNum >= $ruleArray['full'][$i] && $totalNum < $ruleArray['full'][$i + 1]) {
                    $discountPrice = $ruleArray['minus'][$i];
                    $discountDesc = "消费满".$ruleArray['full'][$i]."件，减".($ruleArray['minus'][$i] / 100)."元";
                    break;
                }
            }
        }
        if ($discountPrice < 0 || $totalFee < $discountPrice) {
            $discountPrice = 0;
        }
        $discountInfo = [
            'type' => 'full_minus',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];

        $result['discount_fee'] = $discountPrice;
        return $result;
    }

    /**
     * @brief  应用满X元Y折
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
            $ruleArray['minus'][$k] = bcmul($rule['minus'], 100) ;
        }
        $ruleLength = count($ruleArray['full']);
        $discountDesc = '';

        if ($totalFee >= $ruleArray['full'][$ruleLength - 1]) {
            if ($activity['canjoin_repeat'] == 1) {
                if ($ruleArray['full'][$ruleLength - 1] > 0) {
                    $multiple = floor(bcdiv($totalFee, $ruleArray['full'][$ruleLength - 1], 3));
                    $discountPrice = bcmul($ruleArray['minus'][$ruleLength - 1], $multiple);
                    $discountDesc = "消费满".($ruleArray['full'][$ruleLength - 1] / 100)."元，减".($ruleArray['minus'][$ruleLength - 1] / 100)."元,且上不封顶";
                }
            } else {
                $discountPrice = $ruleArray['minus'][$ruleLength - 1];
                $discountDesc = "消费满".($ruleArray['full'][$ruleLength - 1] / 100)."元，减".($ruleArray['minus'][$ruleLength - 1] / 100)."元";
            }
        } elseif ($totalFee < $ruleArray['full'][0]) {
            $discountPrice = 0;
        } else {
            $discountPrice = 0;
            for ($i = 0; $i < $ruleLength - 1; $i++) {
                if ($totalFee >= $ruleArray['full'][$i] && $totalFee < $ruleArray['full'][$i + 1]) {
                    $discountPrice = $ruleArray['minus'][$i];
                    $discountDesc = "消费满".($ruleArray['full'][$i] / 100)."元，减".($ruleArray['minus'][$i] / 100)."元";
                    break;
                }
            }
        }

        if ($discountPrice < 0) {
            $discountPrice = 0;
        }
        $discountInfo = [
            'type' => 'full_minus',
            'id' => $activity['marketing_id'],
            'rule' => $discountDesc,
            'info' => $activity['marketing_name'],
            'discount_fee' => $discountPrice,
        ];
        $result['discount_desc'] = $discountDesc ? $discountInfo : [];
        $result['discount_fee'] = $discountPrice;
        return $result;
    }
}
