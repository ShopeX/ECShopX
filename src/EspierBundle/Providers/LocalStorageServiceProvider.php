<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use EspierBundle\Services\LocalAdapter;
use EspierBundle\Services\LocalPrivateDownloadUrl;
use Iidestiny\Flysystem\Oss\Plugins\FileUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignUrl;
use Iidestiny\Flysystem\Oss\Plugins\TemporaryUrl;
use Iidestiny\Flysystem\Oss\Plugins\SignatureConfig;
use Iidestiny\Flysystem\Oss\Plugins\SetBucket;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

/**
 * Class LocalStorageServiceProvider
 *
 */
class LocalStorageServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // ShopEx EcShopX Core Module
        app('filesystem')->extend('local', function ($app, $config) {
            $adapter = new LocalAdapter(
                $config['root'] ?? storage_path()
            );
            $filesystem = new Filesystem($adapter, $config);

            $filesystem->addPlugin(new FileUrl());
            $filesystem->addPlugin(new SignUrl());
            $filesystem->addPlugin(new TemporaryUrl());
            $filesystem->addPlugin(new SignatureConfig());
            $filesystem->addPlugin(new SetBucket());
            $filesystem->addPlugin(new LocalPrivateDownloadUrl());

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
