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

use Illuminate\Support\ServiceProvider;
use LaravelDoctrine\ORM\Configuration\Connections\ConnectionManager;
//use App\Console\Commands\GenerateEntitiesCommand;
use EspierBundle\Commands\GenerateEntitiesCommand;
use EspierBundle\Commands\GenerateRepositoriesCommand;


use LaravelDoctrine\ORM\DoctrineServiceProvider;
use LaravelDoctrine\Extensions\GedmoExtensionsServiceProvider;
use LaravelDoctrine\Migrations\MigrationsServiceProvider;
use Doctrine\Common\Annotations\AnnotationReader as AnnotationReader;
use EspierBundle\Commands\GenerateDataDictionaryCommand;
use EspierBundle\Commands\MakeAddressCommand;

use Illuminate\Support\Arr;

class LaravelDoctrineServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //Entities忽略SWG
        AnnotationReader::addGlobalIgnoredNamespace('SWG');

        $this->registerLaravelDoctrine();
        $this->registerConsoleCommands();
    }

    public function registerConsoleCommands()
    {
        $this->commands([
            GenerateEntitiesCommand::class,
            GenerateRepositoriesCommand::class,
            GenerateDataDictionaryCommand::class,
            MakeAddressCommand::class,
        ]);
    }

    public function registerLaravelDoctrine()
    {
        $this->app->register(DoctrineServiceProvider::class);
        $this->app->register(GedmoExtensionsServiceProvider::class);
        $this->app->register(MigrationsServiceProvider::class);

        $this->app->make(ConnectionManager::class)->extend('master_slave', function ($settings) {
            return [
                'driver' => 'pdo_mysql',
                'master' => $settings['master'],
                'slaves' => $settings['slaves'],
                'driverClass' => $settings['driverClass'],
                'wrapperClass' => $settings['wrapperClass'],
            ];
        });

        $this->app->make(ConnectionManager::class)->extend('mysql', function ($settings) {
            return [
                'driver' => 'pdo_mysql',
                'host' => Arr::get($settings, 'host'),
                'dbname' => Arr::get($settings, 'database'),
                'user' => Arr::get($settings, 'username'),
                'password' => Arr::get($settings, 'password'),
                'charset' => Arr::get($settings, 'charset'),
                'port' => Arr::get($settings, 'port'),
                'unix_socket' => Arr::get($settings, 'unix_socket'),
                'prefix' => Arr::get($settings, 'prefix'),
                'defaultTableOptions' => Arr::get($settings, 'defaultTableOptions', []),
                //'driverOptions'         => Arr::get($settings, 'driverOptions', []),
                'serverVersion' => Arr::get($settings, 'serverVersion'),
                'wrapperClass' => Arr::get($settings, 'wrapperClass', \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection::class),
                'driverClass' => Arr::get($settings, 'driverClass', \Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Driver\PDOMySql\Driver::class),
                'driverOptions' => [
                    'x_reconnect_attempts' => 3
                ]
            ];
        });
    }
}
