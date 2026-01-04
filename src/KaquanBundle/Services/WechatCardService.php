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

use KaquanBundle\Entities\WechatRelCard;
use KaquanBundle\Entities\DiscountCards;
use KaquanBundle\Services\UploadServices\WechatCard;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

use Dingo\Api\Exception\ResourceException;

class WechatCardService
{
    public $colorData = [
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
        'Color103' => '#026842',
    ];
    public $wechatCard;
    public $shopdata;
    public function __construct($companyId, $appId = '')
    {
        $shopsService = new ShopsService(new WxShopsService());
        $this->shopdata = $shopsService->getWxShopsSetting($companyId);

        if ($appId) {
            $this->wechatCard = new WechatCard($appId);
        }
    }

    public function pushWechatCard($filter)
    {
        if (isset($filter['card_id'])) {
            $discountCardRepository = app('registry')->getManager('default')->getRepository(DiscountCards::class);
            $dataInfo = $discountCardRepository->get(['card_id' => $filter['card_id']]);
            if (!$dataInfo) {
                return true;
            }
            $dataInfo = reset($dataInfo);
            $cardType = '';
            $baseInfo = [];
            $especial = [];
            $wechatRelCard = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
            $result = $wechatRelCard->getInfo(['card_id' => $filter['card_id']]);

            //检测是否记录了微信卡券card_id
            if ($result) {
                $cardId = $result['wechat_card_id'];
                $this->updateWechatCardData($dataInfo, $cardType, $baseInfo, $especial);
                return $this->wechatCard->updatePushWechat($cardId, $cardType, $baseInfo, $especial);
            } else {
                $this->wechatCardData($dataInfo, $cardType, $baseInfo, $especial, $advancedInfo);
                $cardId = $this->wechatCard->pushWechat($cardType, $baseInfo, $advancedInfo, $especial);
                if ($cardId) {
                    $wechatData['card_id'] = $dataInfo['card_id'];
                    $wechatData['company_id'] = $dataInfo['company_id'];
                    $wechatData['wechat_card_id'] = $cardId;
                    return $wechatRelCard->create($wechatData);
                }
            }
        }
    }

    public function deleteWechatCard($filter)
    {
        if (!$this->wechatCard) {
            throw new ResourceException(trans('KaquanBundle.missing_app_id'));
        }
        $wechatRelCard = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
        $result = $wechatRelCard->getInfo($filter);
        if ($result) {
            $wechatRelCard->deleteBy($filter);
            return $this->wechatCard->removeWechatCard($result['wechat_card_id']);
        }
        return true;
    }

    public function updateWechatCardStore($cardId, $type, $store)
    {
        if (!$this->wechatCard) {
            throw new ResourceException(trans('KaquanBundle.missing_app_id'));
        }
        $wechatRelCard = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
        $result = $wechatRelCard->getInfo(['card_id' => $cardId]);
        if ($result) {
            $wechatCardId = $result['wechat_card_id'];
            $result = $this->wechatCard->updateStock($type, $wechatCardId, $store);
        }
        return $result;
    }

    private function wechatCardData($cardData, &$cardType, &$baseInfo, &$especial, &$advancedInfo = [])
    {
        $baseInfo['logo_url'] = $this->shopdata['logo'];
        $baseInfo['brand_name'] = $this->shopdata['brand_name'];

        $baseInfo['title'] = $cardData['title'];
        $baseInfo['description'] = $cardData['description'];
        $baseInfo['code_type'] = 'CODE_TYPE_QRCODE';
        $baseInfo['sku']['quantity'] = $cardData['quantity'];

        if ($color = array_keys($this->colorData, $cardData['color'])) {
            $baseInfo['color'] = reset($color);
        } else {
            $baseInfo['color'] = 'Color081';
        }

        if (isset($cardData['use_scenes']) && $cardData['use_scenes']) {
            $baseInfo['notice'] = trans('KaquanBundle.use_within_time');
            if ($cardData['use_scenes'] == "ONLINE") {
                $baseInfo['notice'] = trans('KaquanBundle.select_coupon_when_order');
            } elseif ($cardData['use_scenes'] == "QUICK") {
                $baseInfo['notice'] = trans('KaquanBundle.select_coupon_when_pay');
            } elseif ($cardData['use_scenes'] == "SWEEP") {
                $baseInfo['notice'] = trans('KaquanBundle.show_qr_code_at_store');
            } elseif ($cardData['use_scenes'] == "SELF") {
                $baseInfo['notice'] = trans('KaquanBundle.show_coupon_at_store');
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
    }

    private function updateWechatCardData($cardData, &$cardType, &$baseInfo, &$especial, &$advancedInfo = [])
    {
        $cardType = $cardData['card_type'];
        $baseInfo['logo_url'] = $this->shopdata['logo'];
        $baseInfo['description'] = $cardData['description'];

        if ($color = array_keys($this->colorData, $cardData['color'])) {
            $baseInfo['color'] = reset($color);
        } else {
            $baseInfo['color'] = 'Color081';
        }

        if (isset($cardData['use_scenes']) && $cardData['use_scenes']) {
            $baseInfo['notice'] = trans('KaquanBundle.use_within_time');
            if ($cardData['use_scenes'] == "ONLINE") {
                $baseInfo['notice'] = trans('KaquanBundle.select_coupon_when_order');
            } elseif ($cardData['use_scenes'] == "QUICK") {
                $baseInfo['notice'] = trans('KaquanBundle.select_coupon_when_pay');
            } elseif ($cardData['use_scenes'] == "SWEEP") {
                $baseInfo['notice'] = trans('KaquanBundle.show_qr_code_at_store');
            } elseif ($cardData['use_scenes'] == "SELF") {
                $baseInfo['notice'] = trans('KaquanBundle.show_coupon_at_store');
            }
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
        if ($cardData['date_type'] == 'DATE_TYPE_FIX_TIME_RANGE') {
            $baseInfo['date_info'] = [
                'type' => "DATE_TYPE_FIX_TIME_RANGE",
                'begin_timestamp' => $cardData['begin_date'],
                'end_timestamp' => $cardData['end_date']
            ];
        }
    }

    public function getList($filter)
    {
        $wechatRelCard = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
        return $wechatRelCard->lists($filter);
    }

    public function get($filter)
    {
        $wechatRelCard = app('registry')->getManager('default')->getRepository(WechatRelCard::class);
        return $wechatRelCard->getInfo($filter);
    }
}
