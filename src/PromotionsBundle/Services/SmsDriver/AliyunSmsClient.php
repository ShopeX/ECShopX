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

namespace PromotionsBundle\Services\SmsDriver;

use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\AddSmsSignRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\DeleteSmsSignRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\DeleteSmsTemplateRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\ModifySmsSignRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\ModifySmsTemplateRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySendDetailsRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsSignRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsTemplateRequest;
// 2024-12-04,新接口，原有接口将逐步下线
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\CreateSmsSignRequest;//申请短信签名（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\UpdateSmsSignRequest;//修改短信签名（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\GetSmsSignRequest;//查询签名详情（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsSignListRequest;//查询签名列表（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\CreateSmsTemplateRequest;//申请短信模板（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\UpdateSmsTemplateRequest;//修改短信模板（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\GetSmsTemplateRequest;//查询模板审核详情（新接口）
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\QuerySmsTemplateListRequest;//查询模板列表（新接口）

use AlibabaCloud\Tea\Model;
use AliyunsmsBundle\Services\SmsSignMapper;
use AliyunsmsBundle\Services\SceneService;
use AliyunsmsBundle\Services\SettingService;
use PromotionsBundle\Interfaces\SmsInterface;
use PromotionsBundle\Services\DefaultSmsTemplateService;
use PromotionsBundle\Services\SmsService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\AddSmsTemplateRequest;
/**
 * shopex prism sms
 */
class AliyunSmsClient {

    // use HasHttpRequest;

    const ENDPOINT_METHOD = 'SendSms';

    const ENDPOINT_VERSION = '2017-05-25';

    const ENDPOINT_FORMAT = 'JSON';

    const ENDPOINT_REGION_ID = 'cn-hangzhou';

    const ENDPOINT_SIGNATURE_METHOD = 'HMAC-SHA1';

    const ENDPOINT_SIGNATURE_VERSION = '1.0';

    public function __construct($companyId)
    {
        $this->config = (new SettingService())->getConfig(['company_id' => $companyId]);
        $this->companyId = $companyId;
    }

    public function createClient()
    {
        // ShopEx EcShopX Business Logic Layer
        $config = new Config([
            // 您的AccessKey ID
            "accessKeyId" => $this->config['accesskey_id'],
            // 您的AccessKey Secret
            "accessKeySecret" => $this->config['accesskey_secret']
        ]);
        // 访问的域名
        $config->endpoint = "dysmsapi.aliyuncs.com";
        return new Dysmsapi($config);
    }

    public function connection()
    {
        return $this;
    }

