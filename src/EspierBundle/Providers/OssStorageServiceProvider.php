<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use EspierBundle\Services\OssAdapter;
use Iidestiny\Flysystem\Oss\Plugins\FileUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignUrl;
use Iidestiny\Flysystem\Oss\Plugins\TemporaryUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignatureConfig;
use Iidestiny\Flysystem\Oss\Plugins\SetBucket;
use EspierBundle\Services\OssPrivateDownloadUrl;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

/**
 * Class OssStorageServiceProvider
 *
 * @author iidestiny <iidestiny@vip.qq.com>
 */
class OssStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // NOTE: important business logic
        app('filesystem')->extend('oss', function ($app, $config) {
            $root = $config['root'] ?? null;
            $domain = isset($config['domain']) ? $config['domain'] : [];
            $buckets = isset($config['buckets']) ? $config['buckets'] : [];
            $adapter = new OssAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['endpoint'],
                $config['bucket'],
                $config['isCName'],
                $root,
                $buckets,
                $domain
            );

            $filesystem = new Filesystem($adapter);

            $filesystem->addPlugin(new FileUrl());
            $filesystem->addPlugin(new SignUrl());
            $filesystem->addPlugin(new TemporaryUrl());
            $filesystem->addPlugin(new SignatureConfig());
            $filesystem->addPlugin(new SetBucket());
            $filesystem->addPlugin(new OssPrivateDownloadUrl());

            return $filesystem;
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
