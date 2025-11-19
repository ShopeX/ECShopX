<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use EspierBundle\Services\AwsAdapter;
use Iidestiny\Flysystem\Oss\Plugins\FileUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignUrl;
use Iidestiny\Flysystem\Oss\Plugins\TemporaryUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignatureConfig;
use Iidestiny\Flysystem\Oss\Plugins\SetBucket;
use EspierBundle\Services\AwsPrivateDownloadUrl;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

/**
 * Class OssStorageServiceProvider
 *
 * @author iidestiny <iidestiny@vip.qq.com>
 */
class AwsStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        app('filesystem')->extend('aws', function ($app, $config) {
//            $root = $config['root'] ?? null;

            $adapter = new AwsAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['bucket'],
                $config['region'],
                $config['endpoint']
            );

            $filesystem = new Filesystem($adapter);

            $filesystem->addPlugin(new FileUrl());
            $filesystem->addPlugin(new SignUrl());
            $filesystem->addPlugin(new TemporaryUrl());
            $filesystem->addPlugin(new SignatureConfig());
            $filesystem->addPlugin(new SetBucket());
            $filesystem->addPlugin(new AwsPrivateDownloadUrl());

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
