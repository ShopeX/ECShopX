<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Tests;

use EspierBundle\Services\UploadFileService;

class UploadFileTest extends \EspierBundle\Services\TestBaseService
{
    /**
     * @var UploadFileService
     */
    protected $service;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->service = new UploadFileService();
    }

    /**
     * 处理上传时的数据
     */
    public function testHandelImportData()
    {
        $data = [
            "id" => "557",
            "company_id" => "43",
            "file_name" => "更新店铺商品.xlsx",
            "file_type" => "update_distribution_item",
            "file_size" => 10654,
            "file_size_format" => "10.4KB",
            "handle_status" => "wait",
            "handle_line_num" => 0,
            "finish_time" => null,
            "finish_date" => null,
            "handle_message" => null,
            "created" => 1622458267,
            "created_date" => "2021-05-31 18:51:07",
            "updated" => 1622458268,
            "distributor_id" => 0,
        ];
        $resultByImportExcelData = [
            [
                0 => 96.0,
                1 => null,
                2 => "S6035FACF3F201",
                3 => "是",
                4 => "是",
                5 => null,
                6 => null,
            ],
            [
                0 => 96.0,
                1 => null,
                2 => "S6035FACF3D93B",
                3 => "是",
                4 => "是",
                5 => null,
                6 => null,
            ],
            [
                0 => 96.0,
                1 => null,
                2 => "S6035FACF384A2",
                3 => "否",
                4 => "否",
                5 => null,
                6 => null,
            ],
            [
                0 => 96.0,
                1 => null,
                2 => "S6035FACED4BDA",
                3 => "否",
                4 => "否",
                5 => null,
                6 => null,
            ],
        ];
        $column = [
            0 => "distribution_id",
            1 => "shop_code",
            2 => "item_bn",
            3 => "is_onsale",
            4 => "is_total_store",
            5 => "item_store",
            6 => "item_price",
        ];
        $this->service->handelImportData($data, $resultByImportExcelData, $column, true);
    }
}
