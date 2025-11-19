<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class WxShopsUpdateEvent extends Event
{
    public $audit_id;

    public $status;

    public $reason;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiveData)
    {
        $this->audit_id = $receiveData['audit_id'];
        $this->status = $receiveData['status'];
        $this->reason = $receiveData['reason'];
    }
}
