<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests\Services\File;

use EspierBundle\Services\File\UpdateDistributionItemTemplate;

class UpdateDistributionItemTemplateTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * @var UpdateDistributionItemTemplate
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new UpdateDistributionItemTemplate();
    }

    public function testHandleRow()
    {
        try {
            $this->service->handleRow($this->getCompanyId(), [
                "distribution_id" => 135,
                "shop_code" => "",
                "item_bn" => "6969",
                "is_onsale" => 0,
                "is_total_store" => 0,
                "item_store" => 10,
                "item_price" => 5,
            ]);
        } catch (\Exception $exception) {
            dd($exception->getMessage());
        }
    }
}
