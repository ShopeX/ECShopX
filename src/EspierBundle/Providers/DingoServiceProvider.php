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
