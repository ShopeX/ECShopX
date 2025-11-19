<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Interfaces;

use Swoole\Http\Request;

interface WebSocketInterface
{
    public const KEYPREFIX = 'wxappwebsocket';

    public function checkAuth(Request $request);

    public function join(Request $request);

    public function close($client);

    public function sendMessage($message);
}
