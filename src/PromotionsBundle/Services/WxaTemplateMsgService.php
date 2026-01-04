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

use PromotionsBundle\Entities\WxaNoticeTemplate;
use MembersBundle\Services\WechatUserService;
use WechatBundle\Services\OpenPlatform;
use PromotionsBundle\Services\WxaTemplateMsg\TemplateList;
use PromotionsBundle\Jobs\WxopenTemplateSend;
use WechatBundle\Services\WeappService;

/**
 * 微信小程序模版消息发送
 */
class WxaTemplateMsgService
{
    /**
     * undocumented function
     *
     * @return mixed
     */
    private function __getTemplateInfo($data, $isJobSend)
    {
        $rules = [
            'company_id' => ['required','企业id'],
            'scenes_name' => ['required','发送模版场景名称必填'],
            'appid' => ['required','发送消息的小程序appid必填'],
            'openid' => ['required','发送的openid'],
            'data' => ['required','发送的模版需要的数据'],
        ];

        $error = validator_params($data, $rules);
        if ($error) {
            //参数错误
            return false;
        }

        $templateList = new TemplateList();
        $templateInfo = $templateList->getTemplateInfo($data['company_id'], $data['scenes_name'], $data['appid']);
        if (!$templateInfo) {
            return false;
        }

        if (!$isJobSend && $templateInfo['send_time_desc'] && isset($templateInfo['send_time_desc']['value']) && $templateInfo['send_time_desc']['value']) {
            $job = (new WxopenTemplateSend($data))->delay($templateInfo['send_time_desc']['value'] * 60);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return false;
        }

        // 检查是否需要发送，例如：发送未支付提醒，那么需要判断发送的订单是否已支付
        if (!$templateList->checkSend($data)) {
            return false;
        }

        // $formId = $this->getFormId($data['appid'], $data['openid']);
        // if( !$formId ) {
        //     // 记录到发送失败日志中
        //     return false;
        // }

        // $templateInfo['formid'] = $formId;
        return $templateInfo;
    }

    /**
     * 获取发送的内容
     *
     * @param array $content 内容模版
     * @param array $data 发送内容的值
     */
    private function __getSendContent($content, $data)
    {
        $sendContent = [];
        foreach ($content as $row) {
            $key = $row['keyword'];
            if (isset($row['value'])) {
                if (isset($row['column'])) {
                    $patterns = '/{'.$row['column'].'}/';
                    $value = preg_replace($patterns, $data[$row['column']], $row['value']);
                } else {
                    $value = $row['value'];
                }
            } else {
                $value = $data[$row['column']] ?? '';
            }

            $sendContent[$key]['value'] = $this->__formatValue($key, $value);
        }
        return $sendContent;
    }

    private function __formatValue($key, $value)
    {
        if (strpos($key, 'thing') === 0) {
            return mb_strlen($value) > 20 ? mb_substr($value, 0, 17).'...' : $value;
        }

        return $value;
    }

    public function getOpenIdBy($userId, $wxaAppId)
    {
        $wechatUserService = new WechatUserService();
        $userInfo = $wechatUserService->getUserInfo(['authorizer_appid' => $wxaAppId, 'user_id' => $userId]);
        if (isset($userInfo['open_id'])) {
            return $userInfo['open_id'];
        } else {
            return 0;
        }
    }

    public function getWxaAppId($companyId, $templateName = 'yykweishop')
    {
        $weappService = new WeappService();
        return $weappService->getWxappidByTemplateName($companyId, $templateName);
    }

    /**
     * 发送通知
     * @param array $data 模版需要的数据
     */
    public function send($data, $isJobSend = false)
    {
        $templateInfo = $this->__getTemplateInfo($data, $isJobSend);
        if (!$templateInfo) {
            return true;
        }

        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($data['appid']);
        $sendData = [
            'touser' => $data['openid'],
            'template_id' => $templateInfo['template_id'],
            'data' => $this->__getSendContent($templateInfo['content'], $data['data']),
            'page' => isset($data['page_query_str']) ? $templateInfo['pages'].'?'.$data['page_query_str'] : $templateInfo['pages'],
        ];

        // if(isset($templateInfo['emp_has_is_keyword']) && $templateInfo['emp_has_is_keyword']) {
        // $sendData['emphasis_keyword'] = $templateInfo['emp_has_is_keyword'];
        // }

        try {
            $app->subscribe_message->send($sendData);
            // break;
        } catch (\Exception $e) {
            app('log')->debug('小程序服务通知发送失败：'.$e->getMessage());
            app('log')->debug('小程序服务通知发送失败参数：'. var_export($sendData, true));
        }

        return true;
    }

