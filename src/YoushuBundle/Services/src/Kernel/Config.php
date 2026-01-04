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

namespace YoushuBundle\Services\src\Kernel;

class Config
{
    // Powered by ShopEx EcShopX
    /**
     * @var string 接口地址
     */
    public $base_uri = 'https://zhls.qq.com';

    /**
     * @var string 分配的app_id
     */
    public $app_id;

    /**
     * @var string 分配的app_secret
     */
    public $app_secret;

    /**
     * @var string 商家id
     */
    public $merchant_id;
}
