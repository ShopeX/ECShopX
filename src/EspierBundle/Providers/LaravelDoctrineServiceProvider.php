<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
