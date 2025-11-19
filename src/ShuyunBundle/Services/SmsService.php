<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
