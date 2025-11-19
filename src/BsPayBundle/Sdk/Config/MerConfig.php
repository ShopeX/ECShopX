<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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