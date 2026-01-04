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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use PromotionsBundle\Entities\SmsIdiograph;
use PromotionsBundle\Entities\SmsTemplate;

/**
 * 短信服务
 */
class SmsService
{
    public $driver;
    public $fanOutStr;
    public $defaultSmsTemplateService;
    
    public function __construct($smsInterface = null)
    {
        $this->fanOutStr = trans('PromotionsBundle.fanout_reply_suffix');
        if ($smsInterface) {
            $this->driver = $smsInterface->connection();
        }
        // 数云模式
        if (config('common.oem-shuyun')) {
            $this->defaultSmsTemplateService = new DefaultSmsTemplateShuyunService();
        } else {
            $this->defaultSmsTemplateService = new DefaultSmsTemplateService();
        }
    }

    /**
     * 发送通知
     * @params $sendToPhones array 发送到的手机号
     * @params $companyId int 企业ID
     * @params $templateName string 模版名称
     * @params $data array 模版需要的数据
     */
    public function send($sendToPhones, $companyId, $templateName, $data)
    {
        // ShopEx EcShopX Service Component
        $smsSign = $this->getSmsSign($companyId);
        if ($smsSign) {
            $sign = '【'.$smsSign.'】';
        } else {
            throw new BadRequestHttpException(trans('PromotionsBundle.signature_not_set_cannot_send_sms'));
        }

        //获取到模版短信类型
        $templateData = $this->getTemplateInfo($companyId, $templateName);
        if (!$templateData) {
            $templateData = $this->defaultSmsTemplateService->getByName($templateName);
            if (!empty($templateData['is_open'])) {
                $templateData['is_open'] = 'true';
                $this->updateTemplate($companyId, $templateName, $templateData);
            } else {
                throw new BadRequestHttpException(trans('PromotionsBundle.sms_template_not_enabled'));
            }
        } else {
            if ($templateData['is_open'] != 'true') {
                throw new BadRequestHttpException(trans('PromotionsBundle.sms_template_not_enabled'));
            }
        }
        $sendType = $templateData['sms_type'];
        $contents = $this->templateCompilers($templateData['content'], $data);

        // 数云模式
        if (config('common.oem-shuyun')) {
            if ($sendType == 'fan-out') {
                $contents .= $this->fanOutStr;
            }
            //处理数据
            $contents = $sign.$contents;
            $smsContents = [
                'phones' => $sendToPhones,
                'content' => $contents,
            ];
            if (!is_array($templateData['send_time_desc'])) {
                $remark = $templateData['send_time_desc']->tmpl_title;
            } else {
                $remark = $templateData['send_time_desc']['tmpl_title'];
            }
            return $this->driver->send($smsContents, $sendType, $remark);
        } else {
            //处理数据
            $contents .= $sign;
            $smsContents = [
                ['phones' => $sendToPhones, 'content' => $contents]
            ];
            return $this->driver->send($smsContents, $sendType);
        }
    }

    /**
     * 发送指定内容短信
     */
    public function sendContent($companyId, $sendToPhones, $data, $sendType = 'notice')
    {
        $smsSign = $this->getSmsSign($companyId);
        if ($smsSign) {
            $sign = '【'.$smsSign.'】';
        } else {
            throw new BadRequestHttpException(trans('PromotionsBundle.signature_not_set_cannot_send_sms'));
        }
        if ($sendType == 'notice') {
            if (is_array($data)) {
                $contents = $this->templateCompilers($data['content'], $data['params'], $data['replaceParams']);
            } else {
                $contents = $data;
            }
        } elseif ($sendType == 'fan-out') {
            $contents = $data;
            // $contents .= " 退订回N";
            $contents .= $this->fanOutStr;
            // 非数云模式
            if (!config('common.oem-shuyun')) {
                $sendToPhones = is_array($sendToPhones) ? implode(',', $sendToPhones) : $sendToPhones;
            }
        }
        // 数云模式
        if (config('common.oem-shuyun')) {
            //处理数据
            $contents = $sign.$contents;
            $smsContents = [
                'phones' => $sendToPhones,
                'content' => $contents,
            ];
            return $this->driver->send($smsContents, $sendType, '会员群发短信');
        } else {
            //处理数据
            $contents .= $sign;
            $smsContents = [
                ['phones' => $sendToPhones, 'content' => $contents]
            ];
            return $this->driver->send($smsContents, $sendType);
        }
    }

