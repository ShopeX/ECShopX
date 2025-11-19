<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Services\EmailService;
use CompanysBundle\Events\CompanyCreateEvent;

class OnlineOpenSendEmailListener extends BaseListeners implements
    ShouldQueue
// class OnlineOpenSendEmailListener extends BaseListeners
{
    /**
     * Handle the event.
     *
     * @param  CompanyCreateEvent $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // 如果为数云模式，则不执行
        if (config('common.oem-shuyun')) {
            return false;
        }
        // if (!config('common.system_is_saas') || !config('common.system_open_online')) {
        if (!config('common.system_is_saas')) {
            return false;
        }

        //收件人邮箱
        $to = $event->entities['email'] ?? '';

        if (!$to) {
            return false;
        }

        //标题
        $subject = '商派云店系统成功开通通知';

        $mobile = $event->entities['mobile'];
        $activeAt = date('Y-m-d', $event->entities['active_at']);
        $expiredAt = date('Y-m-d', $event->entities['expired_at']);
        $shopAdminUrl = config('common.shop_admin_url');

        //邮件内容
        $body = <<<EOF
<p>尊敬的用户:</p>
<p style="text-indent: 2em;">您已于{$activeAt}成功开通商派云店系统，有效期至{$expiredAt};</p>
<p style="text-indent: 2em;">请通过：<a href="{$shopAdminUrl}">{$shopAdminUrl}</a>进入管理端;</p>
<p style="text-indent: 2em;">账户：{$mobile};</p>
<p style="text-indent: 2em;">密码：注册所用密码;</p>
EOF;

        $emailService = new EmailService();
        $emailService->sendmail($to, $subject, $body);
    }
}
