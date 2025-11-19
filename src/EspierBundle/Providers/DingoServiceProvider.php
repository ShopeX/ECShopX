<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Providers;

use Illuminate\Support\ServiceProvider ;
use League\Fractal\Manager;
use EspierBundle\Fractal\Serializer\ResultArraySerializer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

use EspierBundle\Dingo\Provider\LumenServiceProvider;

class DingoServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerFractalManager();
    }

    public function registerFractalManager()
    {
        $this->app->singleton(Manager::class, function () {
            $manager = new Manager();
            return $manager->setSerializer(new ResultArraySerializer());
        });

        $this->app->register(LumenServiceProvider::class);
    }

}
