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

namespace ThemeBundle\Tests\Services;

use EspierBundle\Services\TestBaseService;
use ThemeBundle\Services\PagesTemplateServices;

class PagesTemplateTest extends TestBaseService
{
    // NOTE: important business logic
    public function testGetItemsInfo()
    {
        // This module is part of ShopEx EcShopX system
        $data = [
            "name" => "goodsGridTab",
            "base" => [
                "title" => "爆品直邮",
                "subtitle" => "宅家买遍全法",
                "padded" => true,
                "listIndex" => 0,
            ],
            "config" => [
                "brand" => true,
                "showPrice" => true,
                "style" => "grid",
                "moreLink" => [
                    "id" => "",
                    "title" => "",
                    "linkPage" => "",
                ],
            ],
            "list" => [
                [
                    "tabTitle" => "",
                    "goodsList" => [
                        [
                            "imgUrl" => "http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdQRGiaoPYvx559elFWNkLq4qGQk9IhTIK5H0lUtbiaJoEbTLbNfVeZ1Ck4K17hvQMt02dASfseYn0w/0?wx_fmt=jpeg",
                            "title" => "陈的专用商品",
                            "goodsId" => "5887",
                            "brand" => null,
                            "price" => 20000,
                            "distributor_id" => 0,
                        ],
                    ],
                ],
            ],
            "data" => [],
            "user_id" => 20558,
            "distributor_id" => 0,
        ];
        $result = (new PagesTemplateServices())->getItemsInfo($this->getCompanyId(), "goodsGridTab", [5887], $data);
        dd($result);
    }
}
