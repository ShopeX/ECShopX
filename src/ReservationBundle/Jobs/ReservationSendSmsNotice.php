<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ReservationBundle\Jobs;

use EspierBundle\Jobs\Job;

//发送短信引入类
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use PromotionsBundle\Services\SmsManagerService;

class ReservationSendSmsNotice extends Job
{
    protected $data = [];

    protected $smsTemplateName = '';

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data, $smsTemplateName)
    {
        $this->data = $data;
        $this->smsTemplateName = $smsTemplateName;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $reservationRecordData = $this->data;

        $companyId = $reservationRecordData['company_id'];

        try {
            $shopId = $reservationRecordData['shop_id'];

            $shopsService = new ShopsService(new WxShopsService());
            $shopInfo = $shopsService->getShopInfoByShopId($shopId);

            $data = [
                'date' => date('Y-m-d H:i:s', $reservationRecordData['to_shop_time']),
                'shop_name' => $reservationRecordData['shop_name'],
                'rights_name' => $reservationRecordData['rights_name'],
                'shop_address' => $shopInfo['address'],
                'telephone' => $shopInfo['contract_phone']
            ];
            $mobile = $reservationRecordData['mobile'];
            $smsManagerService = new SmsManagerService($companyId);
            $smsManagerService->send($mobile, $companyId, $this->smsTemplateName, $data);
        } catch (\Exception $e) {
            app('log')->debug('短信发送失败: reservation_notice =>'.$e->getMessage());
        }
    }
}
