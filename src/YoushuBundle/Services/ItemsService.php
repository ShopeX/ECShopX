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

namespace YoushuBundle\Services;

use GoodsBundle\Services\ItemsService as NormalItemService;

class ItemsService
{
    /**
     * @param array $params
     * @return array
     *
     * 添加/更新商品 SKU
     */
    public function getData($params)
    {
        $item_id = $params['object_id'];
        $items_service = new NormalItemService();
        $item_sku_info = $items_service->getItemsSkuDetail($item_id);
        if (empty($item_sku_info)) {
            return [];
        }

        $external_sku_id = $item_sku_info['item_id']; //商品货号
        $external_spu_id = $item_sku_info['goods_id']; //
        $sku_barcode = $item_sku_info['barcode'];//商品条码
        $is_available = 'onsale' == $item_sku_info['approve_status'] ? true : false; //上架 == 前台可售 下架 == 不可销售
        $product_name_chinese = $item_sku_info['item_name']; //商品标题
        $external_created_time = (string)($item_sku_info['created'] * 1000);//商品创建时间
        $img_arr = $this->getImgUrl($item_sku_info['pics']);
        $external_category_id_leaf = $item_sku_info['item_category'];
        //多规格商品
//        if ($itemSkuInfo[''] == '') {
//            $product_props = [
//                'color' => [
//                    'color_rgb'  => '',
//                    'color_name' => ''
//                ],
//                'size'  => ''
//            ];
//        } else {
//            $product_props = [];
//        }
        $category_props = [
            'external_category_id_leaf' => $external_category_id_leaf, //	您为商品分配的叶子类目 id
            'category_type' => 2,
        ];
        $skus[] = [
            'external_sku_id' => $external_sku_id,
            'external_spu_id' => $external_spu_id,
            'sku_barcode' => $sku_barcode,
            'img_urls' => $img_arr,
            'category_props' => $category_props,
            'sales_props' => [
                'is_available' => $is_available
            ],
            'desc_props' => [
                'product_name_chinese' => $product_name_chinese
            ],
            'external_created_time' => $external_created_time
        ];

        return $skus;
    }

    /**
     * 格式化商品图片url列表
     * @param array $item_sku_pic_info
     * @return array
     */
    public function getImgUrl($item_sku_pic_info)
    {
        $image_list = [];
        foreach ($item_sku_pic_info as $v) {
            $image_list[] = [
                "img_url" => $v
            ];
        }
        // 商品图片url列表 数组最大长度 10
        $image_list = array_slice($image_list, 0, 10);
        $img_urls[] = [
            'primary_imgs' => $image_list ? [$image_list[0]] : [],
            'imgs' => $image_list,
            'detail_imgs' => $image_list,
        ];

        return $img_urls;
    }
}
