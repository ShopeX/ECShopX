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
