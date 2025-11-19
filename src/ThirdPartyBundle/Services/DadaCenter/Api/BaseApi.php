<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\DadaCenter\Api;

class BaseApi
{
    private $url;

    private $businessParams;

    public function __construct($url, $params)
    {
        $this->url = $url;
        $this->businessParams = $params;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getBusinessParams()
    {
        return $this->businessParams;
    }
}
