<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services\WdtErp\Client;

use Exception;

class WdtErpException extends Exception
{
    function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }
}
