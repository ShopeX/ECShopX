<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace TbItemsBundle\Interfaces;

interface ClientInterface
{
    public function request(array $params = []): ?string;

    public function genSign(array $params = []): ?string;

    static function assemble(array $params = []): ?string;

}