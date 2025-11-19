<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Interfaces;

interface ClientInterface
{
    public function request(array $params = []): ?string;

    public function genSign(): ?string;

}