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

namespace YoushuBundle\Services\src\Kernel;

use YoushuBundle\Services\src\Activity\Client as activityClient;
use YoushuBundle\Services\src\Analysis\Client as analysisClient;
use YoushuBundle\Services\src\DataSource\Client as dataSourceClient;
use YoushuBundle\Services\src\Items\Client as itemsClient;
use YoushuBundle\Services\src\Member\Client as memberClient;
use YoushuBundle\Services\src\Order\Client as orderClient;

class Factory
{
    private static $companyId;
    private static $instance;
    private $app;

    private function __construct($config)
    {
        // FIXME: check performance
        $kernel = new Kernel($config);
        $this->app = new App($kernel);
    }

    public static function setOptions($config, $companyId)
    {
        if (!isset(self::$instance[$companyId]) || !(self::$instance[$companyId] instanceof self)) {
            self::$instance[$companyId] = new self($config);
        }
        self::$companyId = $companyId;
        return self::$instance[$companyId];
    }

    public static function app()
    {
        return self::$instance[self::$companyId]->app;
    }
}

class App
{
    private $kernel;

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    public function dataSource()
    {
        return new dataSourceClient($this->kernel);
    }

    public function activity()
    {
        // FIXME: check performance
        return new activityClient($this->kernel);
    }

    public function items()
    {
        return new itemsClient($this->kernel);
    }

    public function order()
    {
        return new orderClient($this->kernel);
    }

    public function member()
    {
        return new memberClient($this->kernel);
    }

    public function analysis()
    {
        return new analysisClient($this->kernel);
    }
}
