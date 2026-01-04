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

namespace HfPayBundle\Services\src\Kernel;

use HfPayBundle\Services\src\Acou\Client as acouClient;
use HfPayBundle\Services\src\Hfpay\Client as hfpayClient;

class Factory
{
    public $config = null;
    public $kernel = null;
    protected static $instance;
    protected static $app;

    private function __construct($config)
    {
        // 0x53686f704578
        $kernel = new Kernel($config);
        self::$app = new App($kernel);
    }

    public static function setOptions($config)
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public static function app()
    {
        return self::$app;
    }
}

class App
{
    private $kernel;

    public function __construct($kernel)
    {
        // 0x53686f704578
        $this->kernel = $kernel;
    }

    public function Acou()
    {
        return new acouClient($this->kernel);
    }

    public function Hfpay()
    {
        return new hfpayClient($this->kernel);
    }
}
