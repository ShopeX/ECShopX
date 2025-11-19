<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use Iidestiny\Flysystem\Oss\OssAdapter as Adapter;

class OssAdapter extends Adapter
{
    public function __construct($accessKeyId, $accessKeySecret, $endpoint, $bucket, $isCName = false, $prefix = '', $buckets = [], $domain = null, ...$params)
    {
        if ($isCName) {
            parent::__construct($accessKeyId, $accessKeySecret, $domain, $bucket, $isCName, $prefix, $buckets);
        } else {
            parent::__construct($accessKeyId, $accessKeySecret, $endpoint, $bucket, $isCName, $prefix, $buckets);
        }
    }
}
