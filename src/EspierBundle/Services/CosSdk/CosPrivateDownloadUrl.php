<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\CosSdk;

use Carbon\Carbon;
use League\Flysystem\Plugin\AbstractPlugin;

class CosPrivateDownloadUrl extends AbstractPlugin
{
    /**
     * getTemporaryUrl.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'privateDownloadUrl';
    }

    /**
     * handle.
     *
     * @param       $path
     * @param       $expiration
     * @param array $options
     *
     * @return mixed
     */
    public function handle($path, $expiration = 3600, array $options = [])
    {
        $ts = time()+$expiration;
        $expiration = Carbon::createFromTimestamp($ts);
        return $this->filesystem->getAdapter()->getTemporaryUrl($path, $expiration, $options);
    }
}
