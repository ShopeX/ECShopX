<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use Laravel\Lumen\Testing\TestCase;

abstract class TestBaseService extends TestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../../../bootstrap/app.php';
    }

    /**
     * 获取公司id
     * @return int
     */
    public function getCompanyId(): int
    {
        return (int)env("TEST_COMPANYS_ID", 1);
    }
}
