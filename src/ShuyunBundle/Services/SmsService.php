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

namespace ShuyunBundle\Services;

use Dingo\Api\Exception\ResourceException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

use ShuyunBundle\Services\Client\Request;

/**
 * 短信
 */
class SmsService
{
    /**
     * 短信类型
     */
    public const SMS_TYPE = [
        'fan-out' => 'MARKETING',// 营销类
        'notice' => 'NOTICE',// 通知类
    ];

    private $companyId;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    public function addSmsSign($content)
    {
        return true;
    }
    public function updateSmsSign($content, $oldContent)
    {
        return true;
    }

    public function connection()
    {
        return $this;
    }

    /**
     * 短信发送
     */
    public function send($contents, $sendType = 'notice', $remark = '')
    {
        app('log')->info('sendSms contents====>'.var_export($contents, true));
        if (!is_array($contents['phones'])) {
            $phones = [$contents['phones']];
        } else {
            $phones = $contents['phones'];
        }
        $params = [
            'smsType' => self::SMS_TYPE[$sendType] ?? self::SMS_TYPE['notice'],
            'phones' => $phones,
            'content' => $contents['content'],
            'remark' => $remark,
        ];
        $client = new Request($this->companyId);
        $url = '/captcha/1.0/sms/send';
        $resp = $client->json($url, $params);
        if ($resp->code != 0) {
            throw new AccessDeniedHttpException($resp->message);
        }
        return true;
    }

}
