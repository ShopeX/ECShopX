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

use PromotionsBundle\Services\AliTemplateMsg\TemplateList;
use AliBundle\Factory\MiniAppFactory;

/**
 * 微信小程序模版消息发送
 */
class AliTemplateMsgService
{
    /**
     * undocumented function
     *
     * @return mixed
     */
    private function __getTemplateInfo($data, $isJobSend)
    {
        $rules = [
            'company_id' => ['required', trans('PromotionsBundle.company_id_validation')],
            'scenes_name' => ['required', trans('PromotionsBundle.template_scene_name_required')],
            'to_user_id' => ['required', trans('PromotionsBundle.to_user_id_validation')],
            'data' => ['required', trans('PromotionsBundle.template_data_validation')],
        ];

        $error = validator_params($data, $rules);
        if ($error) {
            //参数错误
            return false;
        }

        $templateList = new TemplateList();
        $templateInfo = $templateList->getTemplateInfo($data['company_id'], $data['scenes_name']);
        if (!$templateInfo) {
            return false;
        }

        if (!$isJobSend && $templateInfo['send_time_desc'] && isset($templateInfo['send_time_desc']['value']) && $templateInfo['send_time_desc']['value']) {
            $job = (new AliTemplateMsgSend($data))->delay($templateInfo['send_time_desc']['value'] * 60);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
            return false;
        }

        // 检查是否需要发送，例如：发送未支付提醒，那么需要判断发送的订单是否已支付
        if (!$templateList->checkSend($data)) {
            return false;
        }

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
        $i = 1;
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

            $sendContent['keyword'.($i++)]['value'] = $this->__formatValue($key, $value);
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

        $app = (new MiniAppFactory())->getApp($data['company_id']);

        $toUserId = $data['to_user_id'];
        $userTemplateId = $templateInfo['template_id'];
        $page = isset($data['page_query_str']) ? $templateInfo['pages'].'?'.$data['page_query_str'] : $templateInfo['pages'];
        $data = $this->__getSendContent($templateInfo['content'], $data['data']);

        try {
            $app->getFactory()->marketing()->templateMessage()->send($toUserId, null, $userTemplateId, $page, $data);
        } catch (\Exception $e) {
            app('log')->debug('小程序服务通知发送失败：'.$e->getMessage());
        }

        return true;
    }

    /**
        * @brief 获取可被订阅的消息列表（指定小程序模板，指定消息模板类型）
        *
        * @param $filter
        * @param $sourceType
        *
        * @return
     */
    public function getValidTempLists($companyId, $sourceType)
    {
        $sourceCategare = [
            'logistics_order' => ['paymentSucc', 'payOrdersRemind', 'orderDeliverySucc'], //订单创建时可被订阅的消息
            'ziti_order' => ['paymentSucc', 'payOrdersRemind'], //订单创建时可被订阅的消息
            'after_refund' => ['aftersalesRefuse'],   //申请售后时可被订阅的消息
            'activity' => ['registrationResultNotice'], //活动申请或预约时 可被订阅的消息
            'member' => ['memberCreateSucc'], //会员创建时可被订阅的消息
            'coupon' => ['userGetCardSucc'], //会员创建时可被订阅的消息
            'goods' => ['goodsArrivalNotice'], //库存充足时被订阅的消息
        ];

        if (!$sourceCategare[$sourceType]) {
            return [];
        }

        $lists = [];
        $templateList = new TemplateList();
        foreach ($sourceCategare[$sourceType] as $scenesName) {
            $template = $templateList->getTemplateInfo($companyId, $scenesName);
            if ($template) {
                $lists[] = [
                    'template_id' => $template['template_id'],
                    'scenes_name' => $scenesName,
                ];
            }
        }
        
        return $lists;
    }
}
