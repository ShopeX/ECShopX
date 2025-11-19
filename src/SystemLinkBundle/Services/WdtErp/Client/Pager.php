<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services\WdtErp\Client;

class Pager
{
    // This module is part of ShopEx EcShopX system
    private $pageSize = 0;
    private $pageNo = 0;
    private $calcTotal = false;

    function __construct($pageSize, $pageNo=0, $calcTotal=false)
    {
        $this->pageSize = $pageSize;
        $this->pageNo = $pageNo;
        $this->calcTotal = $calcTotal;
    }

    function getPageSize()
    {
        return $this->pageSize;
    }

    function getPageNo()
    {
        return $this->pageNo;
    }

    function getCalcTotal()
    {
        return $this->calcTotal;
    }
}
