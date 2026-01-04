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

namespace CompanysBundle\Services;

use AftersalesBundle\Services\AftersalesService;
use GoodsBundle\Services\ItemsService;
use Illuminate\Support\Facades\Log;
use OrdersBundle\Services\LitePosTenderService;
use OrdersBundle\Services\Orders\NormalOrderService;

class MailerService
{
    public $debug;
    public $smtp_port; //smtp_port 端口号
    public $email_from_name; //服务器主机名
    public $relay_host; //服务器主机地址
    public $user; //服务器用户名
    public $password; //服务器密码
    public $encryption; //服务器密码

    public function __construct($config = [])
    {
        $this->debug = false;
        if (!empty($config)) {
            // [2025-09-16 14:42:08] production.INFO: MailerService: 邮件配置:{
            //     "email_smtp_port":"465",
            //     "email_relay_host":"ssl:\/\/smtp.exmail.qq.com",
            //     "email_user":"test@shopex.cn",
            //     "email_password":"123456"
            // }  
            app('log')->info('MailerService: 邮件配置:'.json_encode($config));
            $this->smtp_port = $config['email_smtp_port'];
            $this->relay_host = $config['email_relay_host'];
            $this->user = $config['email_user'];
            $this->password = $config['email_password'];
            $this->email_from_name = $config['email_sender'];
        }else{
            app('log')->info('MailerService: 邮件配置:使用默认配置');
            $this->smtp_port = config('common.email_smtp_port');
            $this->relay_host = config('common.email_relay_host');
            $this->user = config('common.email_user');
            $this->password = config('common.email_password');
            $this->email_from_name = config('common.email_from_name'); //is used in HELO command
        }
        $this->encryption = config('common.email_encryption');; //is used in HELO command
    }

    //处理邮件模板和模板参数
    public function sendEmail($receiver_email, $email_type, $params)
    {
        //获取邮件模板
        $template = $this->getMailTemplate($email_type, $params);
        $sendRes = $this->doSend($receiver_email, $template['subject'], $template['body']);
        return $sendRes;
    }

    //发送邮件
    public function doSend($to, $subject = "", $body = "", $mailtype = "HTML", $cc = [], $additional_headers = "")
    {
        $transport = (new \Swift_SmtpTransport($this->relay_host, $this->smtp_port, $this->encryption))
            ->setUsername($this->user)->setPassword($this->password);

        $mailer = new \Swift_Mailer($transport);

        $message = (new \Swift_Message($subject))
            ->setFrom([$this->user => $this->email_from_name])
            ->setTo($to)
            ->setCc($cc)
            ->setBody($body, 'text/html');

        $result = $mailer->send($message);
        return $result;
    }

    //获取邮件模板
    public function getMailTemplate($emailType = '', $email_params = [])
    {
        switch ($emailType) {
            case 'find_password':
                $mailTemplate = [
                    'subject' => 'H&M找回密码邮箱验证码',
                    'body' => view('email_find_password', $email_params),
                ];
                break;

            case 'order_paid':
                $mailTemplate = [
                    'subject' => 'H&M订单确认',
                    'body' => view('email_order_paid', $email_params),
                ];
                break;

            case 'order_shipped':
                $mailTemplate = [
                    'subject' => 'H&M快递确认',
                    'body' => view('email_order_shipped', $email_params),
                ];
                break;

            case 'order_refund':
                $mailTemplate = [
                    'subject' => 'H&M退款确认',
                    'body' => view('email_order_refund', $email_params),
                ];
                break;
        }
        return $mailTemplate;
    }


}