    public function send($contents)
    {
        // ShopEx EcShopX Business Logic Layer
        $client = $this->createClient();
        $sendParams = [
            'phoneNumbers' => $contents['phones'],
            'signName' => $contents['sign'],
            'templateCode' => $contents['template_code'],
        ];
        if (isset($contents['data']) && $contents['data']) {
            $sendParams['templateParam'] = json_encode($contents['data'], JSON_FORCE_OBJECT);
        }
        $sendSmsRequest = new SendSmsRequest($sendParams);
        app('log')->debug('短信参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->sendSms($sendSmsRequest)->toMap();
        app('log')->debug('短信结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '发送阿里云短信失败';
            app('log')->error('send sms Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }
    public function addSmsTemplate($params)
    {
        // 2024-12-04 使用新的阿里云接口
        return $this->createSmsTemplate($params);

        $client = $this->createClient();
        $sendParams = [
            "templateType" => $params['template_type'],
            "templateName" => $params['template_name'],
            "remark" => $params['remark'],
        ];
        $sendParams['templateContent'] = $this->templateConversion($params);
        $addSmsTemplateRequest = new AddSmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信模板参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->addSmsTemplate($addSmsTemplateRequest)->toMap();
        app('log')->debug('短信模板添加结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '添加阿里云短信模板失败';
            app('log')->error('add aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body']['TemplateCode'];
    }

    public function modifySmsTemplate($params)
    {
        // 2024-12-04 使用阿里云新接口
        return $this->updateSmsTemplate($params);

        $client = $this->createClient();
        $sendParams = [
            "templateType" => $params['template_type'],
            "templateName" => $params['template_name'],
            "remark" => $params['remark'],
            'templateCode' => $params['template_code'],
        ];
        $sendParams['templateContent'] = $this->templateConversion($params);
        $modifySmsTemplateRequest = new ModifySmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信模板参数: fan-out =>'.var_export($params,1));
        $result = $client->modifySmsTemplate($modifySmsTemplateRequest)->toMap();
        app('log')->debug('修改短信模板结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '修改阿里云短信模板失败';
            app('log')->error('modify aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body']['TemplateCode'];
    }

    public function deleteSmsTemplate($params)
    {
        $client = $this->createClient();
        $sendParams = [
            'templateCode' => $params['template_code'],
        ];
        $deleteSmsTemplateRequest = new DeleteSmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信模板参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->deleteSmsTemplate($deleteSmsTemplateRequest)->toMap();
        app('log')->debug('删除短信模板结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '删除阿里云短信模板失败';
            app('log')->error('delete aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body']['TemplateCode'];
    }

    public function querySmsTemplate($params)
    {
        // 2024-12-04 使用阿里云新接口
        return $this->getSmsTemplate($params);

        $client = $this->createClient();
        $sendParams = [
            'templateCode' => $params['template_code']
        ];
        $querySmsTemplateRequest = new QuerySmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信模板参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->querySmsTemplate($querySmsTemplateRequest)->toMap();
        app('log')->debug('查询短信模板结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信模板失败';
            app('log')->error('query aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    /**
     * add idiograph 添加短信签名
     *
     * @param content 签名内容
     */
    public function addSmsSign($params)
    {
        // 2024-12-04 使用新的阿里云接口
        $this->createSmsSign($params);
        return true;

        $client = $this->createClient();
        $sendParams = [
            'signName' => $params['sign_name'],
            'signSource' => $params['sign_source'],
            'remark' => $params['remark'],
        ];
        if($params['sign_file'] ?? 0) {
            $sendParams['signFileList'][] = new AddSmsSignRequest\signFileList($params['sign_file']);
        }
        if($params['delegate_file'] ?? 0) {
            $sendParams['signFileList'][] = new AddSmsSignRequest\signFileList($params['delegate_file']);
        }
        $addSmsSignRequest = new AddSmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信签名参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->addSmsSign($addSmsSignRequest)->toMap();
        app('log')->debug('添加短信签名结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '添加阿里云短信签名失败';
            app('log')->error('add aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return true;
    }

    public function modifySmsSign($params)
    {
        // 2024-12-04 使用新的阿里云接口
        $this->updateSmsSign($params);
        return true;
        $client = $this->createClient();
        $sendParams = [
            'signName' => $params['sign_name'],
            'signSource' => $params['sign_source'],
            'remark' => $params['remark'],
        ];
        if($params['sign_file'] ?? 0) {
            $sendParams['signFileList'][] = new ModifySmsSignRequest\signFileList($params['sign_file']);
        }
        if($params['delegate_file'] ?? 0) {
            $sendParams['signFileList'][] = new ModifySmsSignRequest\signFileList($params['delegate_file']);
        }
        $modifySmsSignRequest = new ModifySmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('修改短信签名参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->modifySmsSign($modifySmsSignRequest)->toMap();
        app('log')->debug('修改短信签名结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '修改阿里云短信签名失败';
            app('log')->error('modify aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return true;
    }

    public function deleteSmsSign($params)
    {
        $client = $this->createClient();
        $sendParams = ['signName' => $params['sign_name']];
        $deleteSmsSignRequest = new DeleteSmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('删除短信签名参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->deleteSmsSign($deleteSmsSignRequest)->toMap();
        app('log')->debug('删除短信签名结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '删除阿里云短信签名失败';
            app('log')->error('delete aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return true;
    }

    public function querySmsSign($params)
    {
        // 2024-12-04 使用新的阿里云接口
        return $this->getSmsSign($params);

        $client = $this->createClient();
        $sendParams = ['signName' => $params['sign_name']];
        $querySmsSignRequest = new QuerySmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('短信签名参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->querySmsSign($querySmsSignRequest)->toMap();
        app('log')->debug('查询短信签名结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信签名失败';
            app('log')->error('query aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    public function querySendDetail($params)
    {
        $client = $this->createClient();
        $sendParams = [
            "phoneNumber" => $params['mobile'],
            "bizId" => $params['biz_id'],
            "sendDate" => date("Ymd", $params['created']),
            "pageSize" => 1,
            "currentPage" => 1
        ];
        $querySendDetailsRequest = new QuerySendDetailsRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('查询发送详情参数: fan-out =>'.var_export($sendParams,1));
        $result = $client->querySendDetails($querySendDetailsRequest)->toMap();
        app('log')->debug('查询发送详情结果: fan-out =>'.var_export($result['body'],1));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询短信发送详情失败';
            app('log')->error('query aliyunSms sendDetail Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    public function templateCompilers($template, $data) {
        if(!$template) {
            throw new AccessDeniedHttpException("阿里云短信模板未添加");
        }
        return compact('template', 'data');
    }
    //商派短信模板转阿里云短信模板
    public function templateConversion($params) {
        $scene = (new SceneService())->getInfo(['id' => $params['scene_id']]);
        if(!$scene['variables']) {
            return ['content' => $params['template_content'], 'rule' => json_encode([])];
        }
        $variables = json_decode($scene['variables'],true);
        preg_match_all("/\\$\{(.+?)\}/", $params['template_content'],$result);
        $replaceParams = array_column($variables, NULL, 'var_title');
        $replacements = [];
        $patterns = [];
        $rule = [];
        foreach ($result[1] as $v) {
            if($replaceParams[$v] ?? 0) {
                $patterns[$v] = '/\\${'.$v.'}/';
                $replacements[$v] = '${'.$replaceParams[$v]['var_name'].'}';
                if (isset($replaceParams[$v]['rule'])) {
                    $rule[$replaceParams[$v]['var_name']] = $replaceParams[$v]['rule'];
                } else {
                    $rule[$replaceParams[$v]['var_name']] = $this->templateConversionRule($replaceParams[$v]['var_name']);
                }
            }
        }
        $content = preg_replace($patterns, $replacements, $params['template_content']);
        return ['content' => $content, 'rule' => json_encode($rule)];
    }

    private function templateConversionRule($varName)
    {
        $ruleList = [
            'code' => 'numberCaptcha',
            'pay_time' => 'time',
            'pay_money' => 'money',
            'order_id' => 'other_number2',
            'pickup_code' => 'pick_up_code',
            'item_name' => 'others',
            'end_time' => 'time',
            'activity_name' => 'others',
            'review_result' => 'others',
            'password' => 'other_number2',
            'phone' => 'phone_number2',
            'mer_name' => 'others',
            'step' => 'others',
            'dealer' => 'others',
        ];
        return $ruleList[$varName] ?? 'others';
    }


    /**
     * CreateSmsSign - 申请短信签名（新接口）
     *
     * @param array params 签名参数
     */
    public function createSmsSign($params)
    {
        $mapper = new SmsSignMapper();
        $client = $this->createClient();
        $sendParams = [
            'signName' => $params['sign_name'],
            'signSource' => $params['sign_source'],
            'remark' => $params['remark'],
            'thirdParty' => $mapper->normalizeThirdPartyBool($params['third_party']),
            'qualificationId' => $params['qualification_id'],
        ];
        $createSmsSignRequest = new CreateSmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::createSmsSign::短信签名参数: fan-out =>'.json_encode($sendParams));
        $result = $client->createSmsSign($createSmsSignRequest)->toMap();
        app('log')->debug('新::createSmsSign::添加短信签名结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '添加阿里云短信签名失败';
            app('log')->error('新::createSmsSign::add aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return true;
    }

    /**
     * UpdateSmsSign - 修改短信签名（新接口）
     * @param  array $params 签名参数
     */
    public function updateSmsSign($params)
    {
        $mapper = new SmsSignMapper();
        $client = $this->createClient();
        $sendParams = [
            'signName' => $params['sign_name'],
            'signSource' => $params['sign_source'],
            'remark' => $params['remark'],
            'thirdParty' => $mapper->normalizeThirdPartyBool($params['third_party']),
            'qualificationId' => $params['qualification_id'],
        ];
        $updateSmsSignRequest = new UpdateSmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::updateSmsSign::修改短信签名参数: fan-out =>'.json_encode($sendParams));
        $result = $client->updateSmsSign($updateSmsSignRequest)->toMap();
        app('log')->debug('新::updateSmsSign::修改短信签名结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '修改阿里云短信签名失败';
            app('log')->error('新::updateSmsSign::modify aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return true;
    }

    /**
     * QuerySmsSignList - 查询签名列表（新接口）
     *
     * @param int $pageIndex
     * @param int $pageSize
     * @return array
     */
    public function querySmsSignList($pageIndex = 1, $pageSize = 50)
    {
        if ($pageSize < 1 || $pageSize > 50) {
            throw new \InvalidArgumentException('PageSize must be between 1 and 50');
        }

        $client = $this->createClient();
        $sendParams = [
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
        ];
        $querySmsSignListRequest = new QuerySmsSignListRequest($sendParams);
        app('log')->debug('新::querySmsSignList::短信签名列表参数: fan-out =>'.json_encode($sendParams));
        $result = $client->querySmsSignList($querySmsSignListRequest)->toMap();
        app('log')->debug('新::querySmsSignList::查询短信签名列表结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信签名列表失败';
            app('log')->error('新::querySmsSignList::query aliyunSms sign list Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    /**
     * GetSmsSign - 查询签名详情（新接口）
     * @param  array $params 签名数据
     */
    public function getSmsSign($params)
    {
        $client = $this->createClient();
        $sendParams = ['signName' => $params['sign_name']];
        $getSmsSignRequest = new GetSmsSignRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::getSmsSign::短信签名参数: fan-out =>'.json_encode($sendParams));
        $result = $client->getSmsSign($getSmsSignRequest)->toMap();
        app('log')->debug('新::getSmsSign::查询短信签名结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信签名失败';
            app('log')->error('新::getSmsSign::query aliyunSms sign Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    /**
     * CreateSmsTemplate - 申请短信模板（新接口）
     * @param  array $params 模板参数
     */
    public function createSmsTemplate($params)
    {   
        $client = $this->createClient();
        $sendParams = [
            "templateType" => $params['template_type'],
            "templateName" => $params['template_name'],
            "remark" => $params['remark'],
            "relatedSignName" => $params['related_sign_name'],
        ];
        $template = $this->templateConversion($params);
        $sendParams['templateContent'] = $template['content'];
        $sendParams['templateRule'] = $template['rule'];
        $createSmsTemplateRequest = new CreateSmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::createSmsTemplate::短信模板参数: fan-out =>'.json_encode($sendParams,JSON_UNESCAPED_UNICODE));
        $result = $client->createSmsTemplate($createSmsTemplateRequest)->toMap();
        app('log')->debug('新::createSmsTemplate::短信模板添加结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '添加阿里云短信模板失败';
            app('log')->error('新::createSmsTemplate::add aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body']['TemplateCode'];
    }

    /**
     * UpdateSmsTemplate - 修改短信模板（新接口）
     * @param  array $params 模板参数
     */
    public function updateSmsTemplate($params)
    {
        $client = $this->createClient();
        $template = $this->templateConversion($params);
        $sendParams = [
            "templateType" => $params['template_type'],
            "templateName" => $params['template_name'],
            "remark" => $params['remark'],
            'templateCode' => $params['template_code'],
            "relatedSignName" => $params['related_sign_name'],
        ];
        $sendParams['templateContent'] = $template['content'];
        $sendParams['templateRule'] = $template['rule'];
        $updateSmsTemplateRequest = new UpdateSmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::updateSmsTemplate::短信模板参数: fan-out =>'.json_encode($params,JSON_UNESCAPED_UNICODE));
        $result = $client->updateSmsTemplate($updateSmsTemplateRequest)->toMap();
        app('log')->debug('新::updateSmsTemplate::修改短信模板结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '修改阿里云短信模板失败';
            app('log')->error('新::updateSmsTemplate::modify aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body']['TemplateCode'];
    }

    /**
     * GetSmsTemplate - 查询模板审核详情（新接口）
     * @param  array $params 模板参数
     */
    public function getSmsTemplate($params)
    {
        $client = $this->createClient();
        $sendParams = [
            'templateCode' => $params['template_code']
        ];
        $getSmsTemplateRequest = new GetSmsTemplateRequest($sendParams);
        // 复制代码运行请自行打印 API 的返回值
        app('log')->debug('新::getSmsTemplate::短信模板参数: fan-out =>'.json_encode($sendParams,JSON_UNESCAPED_UNICODE));
        $result = $client->getSmsTemplate($getSmsTemplateRequest)->toMap();
        app('log')->debug('新::getSmsTemplate::查询短信模板结果: fan-out =>'.json_encode($result['body'],JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信模板失败';
            app('log')->error('新::getSmsTemplate::query aliyunSms template Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }
        return $result['body'];
    }

    /**
     * QuerySmsTemplateList - 查询模板列表（新接口）
     *
     * @param int $pageIndex
     * @param int $pageSize
     * @return array
     */
    public function querySmsTemplateList($pageIndex = 1, $pageSize = 50)
    {
        if ($pageSize < 1 || $pageSize > 50) {
            throw new \InvalidArgumentException('PageSize must be between 1 and 50');
        }

        $client = $this->createClient();
        $sendParams = [
            'pageIndex' => $pageIndex,
            'pageSize' => $pageSize,
        ];
        $querySmsTemplateListRequest = new QuerySmsTemplateListRequest($sendParams);
        app('log')->debug('新::querySmsTemplateList::短信模板列表参数: fan-out =>'.json_encode($sendParams));
        $result = $client->querySmsTemplateList($querySmsTemplateListRequest)->toMap();
        app('log')->debug('新::querySmsTemplateList::查询短信模板列表结果: fan-out =>'.json_encode($result['body'], JSON_UNESCAPED_UNICODE));
        if ('OK' != $result['body']['Code']) {
            $errMsg = $result['body']['Message'] ?? '查询阿里云短信模板列表失败';
            app('log')->error('新::querySmsTemplateList::query aliyunSms template list Error :'. $errMsg);
            throw new AccessDeniedHttpException($errMsg);
        }

        return $result['body'];
    }

}
