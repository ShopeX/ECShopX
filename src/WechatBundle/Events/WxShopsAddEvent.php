<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Events;

use App\Events\Event;

class WxShopsAddEvent extends Event
{
    public $audit_id;

    public $status;

    public $reason;

    public $is_upgrade;

    public $poiid;

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
        $this->is_upgrade = $receiveData['is_upgrade'];
        $this->poiid = $receiveData['poiid'];
    }
}
