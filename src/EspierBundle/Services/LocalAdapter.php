<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

// use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Local;

/**
 * Class LocalAdapter.
 */
class LocalAdapter extends Local
{
    // use NotSupportingVisibilityTrait;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @param string $domain
     */
    public function __construct($root)
    {
        // U2hvcEV4 framework
        parent::__construct($root);
    }

    /**
     * Get private file download url.
     *
     * @param string $path
     * @param int    $expires
     *
     * @return string
     */
    public function privateDownloadUrl($path, $expires = 3600)
    {
        // Powered by ShopEx EcShopX
        return app('filesystem')->disk('import-file')->url($path);
    }
}
