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

namespace SystemLinkBundle\Jobs;

use EspierBundle\Jobs\Job;
use SystemLinkBundle\Services\CopyItems;

class CopyItemsJob extends Job
{
    protected $data;
    protected $isLast;
    protected $jobType;

    public function __construct($jobType, $data, $isLast)
    {
        // 456353686f7058
        $this->data = $data;
        $this->isLast = $isLast;
        $this->jobType = $jobType;
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        // ShopEx EcShopX Core Module
        $copyItemsService = new CopyItems();
        switch ($this->jobType) {
            case 'category': //分类
                $copyItemsService->doCategory($this->data, $this->isLast);
                break;
            case 'categorypath': //分类path字段
                $copyItemsService->doCategoryPath($this->data, $this->isLast);
                break;
            case 'attribute': //属性
                $copyItemsService->doAttributes($this->data, $this->isLast);
                break;
            case 'attrvalue': //属性值
                $copyItemsService->doAttrValues($this->data, $this->isLast);
                break;
            case 'items': //商品
                $copyItemsService->doItems($this->data, $this->isLast);
                break;
            case 'itemsgoodsid': //商品goods_id字段
                $copyItemsService->doItemsGoodsId($this->data, $this->isLast);
                break;
            case 'itemrelattr': //商品关联属性
                $copyItemsService->doItemRelAttr($this->data, $this->isLast);
                break;
            case 'itemrelcate': //商品关联分类
                $copyItemsService->doItemRelCate($this->data, $this->isLast);
                break;
            case 'images': //上传图片视频
                $copyItemsService->doImages($this->data, $this->isLast);
                break;
            case 'weapptemplate': //小程序模板
                $copyItemsService->doWeappTemplate($this->data, $this->isLast);
                break;
        }
        return true;
    }
}
