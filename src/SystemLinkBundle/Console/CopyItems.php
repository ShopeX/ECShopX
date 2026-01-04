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

namespace SystemLinkBundle\Console;

use EspierBundle\Entities\UploadImages;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Entities\ItemsRelCats;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsCategoryService;
use Illuminate\Console\Command;
use SystemLinkBundle\Jobs\CopyItemsJob;
use WechatBundle\Entities\WeappSetting;

class CopyItems extends Command
{
    /**
     * 控制台命令 signature 的名称。
     *
     * @var string
     */
    protected $signature = 'copyitems {fromCompany} {toCompany} {--data=items}';

    /**
     * 控制台命令说明。
     *
     * @var string
     */
    protected $description = '复制商品信息';

    private $redis;

    /**
     * 创建一个新的命令实例。
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->redis = app('redis')->connection('default');
    }



    /**
     * 执行控制台命令。
     *
     * @return mixed
     */
    public function handle()
    {
        // ShopEx EcShopX Business Logic Layer
        $toCompanyId = $this->argument('toCompany');
        $fromCompanyId = $this->argument('fromCompany');
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $data = $this->option('data');
            $this->redis->SET('copyitems_from_company_id', $fromCompanyId);
            $this->redis->SET('copyitems_to_company_id', $toCompanyId);
            if ($data == 'items') {
                $tables = ['items_attributes', 'items', 'items_rel_attributes', 'items_attribute_values', 'items_category', 'espier_uploadimages', 'wechat_weapp_setting'];
                foreach ($tables as $value) {
                    $qb = $conn->createQueryBuilder()->delete($value);
                    $qb = $qb->andWhere($qb->expr()->eq('company_id', $qb->expr()->literal($toCompanyId)));
                    $qb->execute();
                }
                $this->cleanRedis();
                $this->redis->SET('withtemplate', 'false');
                $this->copyAttributes();
                $this->copyUploadImages();
            } elseif ($data == 'templates') {
                if ($this->redis->EXISTS('item_relation_'.$fromCompanyId.'_'.$toCompanyId)) {
                    $this->copyWeappTemplate();
                } else {
                    $this->error('商品信息不存在');
                }
            } elseif ($data == 'all') {
                $tables = ['items_attributes', 'items', 'items_rel_attributes', 'items_attribute_values', 'items_category', 'espier_uploadimages', 'wechat_weapp_setting'];
                foreach ($tables as $value) {
                    $qb = $conn->createQueryBuilder()->delete($value);
                    $qb = $qb->andWhere($qb->expr()->eq('company_id', $qb->expr()->literal($toCompanyId)));
                    $qb->execute();
                }
                $this->cleanRedis();
                $this->redis->SET('withtemplate', 'true');
                $this->copyAttributes();
                $this->copyUploadImages();
            }

            $conn->commit();
        } catch (\Exception $exception) {
            $this->error('复制失败');
            $conn->rollback();
            throw $exception;
        }

