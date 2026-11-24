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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Distributor;

use Dingo\Api\Exception\ResourceException;
use OpenapiBundle\Http\Controllers\Controller;

/**
 * 店铺售后地址（尚未实现，显式拒绝避免静默空响应）
 * Class DistributorAftersaleAddressController
 * @package OpenapiBundle\Http\ThirdApi\V2\Action\Distributor
 */
class DistributorAftersaleAddressController extends Controller
{
    public function list()
    {
        throw new ResourceException('店铺售后地址接口未实现');
    }

    public function create()
    {
        throw new ResourceException('店铺售后地址接口未实现');
    }

    public function update()
    {
        throw new ResourceException('店铺售后地址接口未实现');
    }

    public function delete()
    {
        throw new ResourceException('店铺售后地址接口未实现');
    }
}
