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

namespace BsPayBundle\Sdk\Config;

class MerConfig {

    /**
     * @var string 商户rsa私钥，用来进行对斗拱接口调用的请求数据加签
     */
    public $rsa_merch_private_key = "";

    /**
     * @var string 商户汇付rsa公钥，用来进行对斗拱接口应答数据的验签，以及部分请求数据非对称加密
     */
	public $rsa_huifu_public_key = '';
	
	public $product_id = '';
	
	public $sys_id = '';

    public $huifu_id = '';

    public $upper_huifu_id = '';
}