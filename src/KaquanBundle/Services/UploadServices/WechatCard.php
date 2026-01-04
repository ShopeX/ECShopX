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

namespace KaquanBundle\Services\UploadServices;

use WechatBundle\Services\OpenPlatform;

class WechatCard
{
    public $card;
    public function __construct($appId)
    {
        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($appId);
        $this->card = $app->card;
    }

    /**
     * 将卡券推送至微信
     */
    public function pushWechat($cardType, $baseInfo, $advancedInfo, $especial)
    {
        $result = $this->card->create($cardType, $baseInfo, $especial, $advancedInfo);
        app('log')->debug('7-----'.var_export($result, 1));
        if ($result['errcode'] == 0) {
            return $result['card_id'];
        }
        return false;
    }

    public function updatePushWechat($cardId, $cardType, $baseInfo, $especial = [])
    {
        $result = $this->card->update($cardId, $cardType, $baseInfo, $especial);
        if ($result['errcode'] == 0) {
            return true;
        }
        return false;
    }

    /**
     * 删除微信卡券
     */
    public function removeWechatCard($cardId)
    {
        $result = $this->card->delete($cardId);
        if ($result['errcode'] > 0) {
            return false;
        }
        return true;
    }

    /**
     * 修改卡券库存
     */
    public function updateStock($type, $cardId, $store)
    {
        if ($type == "reduce") {
            $result = $this->card->reduceStock($cardId, $store);
        } elseif ($type == 'increase') {
            $result = $this->card->increaseStock($cardId, $store);
        }
        return $result;
    }


    /**
     * 拉去微信端所有卡券 id 列表
     */
    public function wechatCardList($offset = 0, $count = 50, $statusList = [])
    {
        if (!$statusList) {
            $statusList = ['CARD_STATUS_NOT_VERIFY','CARD_STATUS_VERIFY_OK','CARD_STATUS_DISPATCH','CARD_STATUS_VERIFY_OK'];
        }
        $result = $this->card->lists($offset, $count, $statusList);
        $data = [
            'list' => $result['card_id_list'],
            'total_num' => $result['total_num']
        ];
        return $data;
    }

    /**
     * 拉去微信端所有卡券的详情
     */
    public function wechatCardInfo($cardId)
    {
        $result = $this->card->getCard($cardId);
        return $result['card'];
    }

    /**
     * 核销微信优惠券
     */
    public function consumeCard($cardId, $code, $companyId)
    {
        $checkConsume = false;
        //核销之前验证 code
        $result = $this->card->getCode($code, $checkConsume, $cardId);
        if ($result['can_consume']) {
            $result = $this->card->consume($code, $cardId);
            if ($result['errcode'] == 0) {
                //记录卡券核销状态
                // $params['card_id'] = $cardId;
                // $params['code'] = $code;
                // $params['company_id'] = $companyId;
                // $WechatDiscountCardService = new WechatDiscountCardService();
                // return $WechatDiscountCardService->userConsumeCard($params);
            }
        }
        return false;
    }

    /**
     * 获取微信卡券的html (消息群发卡券)
     */
    public function getWechatCardHtml($cardId)
    {
        $result = $this->card->getHtml($cardId);
        return $result['content'];
    }

    /**
     * 获取微信的颜色数据
     */
    public function getWechatBackgroundColor()
    {
        $result = $this->card->getColors();
        return $result['colors'];
    }

    /**
     * 通过 ticket 获取二维码图片
     */
    public function getQRCodeByTicket($ticket)
    {
        $showQRcode = $this->card->showQRCode($ticket);
        return $showQRcode;
    }

    /**
     * 通过 ticket 获取二维码链接
     */
    public function getQRCodeUrlByTicket($ticket)
    {
        $urlQRcode = $this->card->getQRCodeUrl($ticket);
        return $urlQRcode;
    }

    /**
     * 小程序添加卡券到卡包需要的参数
     */
    public function addCardAttachExtension($cardId, $extension)
    {
        $data = $this->card->attachExtension($cardId, $extension);
        return $data;
    }

    /**
     * 卡券code解码
     */
    public function decrypt($encryptedCode)
    {
        $result = $this->card->decryptCode($encryptedCode);
        return $result['code'];
    }
}
