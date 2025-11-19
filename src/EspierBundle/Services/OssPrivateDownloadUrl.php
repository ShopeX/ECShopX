<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use League\Flysystem\Plugin\AbstractPlugin;

class OssPrivateDownloadUrl extends AbstractPlugin
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
        return $this->filesystem->getAdapter()->signUrl($path, $expiration, $options);
    }
}