        return true;
    }

    /**
     * 复制商品属性
     * @return bool
     */
    public function copyAttributes()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $attributeServices = new ItemsAttributesService();

        $attributeCount = $attributeServices->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($attributeCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $categories = $attributeServices->lists(['company_id' => $fromCompanyId], $i, 500, ['attribute_id' => 'asc'])['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('attribute', $categories, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 复制商品属性值
     * @param $fromCompanyId
     * @param $toCompanyId
     * @return bool
     */
    public function copyAttributeValues()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $itemsAttributeValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);

        $itemsAttrValueCount = $itemsAttributeValuesRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($itemsAttrValueCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemsAttrValues = $itemsAttributeValuesRepository->lists(['company_id' => $fromCompanyId], $i, 500, ['attribute_value_id' => 'asc'])['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('attrvalue', $itemsAttrValues, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 复制商品主类目、分类
     * @param $fromCompanyId
     * @param $toCompanyId
     * @return bool
     */
    public function copyCategory()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $categoryServices = new ItemsCategoryService();

        $filter = [
            'company_id' => $fromCompanyId,
            'parent_id|neq' => -1
        ];
        $categoriesCount = $categoryServices->countCopy($filter);
        $totalPage = ceil(bcdiv($categoriesCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $categories = $categoryServices->listsCopy($filter, ['category_id' => 'asc'], 500, $i)['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('category', $categories, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        return true;
    }

    /**
     * 修改分类path字段
     * @return bool
     */
    public function updateCategoryPath()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $categoryServices = new ItemsCategoryService();
        $categoriesCount = $categoryServices->countCopy(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($categoriesCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $categories = $categoryServices->listsCopy(['company_id' => $fromCompanyId], ['category_id' => 'asc'], 500, $i)['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('categorypath', $categories, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }

        return true;
    }

    /**
     * 复制商品
     * @return bool
     */
    public function copyItems()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $itemsCount = $itemsRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($itemsCount, 100, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $items = $itemsRepository->listCopy(['company_id' => $fromCompanyId], ['item_id' => 'asc'], 100, $i)['list'];
            if ($i == $totalPage) {
                $isLast = true;
            } else {
                $isLast = false;
            }
            $gotoJob = (new CopyItemsJob('items', $items, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 修改商品goods_id字段
     * @return bool
     */
    public function updateItemsGoodsId()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);

        $itemsCount = $itemsRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($itemsCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $items = $itemsRepository->list(['company_id' => $fromCompanyId], ['item_id' => 'asc'], 500, $i)['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('itemsgoodsid', $items, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 复制商品关联属性
     * @return bool
     */
    public function copyItemRelAttribute()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $itemRelAttributeRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);


        $itemRelAttributeCount = $itemRelAttributeRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($itemRelAttributeCount, 200, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemRelAttributes = $itemRelAttributeRepository->lists(['company_id' => $fromCompanyId], $i, 200, ['id' => 'asc'])['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('itemrelattr', $itemRelAttributes, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 复制商品关联分类
     * @return bool
     */
    public function copyItemRelCate()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $itemRelCateRepository = app('registry')->getManager('default')->getRepository(ItemsRelCats::class);

        $itemRelCateCount = $itemRelCateRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($itemRelCateCount, 500, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $itemRelCates = $itemRelCateRepository->getList(['company_id' => $fromCompanyId], "*", $i, 500, []);
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('itemrelcate', $itemRelCates, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    /**
     * 复制图片视频素材
     * @return bool
     */
    public function copyUploadImages()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $imagesRepository = app('registry')->getManager('default')->getRepository(UploadImages::class);

        $count = $imagesRepository->count(['company_id' => $fromCompanyId]);
        $totalPage = ceil(bcdiv($count, 50, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $images = $imagesRepository->lists(['company_id' => $fromCompanyId], $i, 50, ['image_id' => 'asc'])['list'];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('images', $images, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
        return true;
    }

    public function copyWeappTemplate()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $templateRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);

        $count = $templateRepository->count(['company_id' => $fromCompanyId, 'template_name' => 'yykweishop', 'page_name' => 'index']);
        $totalPage = ceil(bcdiv($count, 50, 3));
        for ($i = 1; $i <= $totalPage; $i++) {
            $templates = $templateRepository->lists(['company_id' => $fromCompanyId, 'template_name' => 'yykweishop',], ['id' => 'asc'], 50, $i)["list"];
            $isLast = ($i == $totalPage) ? true : false;

            $gotoJob = (new CopyItemsJob('weapptemplate', $templates, $isLast))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        }
    }

    public function cleanRedis()
    {
        $fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $toCompanyId = $this->redis->get('copyitems_to_company_id');
        $this->redis->DEL('item_relation_'.$fromCompanyId.'_'.$toCompanyId);
        $this->redis->DEL('category_relation_'.$fromCompanyId.'_'.$toCompanyId);
        $this->redis->DEL('attribute_relation');
        $this->redis->DEL('attribute_value_relation');
//        $this->redis->DEL('copyitems_from_company_id');
//        $this->redis->DEL('copyitems_to_company_id');
        $this->redis->DEL('withtemplate');
    }
}
