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
