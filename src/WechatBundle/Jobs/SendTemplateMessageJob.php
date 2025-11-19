<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Jobs;

use EspierBundle\Jobs\Job;
use WechatBundle\Services\OpenPlatform;
use WechatBundle\Services\TemplateMessageService;

class SendTemplateMessageJob extends Job
{
    private $params;

    /**
     * 创建一个新的任务实例。
     *
     * @param $jobParams
     */
    public function __construct($jobParams)
    {
        $this->params = $jobParams;
    }

    /**
     * 运行任务。
     *
     * @return boolean
     */
    public function handle()
    {
        $touser = $this->params['touser'];
        $template_id = $this->params['template_id'];
        $msg_data = $this->params['msg_data'];
        $company_id = $this->params['company_id'];
            
        $openPlatform = new OpenPlatform();
        $woaAppId = $openPlatform->getWoaAppidByCompanyId($company_id);//公众号appid
        if (!$woaAppId) return true;
        // app('log')->debug('$woaAppId = ' . $woaAppId);
        
        try {
            $templateMessageService= new TemplateMessageService($woaAppId);
            $sendRes = $templateMessageService->send($touser, $template_id, $msg_data);
        } catch (\Exception $e) {
            app('log')->error('公众号模板消息发送失败 =>' . $e->getMessage());
        }
        // app('log')->debug('$sendRes' . var_export($sendRes, true));
        // app('log')->debug('微信公众号模板消息发送成功');
        return true;
    }
}