    /**
     * 微信支付统一下单，返回的prepayid缓存
     */
    // public function setPrepayIdCache($tradeId, $data)
    // {
    //     app('redis')->set('WxaPayprepayId:'.$tradeId, json_encode($data), 'EX', 7200);
    // }

    // public function setFormIdByWxpay($tradeId)
    // {
    // try {
    //     $data = app('redis')->get('WxaPayprepayId:'.$tradeId);
    //     if ($data) {
    //         $data = json_decode($data, true);
    //         $this->setFormId($data['wxa_appid'], $data['prepay_id'], 'wxpay', $data['openid']);
    //         app('redis')->del('WxaPayprepayId:'.$tradeId);
    //     }
    // } catch ( \Exception $e) {
    // }
    // }

    /**
     * 设置发送模版需要的formId
     * formid 只可以使用一次
     * prepay_id 支付场景下返回，可使用三次
     */
    // public function setFormId($wxaAppId, $formId, $formIdType='form', $openId=null)
    // {
    //     if(!$formId || !$openId) return false;

    //     if($formIdType == 'form') {
    //         $formId = $formId.'__0';
    //         app('redis')->lpush('WxaTemplateMsgWxaAppId:'.$wxaAppId.':'.$openId, $formId);
    //         app('redis')->set('WxaTemplateMsgFormId:'.$formId, $formId, 'EX', 604200);
    //     } else {
    //         $tmpId = $formId;
    //         for($i=0; $i<3; $i++) {
    //             $formId = $tmpId.'__'.$i;
    //             app('redis')->lpush('WxaTemplateMsgWxaAppId:'.$wxaAppId.':'.$openId, $formId);
    //             app('redis')->set('WxaTemplateMsgFormId:'.$formId, $formId, 'EX', 604200);
    //         }
    //     }
    //     return true;
    // }

    // public function getFormId($wxaAppId, $openId)
    // {
    //     while(true) {
    //         $formId = app('redis')->rpop('WxaTemplateMsgWxaAppId:'.$wxaAppId.':'.$openId);
    //         if(!$formId) break;

    //         if( app('redis')->del('WxaTemplateMsgFormId:'.$formId) ) {
    //             $formId = substr($formId, 0, -3);
    //             break;
    //         }
    //     }
    //     return $formId ? $formId : null;
    // }
    //
    /**
        * @brief 获取可被订阅的消息列表（指定小程序模板，指定消息模板类型）
        *
        * @param $filter
        * @param $sourceType
        *
        * @return
     */
    public function getValidTempLists($filter, $sourceType)
    {
        $sourceCategare = [
            'logistics_order' => ['paymentSucc', 'payOrdersRemind', 'orderDeliverySucc'], //订单创建时可被订阅的消息
            'ziti_order' => ['paymentSucc', 'payOrdersRemind', 'pickSuccess'], //订单创建时可被订阅的消息
            'after_refund' => ['aftersalesRefuse'],   //申请售后时可被订阅的消息
            'activity' => ['reservationRemind', 'registrationResultNotice', 'registrationActivityNotice'], //活动申请或预约时 可被订阅的消息
            'member' => ['memberCreateSucc'], //会员创建时可被订阅的消息
            'coupon' => ['userGetCardSucc'], //会员创建时可被订阅的消息
            'goods' => ['goodsArrivalNotice'], //库存充足时被订阅的消息
        ];

        $filter['scenes_name'] = $sourceCategare[$sourceType] ?? [];
        if (!$filter['scenes_name']) {
            return [];
        }
        $filter['is_open'] = 1;
        $wxaNoticeTemplateRepository = app('registry')->getManager('default')->getRepository(WxaNoticeTemplate::class);
        $cols = ['template_id', 'wxa_template_id', 'scenes_name'];
        $lists = $wxaNoticeTemplateRepository->getLists($filter, $cols);
        return $lists;
    }
}
