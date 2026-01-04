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

namespace AliyunsmsBundle\Services;
use AliyunsmsBundle\Jobs\AddSmsBatchRecord;
use AliyunsmsBundle\Jobs\QuerySendDetail;
use AliyunsmsBundle\Jobs\QuerySmsTemplate;
use Dingo\Api\Exception\ResourceException;
use PromotionsBundle\Services\SmsDriver\AliyunSmsClient;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class SmsService
{
    public $client;
    public function __construct($companyId = null)
    {
        if($companyId) {
            $this->client = new AliyunSmsClient($companyId);
        }
    }

    /**
     * 发送通知
     * @params $sendToPhones array 发送到的手机号
     * @params $companyId int 企业ID
     * @params $sceneTitle string 场景名称
     * @params $data array 模版需要的数据
     */
    public function send($sendToPhones, $companyId, $sceneTitle, $data)
    {
        $sceneService = new SceneService();
        $scene = $sceneService->getInfo(['scene_title' => $sceneTitle, 'company_id' => $companyId]);
        if(!$scene) {
            throw new BadRequestHttpException('短信场景无效,不能发送');
        }
        $sceneItem = (new SceneItemService())->getInfo(['scene_id' => $scene['id'], 'status' => 1]);
        if(!$sceneItem) {
            throw new BadRequestHttpException('模板未启用,不能发送');
        }
        $templateData = (new TemplateService())->getInfoById($sceneItem['template_id']);
        if(!$templateData) {
            throw new BadRequestHttpException('模板无效,不能发送');
        }
        $smsSign = (new SignService())->getInfo(['id' => $sceneItem['sign_id'], 'status' => 1]);
        if (!$smsSign) {
            throw new BadRequestHttpException('签名无效,不能发送');
        }
        $data = $this->filterTemplateParam($templateData['template_content'], $data, $scene['variables']);
        $smsContents = [
            'phones' => $sendToPhones,
            'data' => $data,
            'sign' => $smsSign['sign_name'],
            'template_code' => $templateData['template_code']
        ];
        $result = $this->client->send($smsContents);
        $content = $this->templateCompilers($templateData['template_content'], $smsContents['data'], $scene['variables']);
        //发送记录
        $recordParams = [
            'company_id' => $companyId,
            'mobile' => $smsContents['phones'],
            'scene_id' => $scene['id'],
            'template_code' => $templateData['template_code'],
            'template_type' => $scene['template_type'],
            'status' => 1,
            'sms_content' =>'【'. $smsSign['sign_name']. '】'.$content,
            'biz_id' => $result['BizId'] //短信发送回执ID,作为短信结果查询参数
        ];
        return (new RecordService())->addRecord($recordParams);
    }

    public function runSmsTask($sendToPhones, $task)
    {
        $templateData = (new TemplateService())->getInfoById($task['template_id']);
        if(!$templateData) {
            throw new BadRequestHttpException('模板无效,不能发送');
        }
        $smsSign = (new SignService())->getInfo(['id' => $task['sign_id'], 'status' => 1]);
        if (!$smsSign) {
            throw new BadRequestHttpException('签名无效,不能发送短');
        }
        $smsContents = [
            'phones' => implode(',', $sendToPhones),
            'sign' => $smsSign['sign_name'],
            'template_code' => $templateData['template_code']
        ];
        $result = $this->client->send($smsContents);
        $taskScene = (new SceneService())->getInfo(['template_type' => 2, 'company_id' => $task['company_id']]); //当前账号下的推广场景
        //发送记录
        $recordParams = [
            'company_id' => $task['company_id'],
            'task_id' => $task['id'],
            'mobile' => $sendToPhones,
            'scene_id' => $taskScene['id'],
            'template_code' => $templateData['template_code'],
            'template_type' => $templateData['template_type'],
            'sms_content' =>'【'. $smsSign['sign_name']. '】'.$templateData['template_content'],
            'status' => 1,
            'biz_id' => $result['BizId'] //短信发送回执ID,作为短信结果查询参数
        ];
        //批量添加发送记录
//        (new AddSmsBatchRecord($recordParams))->handle();
        $queue = (new AddSmsBatchRecord($recordParams))->onQueue('sms');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        return true;
    }

    public function querySendDetail()
    {
        //获取审核中的列表, 调阿里云接口查询状态
        $service = new RecordService();
        $list = $service->lists(['status' => 1]);
        foreach ($list['list'] as $row) {
//            (new QuerySendDetail($row))->handle();
            $queue = (new QuerySendDetail($row))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);
        }
    }

    /*
     * 根据模版名称获取到已开启的模版的配置信息 兼容shopex sms
     *
     * @params $companyId int 企业ID
     * @params $sceneTitle string 场景名称
     */
    public function getOpenTemplateInfo($companyId, $sceneTitle)
    {
        // ShopEx EcShopX Business Logic Layer
        $sceneService = new SceneService();
        $sceneInfo = $sceneService->getInfo(['status' => 'enabled', 'company_id' => $companyId, 'scene_title' => $sceneTitle]);
        if ($sceneInfo) {
            return $sceneInfo;
        } else {
            return null;
        }
    }

    //编译模版
    private function templateCompilers($tmplContent, $data, $replaceParams = null)
    {
        if(!$replaceParams) return $tmplContent;
        $replaceParams = json_decode($replaceParams, true);
        preg_match_all("/\\$\{(.+?)\}/", $tmplContent, $result);
        $replaceParams = array_column($replaceParams, NULL, 'var_title');
        $replacements = [];
        $patterns = [];
        foreach ($result[1] as $v) {
            if($replaceParams[$v] ?? 0) {
                $patterns[$v] = '/\\${'.$v.'}/';
                $replacements[$v] = $data[$replaceParams[$v]['var_name']] ?? '';
            } else {
                $replacements[$v]  = '';
            }
        }
        $content = '';
        $content = preg_replace($patterns, $replacements, $tmplContent);
        return $content;
    }

    private function filterTemplateParam($tmplContent, $data, $replaceParams = null)
    {
        if(!$replaceParams) return [];
        $replaceParams = json_decode($replaceParams, true);
        preg_match_all("/\\$\{(.+?)\}/", $tmplContent, $result);
        $replaceParams = array_column($replaceParams, 'var_name', 'var_title');
        $replacements = [];
        foreach ($result[1] as $v) {
            if(isset($replaceParams[$v])) {
                $replacements[] = $replaceParams[$v];
            }
        }
        foreach ($data as $k => $v) {
            if (!in_array($k, $replacements)) {
                unset($data[$k]);
            }
        }

        return $data;
    }
}
