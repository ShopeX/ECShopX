<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class MembercardCheckEvent extends Event
{
    public $cardId;

    public $checkStatus;

    public $refuseReason;

    public $companyId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->cardId = $receiveData['cardId'];
        $this->companyId = $receiveData['company_id'];
        $this->checkStatus = $receiveData['checkStatus'];
        $this->refuseReason = $receiveData['refuseReason'];
    }
}
