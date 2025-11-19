<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\CosSdk;

use Carbon\Carbon;
use League\Flysystem\Plugin\AbstractPlugin;

class CosGetAuthorization extends AbstractPlugin
{
    /**
     * getTemporaryUrl.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getAuthorization';
    }

    /**
     * @param $method
     * @param $url
     * @return mixed
     */
    public function handle($method, $url)
    {
        return $this->filesystem->getAdapter()->getAuthorization($method, $url);
    }
}
