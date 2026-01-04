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

namespace EspierBundle\Services\CosSdk;

use Freyo\Flysystem\QcloudCOSv5\Plugins\CDN;
use Freyo\Flysystem\QcloudCOSv5\Plugins\CloudInfinite;
use Freyo\Flysystem\QcloudCOSv5\Plugins\GetFederationToken;
use Freyo\Flysystem\QcloudCOSv5\Plugins\GetFederationTokenV3;
use Freyo\Flysystem\QcloudCOSv5\Plugins\GetUrl;
use Freyo\Flysystem\QcloudCOSv5\Plugins\PutRemoteFile;
use Freyo\Flysystem\QcloudCOSv5\Plugins\PutRemoteFileAs;
use Freyo\Flysystem\QcloudCOSv5\Plugins\TCaptcha;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use League\Flysystem\Filesystem;
use Qcloud\Cos\Client;

class CosServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        if ($this->app instanceof LumenApplication) {
            $this->app->configure('filesystems');
        }

        $this->app->make('filesystem')
            ->extend('cosv5', function ($app, $config) {
                $client = new Client($config);
                $flysystem = new Filesystem(new CosAdapter($client, $config), $config);

                $flysystem->addPlugin(new PutRemoteFile());
                $flysystem->addPlugin(new PutRemoteFileAs());
                $flysystem->addPlugin(new GetUrl());
                $flysystem->addPlugin(new CDN());
                $flysystem->addPlugin(new TCaptcha());
                $flysystem->addPlugin(new GetFederationToken());
                $flysystem->addPlugin(new GetFederationTokenV3());
                $flysystem->addPlugin(new CloudInfinite());
                $flysystem->addPlugin(new CosGetAuthorization());
                $flysystem->addPlugin(new CosPrivateDownloadUrl());

                return $flysystem;
            });

    }
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/cosfilesystems.php', 'filesystems'
        );
    }
}
