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

namespace WechatBundle\Services;

use Dingo\Api\Exception\StoreResourceFailedException;

/**
 * 微信公众号模板消息
 * https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html
 * 
 * https://easywechat.com/5.x/official-account/template_message.html
 */
class TemplateMessageService
{
    /**
     * 模板实例
     */
    public $template_message;

    public function __construct($authorizerAppId)
    {
        $openPlatform = new OpenPlatform();
        if (!$authorizerAppId) {
            throw new StoreResourceFailedException('当前账号未绑定公众号，请先绑定公众号');
        }
        $app = $openPlatform->getAuthorizerApplication($authorizerAppId);
        $this->template_message = $app->template_message;
    }

    /**
     * 返回所有支持的行业列表，用于做下拉选择行业可视化更新
     *
     * @return array [
     *  'primary_industry' => [
     *      'first_class' => 'IT科技',
     *      'second_class' => '互联网|电子商务'
     *  ],
     *  'secondary_industry' => [
     *      'first_class' => '',
     *      'second_class' => ''
     *  ],
     * ]
     */
    public function getIndustry()
    {
        $data = $this->template_message->getIndustry();
        return $data->all();
    }

    /*
     * 设置所属行业
     * 设置行业可在微信公众平台后台完成，每月可修改行业1次，帐号仅可使用所属行业中相关的模板
     *
     * 查询行业代码
     * https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277
     */
    public function setIndustry($primaryIndustryId, $secondaryIndustryId)
    {
        // ShopEx EcShopX Core Module
        return $this->template_message->setIndustry($primaryIndustryId, $secondaryIndustryId);
    }

    /**
     * 添加模板
     */
    public function addTemplate($shortId)
    {
        $data = $this->template_message->addTemplate($shortId);
        $templateId = $data->template_id;
        return $templateId;
    }

    /**
     * 删除模板
     *
     * @param string $templateId
     */
    public function deletePrivateTemplate($templateId)
    {
        $this->template_message->deletePrivateTemplate($templateId);
        return true;
    }

    /**
     * 获取所有模板列表
     *
     * array (
     *     'template_list' =>
     *     array (
     *       0 =>
     *       array (
     *         'template_id' => '88F1-o7yJN6e299PttHrwJvkPqvaBR314Wb5vqbgFDs',
     *         'title' => '购买成功通知',
     *         'primary_industry' => 'IT科技',
     *         'deputy_industry' => '互联网|电子商务',
     *         'content' => '您好，您已购买成功。
     *
     *   商品信息：{{name.DATA}}
     *   {{remark.DATA}}',
     *         'example' => '您好，您已购买成功。
     *
     *   商品信息：微信影城影票
     *   有效期：永久有效
     *   券号为QQ5024813399，密码为123456890',
     *       ),
     *     ),
     *   )
     */
    public function getPrivateTemplates()
    {
        $data = $this->template_message->getPrivateTemplates();
        $list = $data->all();
        if ($list['template_list']) {
            return $list['template_list'];
        } else {
            return [];
        }
    }

    /**
     * 发送模板消息
     * 
     * {
            "touser":"OPENID",
            "template_id":"ngqIpbwh8bUfcSsECmogfXcV14J0tQlEpBO27izEYtY",
            "url":"http://weixin.qq.com/download",  
            "miniprogram":{
                "appid":"xiaochengxuappid12345",
                "pagepath":"index?foo=bar"
            },
            "client_msg_id":"MSG_000001",
            "data":{        
                "keyword1":{
                     "value":"巧克力"
                 },
                "keyword2": {
                    "value":"39.8元"
                },
                "keyword3": {
                    "value":"2014年9月22日"
                }
            }
        }
     */
    public function send($touser, $template_id, $msg_data)
    {
        $sendRes = $this->template_message->send([
            'touser' => $touser,
            'template_id' => $template_id,
            // 'url' => 'https://easywechat.com',
            // 'miniprogram' => [
            //     'appid' => 'xxxxxxx',
            //     'pagepath' => 'pages/xxx',
            // ],
            'data' => $msg_data,
        ]);
        return $sendRes;
    }
}
