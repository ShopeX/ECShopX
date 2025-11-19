<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use CompanysBundle\Services\MailerService;
use EspierBundle\Jobs\Job;
use CompanysBundle\Services\EmailService;

class SendInvoiceEmailJob extends Job
{
    protected $data;

    /**
     * 创建一个新的任务实例。
     *
     * @param array $data 包含邮件信息的数组，需要包含email和invoice_file_url
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
        app('log')->info('SendInvoiceEmailJob: 任务数据:'.json_encode($data));
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        // 获取邮件地址和发票文件URL
        $to = $this->data['email'] ?? '';
        $invoiceFileUrl = $this->data['invoice_file_url'] ?? '';
        app('log')->info('SendInvoiceEmailJob: 邮箱地址:'.$to.',发票文件URL:'.$invoiceFileUrl);
        if (empty($to) || empty($invoiceFileUrl)) {
            app('log')->error('SendInvoiceEmailJob: 邮箱地址或发票文件URL不能为空');
            return false;
        }

        // 邮件标题
        $subject = $this->data['subject'] ?? '您的电子发票已生成';

        // 邮件内容
        $body = $this->getEmailBody($invoiceFileUrl);

        // 发送邮件
        $config = app('redis')->connection('companys')->get('mailSetting:' . $this->data['company_id']);
        $config = json_decode($config, true);
        app('log')->info('SendInvoiceEmailJob: 邮件配置:'.json_encode($config));
        $configMail = [
            'email_smtp_port' => $config['EMAIL_SMTP_PORT'],
            'email_relay_host' => $config['EMAIL_RELAY_HOST'],
            'email_user' => $config['EMAIL_USER'],
            'email_password' => $config['EMAIL_PASSWORD'],
            'email_sender' => $config['EMAIL_SENDER'],
        ];
        $emailService = new MailerService($configMail);
        app('log')->info('SendInvoiceEmailJob: 发送邮件开始:to:'.$to.',subject:'.$subject.',body:'.$body);
        $result = $emailService->doSend($to, $subject, $body);
        app('log')->info('SendInvoiceEmailJob: 发送邮件结束:result:'.$result);
        if ($result) {
            app('log')->info("发票邮件已成功发送到<{$to}>");
        } else {
            app('log')->error("发送发票邮件到<{$to}>失败");
        }

        return $result;
    }

    /**
     * 获取邮件内容
     *
     * @param string $invoiceFileUrl 发票文件URL
     * @return string
     */
    private function getEmailBody($invoiceFileUrl)
    {
        $invoiceFileName = basename($invoiceFileUrl);

        $body = <<<EOF
<p>尊敬的客户:</p>
<p style="text-indent: 2em;">您的电子发票已生成，详情请查看附件。</p>
<p style="text-indent: 2em;">发票文件: <a href="{$invoiceFileUrl}">{$invoiceFileName}</a></p>
<p style="text-indent: 2em;">如有任何问题，请联系我们的客服。</p>
<p>此邮件为系统自动发送，请勿回复。</p>
EOF;

        return $body;
    }
}