    //编译模版
    private function templateCompilers($tmplContent, $data, $replaceParams = null)
    {
        if (!$replaceParams) {
            $replaceParams = $this->defaultSmsTemplateService->getReplaceParams();
        }
        $contents = $tmplContent;
        if ($replaceParams && $data) {
            foreach ($replaceParams as $paramsKey => $paramsValue) {
                if (isset($data[$paramsKey])) {
                    $replacements[$paramsKey] = $data[$paramsKey];
                } else {
                    $replacements[$paramsKey] = '';
                }
                $patterns[$paramsValue] = '/{{'.$paramsValue.'}}/';
            }
            $contents = preg_replace($patterns, $replacements, $tmplContent);
        }

        return $contents;
    }

    /*
     * 根据模版名称获取到已开启的模版的配置信息
     *
     * @params $companyId int 企业ID
     * @params $templateName string 模版名称
     */
    public function getOpenTemplateInfo($companyId, $templateName)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        if ($templateInfo && $templateInfo['is_open'] == 'true') {
            return $templateInfo;
        } else {
            return null;
        }
    }

    /**
     *  查询短信模板
     * @param $companyId
     * @param $templateName
     * @return null
     */
    public function getTemplateInfo($companyId, $templateName)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        if ($templateInfo) {
            return $templateInfo;
        } else {
            return null;
        }
    }

    /**
     * 获取短信模板详情
     * @param  string $companyId    
     * @param  string $templateName 模板名称
     */
    public function getTemplateDetail($companyId, $templateName)
    {
        $templateInfo = $this->getTemplateInfo($companyId, $templateName);
        if (!$templateInfo) {
            $templateInfo = $this->defaultSmsTemplateService->getByName($templateName);
        }
        return $templateInfo ?? [];
    }

    /*
     *  模版列表
     *
     * @params $companyId int 企业ID
     */
    public function listsTemplateByCompanyId($companyId)
    {
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateLists = $smsTemplateRepository->lists(['company_id' => $companyId]);
        foreach ($templateLists['list'] as $row) {
            $list[$row['tmpl_name']] = $row;
        }

        $defaultLists = $this->defaultSmsTemplateService->lists();
        $return = [];
        foreach ($defaultLists as $tmplName => $row) {
            if (isset($list[$tmplName])) {
                $return[$row['tmpl_type']][] = $list[$tmplName];
            } else {
                if (!empty($row['is_open'])) {
                    $row['is_open'] = 'true';
                }
                $return[$row['tmpl_type']][] = $row;
            }
        }

        return $return;
    }

    //启用模版
    public function updateTemplate($companyId, $templateName, $params)
    {
        if (isset($params['content']) && (mb_strlen($params['content']) < 1 || mb_strlen($params['content']) > 500) ) {
            throw new BadRequestHttpException(trans('PromotionsBundle.template_content_length_error'));
        }
        $smsTemplateRepository = app('registry')->getManager('default')->getRepository(SmsTemplate::class);
        $templateInfo = $smsTemplateRepository->get(['company_id' => $companyId, 'tmpl_name' => $templateName]);
        $defaultTemplateInfo = $this->defaultSmsTemplateService->getByName($templateName);
        if ($templateInfo) {
            // 数云模式
            if (config('common.oem-shuyun')) {
                $params['send_time_desc'] = $defaultTemplateInfo['send_time_desc'];
            }
            $smsTemplateRepository->updateTemplate($companyId, $templateName, $params);
        } else {
            $templateInfo = $defaultTemplateInfo;
            if (isset($params['content'])) {
                $templateInfo['content'] = $params['content'];
            }
            $templateInfo['company_id'] = $companyId;
            $templateInfo['is_open'] = $params['is_open'];
            $smsTemplateRepository->create($templateInfo);
        }
        return true;
    }

    /**
     * 保存短信签名
     */
    public function saveSmsSign($shopexUid, $companyId, $newContent)
    {
        if ($this->checkSign($newContent)) {
            $content = '【'.$newContent.'】';
        }

        $smsIdiograph = app('registry')->getManager('default')->getRepository(SmsIdiograph::class);
        $idiograph = $smsIdiograph->get(['shopex_uid' => $shopexUid, 'company_id' => $companyId]);
        if ($idiograph) {
            $oldContent = '【'.$idiograph->getIdiograph().'】';
            $this->driver->updateSmsSign($content, $oldContent);
            return $smsIdiograph->update($shopexUid, $companyId, $newContent);
        } else {
            $this->driver->addSmsSign($content);
            return $smsIdiograph->create($shopexUid, $companyId, $newContent);
        }
    }

    /**
     * 获取短信签名
     */
    public function getSmsSign($companyId)
    {
        $smsIdiograph = app('registry')->getManager('default')->getRepository(SmsIdiograph::class);
        $idiograph = $smsIdiograph->get(['company_id' => $companyId]);
        if ($idiograph) {
            return $idiograph->getIdiograph();
        } else {
            return null;
        }
    }

    private function checkSign($sign)
    {
        if (mb_strlen(urldecode(trim($sign)), 'utf-8') > 20 || mb_strlen(urldecode(trim($sign)), 'utf-8') < 3) {
            throw new BadRequestHttpException(trans('PromotionsBundle.signature_length_error'));
        }

        $arr = array('天猫','tmall','淘宝','taobao','1号店','易迅','京东','亚马逊','test','测试');
        for ($i = 0; $i < count($arr) ; $i++) {
            if (strstr(strtolower($sign), $arr[$i])) {
                throw new BadRequestHttpException(trans('PromotionsBundle.illegal_signature'));
            }
        }

        $arr = array(
            '【', '】',
        );
        if ((strstr($sign, $arr[0]) && (strstr($sign, $arr[1]))) != false) {
            throw new BadRequestHttpException(trans('PromotionsBundle.signature_contains_illegal_chars'));
        }

        return true;
    }

    /**
     * 发送测试模板短信
     * @param  [type] $companyId [description]
     * @param  [type] $params    [description]
     * @return [type]            [description]
     */
    public function sendTest($companyId, $params)
    {
        $smsSign = $this->getSmsSign($companyId);
        if ($smsSign) {
            $sign = '【'.$smsSign.'】';
        } else {
            throw new BadRequestHttpException(trans('PromotionsBundle.signature_not_set_cannot_send_sms'));
        }
        $sendToPhones = explode(',', $params['mobile']);
        if (empty($sendToPhones)) {
            throw new BadRequestHttpException(trans('PromotionsBundle.mobile_format_incorrect_cannot_send'));
        }
        //获取到模版短信类型
        $templateData = $this->getTemplateInfo($companyId, $params['tmpl_name']);
        if (!$templateData) {
            $templateData = $this->defaultSmsTemplateService->getByName($params['tmpl_name']);
        }
        $sendType = $templateData['sms_type'];
        $contents = $this->getTestContent($params['tmpl_name'], $params['content']);
        if ($sendType == 'fan-out') {
            $contents .= $this->fanOutStr;
        }
        //处理数据
        $contents = $sign.$contents;
        $smsContents = [
            'phones' => $sendToPhones,
            'content' => $contents,
        ];
        if (!is_array($templateData['send_time_desc'])) {
            $remark = $templateData['send_time_desc']->tmpl_title;
        } else {
            $remark = $templateData['send_time_desc']['tmpl_title'];
        }
        return $this->driver->send($smsContents, $sendType, $remark);
    }

    private function getTestContent($templateName, $content)
    {
        $data = [];
        switch ($templateName) {
            case 'trade_pay_success':
                $data = [
                    'pay_time' => date('Y-m-d H:i:s', time()),
                    'pay_money' => '99999.99',
                ];
                break;
            case 'registration_result_notice':
                $data = [
                    'activity_name' => '报名活动的名称',
                    'review_result' => '报名通过，允许参与',
                ];
                break;
            default:
                break;
        }
        return $this->templateCompilers($content, $data);

    }

    /**
     * Dynamically call the rightsService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver->$method(...$parameters);
    }
}
