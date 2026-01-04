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

namespace SystemLinkBundle\Services;

use EspierBundle\Entities\UploadImages;
use GoodsBundle\Entities\ItemRelAttributes;
use GoodsBundle\Entities\Items;
use GoodsBundle\Entities\ItemsAttributeValues;
use GoodsBundle\Entities\ItemsRelCats;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsCategoryService;
use SystemLinkBundle\Console\CopyItems as CopyItemsConsole;
use WechatBundle\Entities\WeappSetting;

class CopyItems
{
    private $redis;
    private $fromCompanyId;
    private $toCompanyId;

    public function __construct()
    {
        // HACK: temporary solution
        $this->redis = app('redis')->connection('default');
        $this->fromCompanyId = $this->redis->get('copyitems_from_company_id');
        $this->toCompanyId = $this->redis->get('copyitems_to_company_id');
    }

    public function doAttributes($attributes, $isLast = false)
    {
        echo '属性...'."\n";
        $attributeServices = new ItemsAttributesService();
        foreach ($attributes as $attribute) {
            $tempId = $attribute['attribute_id'];
            unset($attribute['attribute_id'], $attribute['created'], $attribute['updated']);
            $attribute['company_id'] = $this->toCompanyId;
            $result = $attributeServices->create($attribute);
            $this->redis->HMSET("attribute_relation", $tempId, $result['attribute_id']);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->copyAttributeValues();
        }
        return true;
    }

    public function doAttrValues($itemsAttrValues, $isLast)
    {
        echo '属性值...'."\n";
        $itemsAttributeValuesRepository = app('registry')->getManager('default')->getRepository(ItemsAttributeValues::class);
        foreach ($itemsAttrValues as $itemsAttrValue) {
            $tempId = $itemsAttrValue['attribute_value_id'];
            unset($itemsAttrValue['attribute_value_id'], $itemsAttrValue['created'], $itemsAttrValue['updated']);
            $itemsAttrValue['company_id'] = $this->toCompanyId;
            $itemsAttrValue['attribute_id'] = $this->redis->HGET('attribute_relation', $itemsAttrValue['attribute_id']) ?: 0;
            $result = $itemsAttributeValuesRepository->create($itemsAttrValue);
            $this->redis->HMSET('attribute_value_relation', $tempId, $result['attribute_value_id']);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->copyCategory();
        }
        return true;
    }

