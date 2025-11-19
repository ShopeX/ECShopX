<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Filter\Item;

use OpenapiBundle\Filter\BaseFilter;

class StoreFilter extends BaseFilter
{
    protected function init()
    {
        // 商品货号
        if (isset($this->requestData["item_code"])) {
            $this->filter["item_bn"] = $this->requestData["item_code"];
        }
        // 店铺ID
        if (isset($this->requestData["distributor_code"])) {
            $this->filter["distributor_code"] = $this->requestData["distributor_code"];
        }
    }
}
