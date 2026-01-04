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

namespace KaquanBundle\Jobs;

use EspierBundle\Jobs\Job;
use KaquanBundle\Services\UploadServices\WechatCard;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Entities\WechatRelCard;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class UploadWechatCard extends Job
{
    protected $companyId;
    protected $appId;
    protected $cardIds = [];

    public function __construct($appId, $companyId, $cardIds)
    {
        $this->appId = $appId;
        $this->companyId = $companyId;
        $this->cardIds = $cardIds;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $row = ['*'];
        $filter['company_id'] = $this->companyId;
        $filter['card_id|in'] = $this->cardIds;

        $discountCardRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
        $wechatCardRepository = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
        $shopsService = new ShopsService(new WxShopsService());
        $shopdata = $shopsService->getWxShopsSetting($this->companyId);

        $lists = $discountCardRepository->getList($row, $filter);
        foreach ($lists as $cardData) {
            $wechatFilter['card_id'] = $cardData['card_id'];
            $wechatFilter['company_id'] = $this->companyId;
            $wechatData = $wechatCardRepository->getInfo($wechatFilter);
            if (!$wechatData) {
                $cardId = $this->addToWechat($cardData, $shopdata);
                app('log')->debug('5-----'.var_export($cardId, 1));
                $wechatData['card_id'] = $cardData['card_id'];
                $wechatData['company_id'] = $this->companyId;
                $wechatData['wechat_card_id'] = $cardId;
                app('log')->debug('6-----'.var_export($wechatData, 1));
                $wechatCardRepository->create($wechatData);
            }
        }
    }

    /**
     * 将卡券字段转换成微信字段
     */
    private function addToWechat($cardData, $shopdata)
    {
        $baseInfo = array();

        $baseInfo['logo_url'] = $shopdata['logo'];
        $baseInfo['brand_name'] = $shopdata['brand_name'];
        $baseInfo['title'] = $cardData['title'];
        $baseInfo['description'] = $cardData['title'];
        $baseInfo['code_type'] = 'CODE_TYPE_QRCODE';
        $baseInfo['sku']['quantity'] = $cardData['quantity'];

        $baseInfo['color'] = 'Color060';//$this->wechatColor($cardData['color']);

        if (isset($cardData['use_scenes']) && $cardData['use_scenes']) {
            $baseInfo['notice'] = '请在规定时间内使用';
            if ($cardData['use_scenes'] == "ONLINE") {
                $baseInfo['notice'] = "请在下单时选择优惠券";
            } elseif ($cardData['use_scenes'] == "QUICK") {
                $baseInfo['notice'] = "请在买单时选择优惠券";
            } elseif ($cardData['use_scenes'] == "SWEEP") {
                $baseInfo['notice'] = "到店请出示二维码";
            } elseif ($cardData['use_scenes'] == "SELF") {
                $baseInfo['notice'] = "到店请出示该卡券";
            }
        }
        //时间戳为标准0点时间
        if ($cardData['date_type'] == "DATE_TYPE_FIX_TERM") {
            $baseInfo['date_info'] = [
                'type' => "DATE_TYPE_FIX_TERM",
                'fixed_term' => $cardData['fixed_term'],
                'fixed_begin_term' => $cardData['begin_date'],
                'end_timestamp' => isset($cardData['end_date']) ? ($cardData['end_date']) : "",
            ];
        } elseif ($cardData['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
            $baseInfo['date_info'] = [
                'type' => "DATE_TYPE_FIX_TIME_RANGE",
                'begin_timestamp' => $cardData['begin_date'],
                'end_timestamp' => $cardData['end_date']
            ];
        } elseif ($cardData['date_type'] == 'DATE_TYPE_PERMANENT') {
            $baseInfo['date_info'] = [
                'type' => 'DATE_TYPE_PERMANENT'
            ];
        }

        $baseInfo['service_phone'] = $cardData['service_phone'];
        $baseInfo['use_all_locations'] = false;
        if (isset($cardData['use_all_shops']) && $cardData['use_all_shops']) {
            $baseInfo['use_all_locations'] = true;
        } elseif (isset($cardData['rel_shops_ids']) && $cardData['rel_shops_ids']) {
            $relShops = explode(',', $cardData['rel_shops_ids']);
            foreach ($relShops as $val) {
                $baseInfo['location_id_list'][] = $val;
            }
        }

        $baseInfo['get_limit'] = $cardData['get_limit'];
        $baseInfo['use_limit'] = $cardData['use_limit'];

        switch ($cardData['card_type']) {
        case "discount":
            $cardType = "DISCOUNT";
            $especial['discount'] = $cardData['discount'];
            break;
        case "cash":
            $cardType = "CASH";
            $especial['least_cost'] = $cardData['least_cost'];
            $especial['reduce_cost'] = $cardData['reduce_cost'];
            break;
        case "groupon":
            $cardType = "GROUPON";
            $especial['deal_detail'] = $cardData['deal_detail'];
            break;
        case "general_coupon":
            $cardType = "GENERAL_COUPON";
            $especial['default_detail'] = $cardData['default_detail'];
            break;
        case "gift":
            $cardType = "GIFT";
            $especial['gift'] = $cardData['gift'];
            break;
        }

        if (isset($cardData['text_image_list'])) {
            $advancedInfo['text_image_list'] = unserialize($cardData['text_image_list']);
        } else {
            $advancedInfo['text_image_list'] = [];
        }

        $advancedInfo['use_condition']['can_use_with_other_discount'] = true;
        if ($cardType == "cash") {
            $advancedInfo['use_condition']['least_cost'] = $cardData['least_cost'];
        }
        $advancedInfo['time_limit'] = [
            ['type' => 'MONDAY'],
            ['type' => 'TUESDAY'],
            ['type' => 'WEDNESDAY'],
            ['type' => 'THURSDAY'],
            ['type' => 'FRIDAY'],
            ['type' => 'SUNDAY'],
            ['type' => 'SATURDAY'],
        ];
        app('log')->debug('1-----'.var_export($cardType, 1));
        app('log')->debug('2-----'.var_export($baseInfo, 1));
        app('log')->debug('3-----'.var_export($advancedInfo, 1));
        app('log')->debug('4-----'.var_export($especial, 1));
        $wechatCardService = new WechatCard($this->appId);
        $cardId = $wechatCardService->pushWechat($cardType, $baseInfo, $advancedInfo, $especial);
        return $cardId;
    }

    private function wechatColor($color)
    {
        $colorData = [
            'Color010' => '#63b359',
            'Color020' => '#2c9f67',
            'Color030' => '#509fc9',
            'Color040' => '#5885cf',
            'Color050' => '#9062c0',
            'Color060' => '#d09a45',
            'Color070' => '#e4b138',
            'Color080' => '#ee903c',
            'Color081' => '#f08500',
            'Color082' => '#a9d92d',
            'Color090' => '#dd6549',
            'Color100' => '#cc463d',
            'Color101' => '#cf3e36',
            'Color102' => '#5E6671',
        ];
        return array_keys($colorData, $color)[0];
    }
}