    public function doCategory($categories, $isLast = false)
    {
        echo '分类...'."\n";
        $categoryServices = new ItemsCategoryService();
        foreach ($categories as $category) {
            $tempId = $category['category_id'];
            unset($category['category_id'], $category['created'], $category['updated']);

            if (is_array($category['goods_params']) && $category['goods_params']) {
                $temp = [];
                foreach ($category['goods_params'] as $param) {
                    array_push($temp, $this->redis->HGET('attribute_relation', $param));
                }
                $category['goods_params'] = $temp;
            }
            if ($category['goods_spec']) {
                $temp = [];
                foreach ($category['goods_spec'] as $spec) {
                    array_push($temp, $this->redis->HGET('attribute_relation', $spec));
                }
                $category['goods_spec'] = $temp;
            }

            $category['company_id'] = $this->toCompanyId;
            $category['distributor_id'] = 0;
            $result = $categoryServices->create($category);
            $this->redis->HMSET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $tempId, $result['category_id']);
        }
        //所有分类处理完成，开始处理分类path字段
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->updateCategoryPath();
        }
        return true;
    }

    public function doCategoryPath($categories, $isLast = false)
    {
        echo '分类path...'."\n";
        $categoryServices = new ItemsCategoryService();
        foreach ($categories as $category) {
            $paths = explode(',', $category['path']);
            $newId = $this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $category['category_id']);
            $newPaths = [];
            foreach ($paths as $path) {
                $temp = $this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $path);
                array_push($newPaths, $temp);
            }
            $filter = ['category_id' => $newId];
            $newPaths = implode(',', $newPaths);
            $data = [
                'path' => $newPaths,
                'parent_id' => $category['parent_id'] ? $this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $category['parent_id']) : 0
            ];
            $categoryServices->updateBy($filter, $data);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->copyItems();
        }
        return true;
    }

    public function doItems($items, $isLast)
    {
        echo '商品...'."\n";
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        foreach ($items as $item) {
            $tempId = $item['item_id'];
            unset($item['item_id'], $item['created'], $item['updated']);
            $item['company_id'] = $this->toCompanyId;
            $item['templates_id'] = 0;
            $item['item_category'] = $this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $item['item_category']);
            $item['goods_id'] = 0;
            $item['default_id'] = 0;
            $item['distributor_id'] = 0;
            $item['brand_id'] = $item['brand_id'] ? $this->redis->HGET('attribute_relation', $item['brand_id']) : '';
            $item['nospec'] = ($item['nospec'] === true || $item['nospec'] === 'true' || $item['nospec'] === 1 || $item['nospec'] === '1') ? true : false;
            if (is_array($item['intro'])) {
                $item['intro'] = json_encode($item['intro']);
            }
            $result = $itemsRepository->create($item);
            $this->redis->HMSET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $tempId, $result['item_id']);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->updateItemsGoodsId();
            if ($this->redis->GET('withtemplate') == 'true') {
                $copyItems->copyWeappTemplate();
            }
        }
        return true;
    }

    public function doItemsGoodsId($items, $isLast)
    {
        echo '商品default_item_id'."\n";
        $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
        foreach ($items as $item) {
            $newId = $this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $item['item_id']);
            $filter = [
                'item_id' => $newId
            ];
            $data = [
                'goods_id' => intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $item['default_item_id'])),
                'default_item_id' => intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $item['default_item_id']))
            ];
            $itemsRepository->updateBy($filter, $data);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->copyItemRelAttribute();
        }
        return true;
    }

    public function doItemRelAttr($itemRelAttributes, $isLast)
    {
        echo '商品关联属性...'."\n";
        $itemRelAttributeRepository = app('registry')->getManager('default')->getRepository(ItemRelAttributes::class);
        foreach ($itemRelAttributes as $itemRelAttribute) {
            $tempId = $itemRelAttribute['id'];
            if (!intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelAttribute['item_id']))) {
                app('log')->debug('商品关联属性错误：'.'原rel_id-'.$tempId.'商品id-'.$itemRelAttribute['item_id'].'不存在');
                continue;
            }
            if (!intval($this->redis->HGET('attribute_relation', $itemRelAttribute['attribute_id']))) {
                app('log')->debug('商品关联属性错误：'.'原rel_id-'.$tempId.'属性id-'.$itemRelAttribute['attribute_id'].'不存在');
                continue;
            }
            $itemRelAttribute['company_id'] = $this->toCompanyId;
            $itemRelAttribute['item_id'] = intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelAttribute['item_id']));
            $itemRelAttribute['attribute_id'] = intval($this->redis->HGET('attribute_relation', $itemRelAttribute['attribute_id']));
            $itemRelAttribute['attribute_value_id'] = $itemRelAttribute['attribute_type'] != 'brand' ? intval($this->redis->HGET('attribute_value_relation', $itemRelAttribute['attribute_value_id'])) : null;
            $itemRelAttributeRepository->create($itemRelAttribute);
        }
        if ($isLast) {
            $copyItems = new CopyItemsConsole();
            $copyItems->copyItemRelCate();
        }
        return true;
    }

    public function doItemRelCate($itemRelCates, $isLast)
    {
        echo '商品关联分类...'."\n";
        $itemRelCateRepository = app('registry')->getManager('default')->getRepository(ItemsRelCats::class);
        foreach ($itemRelCates as $itemRelCate) {
            if (!intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelCate['item_id']))) {
                app('log')->debug('商品关联属性错误：商品id-'.$itemRelCate['item_id'].'不存在');
                continue;
            }
            if (!intval($this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelCate['category_id']))) {
                app('log')->debug('商品关联属性错误：分类id-'.$itemRelCate['category_id'].'不存在');
                continue;
            }
            unset($itemRelCate['created'], $itemRelCate['updated']);
            $itemRelCate['company_id'] = $this->toCompanyId;
            $itemRelCate['item_id'] = intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelCate['item_id']));
            $itemRelCate['category_id'] = intval($this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $itemRelCate['category_id']));
            $itemRelCateRepository->create($itemRelCate);
        }
        if ($isLast) {
            //$this->cleanRedis();
        }
        return true;
    }

    public function doImages($images, $isLast)
    {
        echo '图片视频素材...'."\n";
        $imagesRepository = app('registry')->getManager('default')->getRepository(UploadImages::class);
        foreach ($images as $image) {
            try {
                $image['company_id'] = $this->toCompanyId;
                $oldImageUrl = $image['image_url'];
                if ($image['storage'] == 'image') {
                    $imageUrl = explode('/', $image['image_url']);
                    $imageUrl[0] = $this->toCompanyId;
                    $fileSystem = app('filesystem')->disk('import-image');
                } elseif ($image['storage'] == 'videos') {
                    $imageUrl = explode('/', $image['image_url']);
                    $imageUrl[1] = $this->toCompanyId;
                    $fileSystem = app('filesystem')->disk('import-videos');
                } else {
                    app('log')->debug('未知存储类型：image_id-'.$image['image_id']);
                    continue;
                }
                $imageUrl = implode('/', $imageUrl);
                $image['image_url'] = $imageUrl;
                $imagesRepository->create($image);
                if ($fileSystem->has($oldImageUrl)) {
                    $copyRes = $fileSystem->copy($oldImageUrl, $imageUrl);
                    if (!$copyRes) {
                        app('log')->debug('文件复制失败：url-'.$oldImageUrl. ' -----id-'.$image['image_id']);
                    }
                } else {
                    app('log')->debug('文件不存在：url-'.$oldImageUrl);
                }
            } catch (\Exception $exception) {
                app('log')->debug('复制文件错误 -> '.$exception->getFile().':line'.$exception->getLine().':'. $exception->getMessage());
            }
        }
        return true;
    }

    public function doWeappTemplate($templates, $isLast)
    {
        echo '微信小程序模板...'."\n";
        $templateRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);
        foreach ($templates as $template) {
            $template['company_id'] = $this->toCompanyId;

            if ($template['name'] == 'goodsGrid') {
                $params = unserialize($template['params']);

                foreach ($params['data'] as &$data) {
                    $data['goodsId'] = intval($this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $data['goodsId']));
                }
                //unset($v);
                $template['params'] = serialize($params);
            }
            if ($template['name'] == 'imgHotzone') {
                $params = unserialize($template['params']);
                if (($params['data'] ?? false) && $params['data']) {
                    if ($params['data'][0]['linkPage'] == 'category') {
                        $params['data'][0]['id'] = $this->redis->HGET('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $params['data'][0]['id']);
                    } elseif ($params['data'][0]['linkPage'] == 'goods') {
                        $params['data'][0]['id'] = $this->redis->HGET('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId, $params['data'][0]['id']);
                    }
                    $template['params'] = serialize($params);
                }
                //unset($value, $v);
            }

            $templateRepository->create($template);
        }
        return true;
    }

    public function cleanRedis()
    {
//        $this->redis->DEL('item_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId);
//        $this->redis->DEL('category_relation_'.$this->fromCompanyId.'_'.$this->toCompanyId);
        $this->redis->DEL('attribute_relation');
        $this->redis->DEL('attribute_value_relation');
        $this->redis->DEL('copyitems_from_company_id');
        $this->redis->DEL('copyitems_to_company_id');
        $this->redis->DEL('withtemplate');
        echo '完成'."\n";
    }
}
