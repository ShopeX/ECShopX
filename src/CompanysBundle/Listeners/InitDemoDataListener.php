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

namespace CompanysBundle\Listeners;

use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use EspierBundle\Listeners\BaseListeners;
use CompanysBundle\Events\CompanyCreateEvent;
use GoodsBundle\Services\ItemsCategoryService;
use MerchantBundle\Services\MerchantService;
use MerchantBundle\Services\MerchantSettingService;
use MerchantBundle\Services\MerchantSettlementApplyService;
use OrdersBundle\Services\ShippingTemplatesService;
use GoodsBundle\Services\ItemsAttributesService;
use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Entities\SeckillActivity;
use PromotionsBundle\Entities\SeckillRelGoods;
use PromotionsBundle\Services\PromotionSeckillActivityService;
use ThemeBundle\Entities\ThemePcTemplateContent;
use ThemeBundle\Services\ThemePcTemplateServices;
use WechatBundle\Entities\WeappSetting;
use ThemeBundle\Services\PagesTemplateServices;
use WechatBundle\Services\Wxapp\TemplateService;
use AliyunsmsBundle\Entities\Scene;

class InitDemoDataListener extends BaseListeners implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  WxShopsAddEvent  $event
     * @return void
     */
    public function handle(CompanyCreateEvent $event)
    {
        // 如果为数云模式，则不执行
        if (config('common.oem-shuyun')) {
            return false;
        }
        $companyId = $event->entities['company_id'];
        //初始化阿里云短信模版
        $this->addAliyunsmsScene($companyId);
        // 添加运费模板
        $this->addShippingTemplates($companyId);
        // 添加商品品牌
        $this->addGoodsBrand($companyId);
        // 添加商品规格
        $this->addGoodsSpec($companyId);
        // 添加商品参数
        $this->addGoodsParams($companyId);
        // 添加商品分类
        $this->addCatetory($companyId);
        // 添加商品主类目
        $this->addMainCatetory($companyId);
        // 商品主类目叶子节点关联商品规格、商品参数
        $this->relGoodsSpecAndParams($companyId);
        // 添加商品
        $this->addGoods($companyId);
        // 添加秒杀活动
        //   $this->addSeckill($companyId);
        // 添加商户数据
        // $this->addMerchant($companyId);
        // 添加小程序模板
        $this->addWeiShopTemplate($companyId);
        // 添加pc模板
        $this->addPcTemplate($companyId);
    }

    //初始化阿里云短信模版
    public function addAliyunsmsScene($companyId)
    {
        try {
            $input = file_get_contents(storage_path('static/sms_scene.json'));
            $input = json_decode($input, true);
        } catch (\Exception $e) {
            app('log')->debug("读取json文件出错".$e->getMessage());
            return true;
        }
        if (!$input) {
            app('log')->debug("未读取到模板json文件");
            return true;
        }
        $repository = app('registry')->getManager('default')->getRepository(Scene::class);
        //判断是否执行过
        if($repository->getInfo(['company_id' => $companyId])) {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        //template_type: 0-验证码; 1-短信通知; 2-推广短信
        try {
            foreach ($input as $item) {
                $tmp = [
                    'company_id' => $companyId,
                    'scene_name' => $item['scene_name'],
                    'scene_title' => $item['scene_title'],
                    'template_type' => $item['template_type'],
                    'default_template' => $item['default_template'] ?? null,
                ];
                if($item['variables'] ?? 0) {
                    $tmp['variables'] = json_encode($item['variables']);
                }
                $repository->create($tmp);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            app('log')->debug("导入短信场景数据出错：".$e->getMessage());
            return true;
        }
    }

    // 添加运费模板
    public function addShippingTemplates($companyId)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();
        $params = '{
                "name": "包邮",
                "status": "1",
                "is_free": "1",
                "fee_conf": [
                    {
                        "add_fee": "",
                        "start_fee": "",
                        "add_standard": "",
                        "start_standard": ""
                    }
                ],
                "free_conf": [
                    {
                        "area": "0",
                        "upmoney": "",
                        "freetype": "1",
                        "inweight": ""
                    }
                ],
                "valuation": "1"
            }';
        $params = json_decode($params, 1);
        $params['fee_conf'] = json_encode($params['fee_conf']);
        $params['free_conf'] = json_encode($params['free_conf']);
        $params['company_id'] = $companyId;
        $params['distributor_id'] = 0;
        $params['nopost_conf'] = json_encode([]);
        $result = $shippingTemplatesServices->createShippingTemplates($params);
    }

    // 添加商品品牌
    public function addGoodsBrand($companyId)
    {
        $params = '{
            "image_url": "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH",
            "attribute_id": "",
            "attribute_name": "ECshopX",
            "attribute_type": "brand"
        }';
        $input = json_decode($params, true);
        $input['company_id'] = $companyId;

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->createAttr($input);
    }

    // 添加商品规格
    public function addGoodsSpec($companyId)
    {
        $params = '{
            "is_image": "false",
            "attribute_id": "",
            "attribute_memo": "",
            "attribute_name": "颜色",
            "attribute_type": "item_spec",
            "attribute_values": "[{\"attribute_value\":\"红色\",\"image_url\":\"\"},{\"attribute_value\":\"绿色\",\"image_url\":\"\"}]"
        }';
        $input = json_decode($params, true);
        $input['attribute_values'] = json_decode($input['attribute_values'], true);
        $input['company_id'] = $companyId;

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->createAttr($input);
    }

    // 添加商品参数
    public function addGoodsParams($companyId)
    {
        $params = '{
            "is_show": "true",
            "is_image": "false",
            "attribute_id": "",
            "attribute_memo": "",
            "attribute_name": "原产地",
            "attribute_type": "item_params",
            "attribute_values": "[{\"attribute_value\":\"中国\"}]"
        }';
        $input = json_decode($params, true);
        $input['attribute_values'] = json_decode($input['attribute_values'], true);
        $input['company_id'] = $companyId;

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->createAttr($input);
    }

    // 添加商品分类
    public function addCatetory($companyId)
    {
        $params['form'] = '[{"id":1611904111,"category_name":"热销商品","sort":0,"level":0,"children":[],"created":0,"image_url":""}]';
        $params['form'] = json_decode($params['form'], true);
        $distributorId = 0;
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->saveItemsCategory($params['form'], $companyId, $distributorId);
    }

    // 添加商品主类目
    public function addMainCatetory($companyId)
    {
        $params['form'] = '[{"id":1611902635,"category_name":"热销商品","sort":0,"category_level":1,"children":[{"id":1611902644,"category_name":"热销","sort":0,"category_level":2,"children":[{"id":1611902663,"category_name":"爆品","sort":0,"category_level":3,"children":[],"created":-1,"image_url":"","is_main_category":true,"goods_params":[],"parent_id":1611902635}],"created":-1,"image_url":"","is_main_category":true,"goods_params":[],"parent_id":1611902635}],"created":-1,"image_url":"","is_main_category":true,"goods_params":[]}]';
        $params['form'] = json_decode($params['form'], true);
        $distributorId = 0;
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->saveItemsCategory($params['form'], $companyId, $distributorId);
    }

    // 商品主类目叶子节点关联商品规格、商品参数
    public function relGoodsSpecAndParams($companyId)
    {
        // 获取demo数据最后一个商品主类目id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('category_id')
           ->from('items_category')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('category_level', 3))
           ->andWhere($qb->expr()->eq('is_main_category', 1));
        $category_id = $qb->execute()->fetchColumn();
        // 获取参数id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
           ->from('items_attributes')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_spec')));
        $goodsSpecId = $qb->execute()->fetchColumn();
        // 获取规格id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
           ->from('items_attributes')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_params')));
        $goodsParamsId = $qb->execute()->fetchColumn();

        // 绑定规格、参数
        $data['goods_spec'] = [$goodsSpecId];
        $data['goods_params'] = [$goodsParamsId];
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->updateOneBy(['category_id' => $category_id, 'company_id' => $companyId], $data);
    }

    // 添加商品
    public function addGoods($companyId)
    {
        // 获取demo数据最后一个商品主类目id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('category_id')
           ->from('items_category')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('category_level', 3))
           ->andWhere($qb->expr()->eq('is_main_category', 1));
        $goodsMainCategoryId = $qb->execute()->fetchColumn();
        // 获取参数id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
           ->from('items_attributes')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_spec')));
        $goodsSpecId = $qb->execute()->fetchColumn();
        // 获取规格id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
           ->from('items_attributes')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_params')));
        $goodsParamsId = $qb->execute()->fetchColumn();
        // 获取品牌id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
           ->from('items_attributes')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('brand')));
        $goodsBrandId = $qb->execute()->fetchColumn();
        // 获取商品分类id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('category_id')
           ->from('items_category')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('is_main_category', 0));
        $goodsCategoryId = $qb->execute()->fetchColumn();
        // 获取运费模板id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('template_id')
           ->from('shipping_templates')
           ->where($qb->expr()->eq('company_id', $companyId))
           ->andWhere($qb->expr()->eq('status', 1));
        $shippingTemplateId = $qb->execute()->fetchColumn();

        $itemsService = new ItemsService();

        $params1 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "199",
            "store": "1",
            "nospec": "true",
            "rebate": "",
            "videos": "",
            "volume": "",
            "weight": "",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61DE8B3C242D82",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品1",
            "item_type": "normal",
            "item_unit": "",
            "point_num": "0",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params1 = json_decode($params1, true);

        $params1['company_id'] = $companyId;
        $params1['brand_id'] = $goodsBrandId;
        $params1['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params1['templates_id'] = $shippingTemplateId;
        $params1['item_category'] = [$goodsCategoryId];
        $params1['item_main_cat_id'] = $goodsMainCategoryId;

        $params2 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "199",
            "store": "1",
            "nospec": "true",
            "rebate": "0",
            "videos": "",
            "volume": "",
            "weight": "0",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61DE8C1347B74",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品2",
            "item_type": "normal",
            "item_unit": "",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "1999.9",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params2 = json_decode($params2, true);

        $params2['company_id'] = $companyId;
        $params2['brand_id'] = $goodsBrandId;
        $params2['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params2['templates_id'] = $shippingTemplateId;
        $params2['item_category'] = [$goodsCategoryId];
        $params2['item_main_cat_id'] = $goodsMainCategoryId;

        $params3 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "199",
            "store": "1",
            "nospec": "true",
            "rebate": "0",
            "videos": "",
            "volume": "",
            "weight": "0",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61DE8C4229650",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品3",
            "item_type": "normal",
            "item_unit": "",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "1999.9",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params3 = json_decode($params3, true);

        $params3['company_id'] = $companyId;
        $params3['brand_id'] = $goodsBrandId;
        $params3['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params3['templates_id'] = $shippingTemplateId;
        $params3['item_category'] = [$goodsCategoryId];
        $params3['item_main_cat_id'] = $goodsMainCategoryId;

        $result = $itemsService->addItems($params1);
        $result = $itemsService->addItems($params2);
        $result = $itemsService->addItems($params3);
    }

    // 添加秒杀
    public function addSeckill($companyId)
    {
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('item_id,item_name,store,price,item_type')
           ->from('items')
           ->where($qb->expr()->eq('company_id', $companyId));
        $items = $qb->execute()->fetchAll();
        foreach ($items as $k => &$v) {
            // $v['item_id'] = $v['item_id'];
            $v['item_title'] = $v['item_name'];
            $v['activity_store'] = $v['store'];
            $v['activity_price'] = $v['price'] / 100;
            $v['sort'] = $k;
            $v['limit_num'] = 1;
            //$v['item_type'] = $v['item_type'];
        }

        $params = '{
            "items": "[{\"item_id\":\"926\",\"item_title\":\"热销商品4\",\"activity_store\":100,\"activity_price\":99,\"sort\":\"3\",\"limit_num\":1,\"item_type\":\"normal\"},{\"item_id\":\"925\",\"item_title\":\"热销商品3\",\"activity_store\":100,\"activity_price\":99,\"sort\":\"2\",\"limit_num\":1,\"item_type\":\"normal\"},{\"item_id\":\"924\",\"item_title\":\"热销商品2\",\"activity_store\":100,\"activity_price\":99,\"sort\":\"1\",\"limit_num\":1,\"item_type\":\"normal\"},{\"item_id\":\"923\",\"item_title\":\"热销商品1\",\"activity_store\":100,\"activity_price\":99,\"sort\":0,\"limit_num\":1,\"item_type\":\"normal\"}]",
            "ad_pic": "https://preissue-b-img-cdn.yuanyuanke.cn/image/565/2021/01/27/f2ccf0aa3f3bd5dabe5f89a62983ff40jt4X5vtYGa7zzDieaV4l2Jev3GA7oqmC",
            "item_type": "normal",
            "use_bound": "goods",
            "seckill_id": "",
            "description": "限时秒杀，限时专享！",
            "activity_name": "限时秒杀",
            "item_category": "[]",
            "validity_period": "15",
            "is_free_shipping": "false",
            "activity_end_time": "2021-02-27 23:59:59",
            "is_activity_rebate": "false",
            "activity_start_time": "2021-01-27 00:00:00",
            "activity_release_time": "2021-01-26T16:00:00.000Z"
        }';

        $params = json_decode($params, true);
        $params['items'] = $items;
        $params['company_id'] = $companyId;

        $params['activity_start_time'] = time() + 600;
        $params['activity_end_time'] = time() + 600 + (86400 * 30);
        $params['activity_release_time'] = time();
        $params['seckill_type'] = $params['seckill_type'] ?? 'normal';
        $params['distributor_id'] = (isset($params['distributor_id']) && is_array($params['distributor_id'])) ? implode(',', $params['distributor_id']) : null;

        $service = new PromotionSeckillActivityService();
        $service->create($params);
    }


    // 添加商城小程序模板
    public function addWeiShopTemplate($companyId)
    {
        //商户初始化微信模板数据创建
        $params = [
            'company_id' => $companyId,
            'distributor_id' => 0,
            'template_title' => 'B2B2C商圈/同城',
            'template_name' => 'yykweishop',
            'template_pic' => 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH',
            'template_type' => 0,
            'status' => 1,
            'weapp_pages' => 'index',
        ];

        $pagesTemplateServices = new PagesTemplateServices();
        $result = $pagesTemplateServices->create($params);

        $new_pagesTemplateId = $result['pages_template_id'];

        //查找模板商户微信模板内容
        $templateName = 'yykweishop';
        $pageName = 'index';
        $configName = '';
        $version = 'v1.0.2';

        $entityRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);

        //search
        $name = 'search';
        $configParams = 'a:5:{s:4:"name";s:6:"search";s:4:"base";a:1:{s:6:"padded";b:0;}s:6:"config";a:2:{s:6:"fixTop";b:0;s:8:"scanCode";b:0;}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);

        $name = 'slider';
        $configParams = 'a:6:{s:4:"name";s:6:"slider";s:4:"base";a:3:{s:5:"title";s:0:"";s:8:"subtitle";s:0:"";s:6:"padded";b:0;}s:6:"config";a:12:{s:7:"current";i:1;s:8:"interval";i:3000;s:7:"spacing";i:0;s:3:"dot";b:0;s:11:"dotLocation";s:5:"right";s:8:"dotColor";s:4:"dark";s:5:"shape";s:6:"circle";s:11:"numNavShape";s:4:"rect";s:8:"dotCover";b:1;s:7:"rounded";b:0;s:6:"padded";b:0;s:7:"content";b:1;}s:4:"data";a:2:{i:0;a:6:{s:6:"imgUrl";s:141:"https://preissue-b-img-cdn.yuanyuanke.cn/default_project/image/42/2021/12/17/dfcf07f7b1a2487185c9bea769cd5d08gLZO7SHcWYSYUlqu4gzAPUbFEAUwBl9b";s:8:"linkPage";s:11:"custom_page";s:7:"content";s:0:"";s:2:"id";s:2:"82";s:8:"template";s:3:"one";s:5:"title";s:9:"生活馆";}i:1;a:6:{s:6:"imgUrl";s:141:"https://preissue-b-img-cdn.yuanyuanke.cn/default_project/image/42/2021/12/17/dfcf07f7b1a2487185c9bea769cd5d08dCB7BBIzlA3hOV3LWY4L3hUSy67KmiVm";s:8:"linkPage";s:5:"goods";s:7:"content";s:0:"";s:2:"id";s:4:"1458";s:8:"template";s:3:"one";s:5:"title";s:65:"Lamer海蓝之谜 修护精粹液30ml小样 浓缩奇迹精华水";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);

        $name = 'navigation';
        $configParams = 'a:5:{s:4:"name";s:10:"navigation";s:4:"base";a:1:{s:6:"padded";b:1;}s:4:"data";a:5:{i:0;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5G6YJdq8TavZ8jCm5rw4HA0dY1gnvVFDF";s:8:"linkPage";s:0:"";s:7:"content";s:6:"服饰";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:1;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5TN3fGOMhgax66WMp9rYgxumZLqxkdvAo";s:8:"linkPage";s:0:"";s:7:"content";s:6:"母婴";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:2;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5lDlBqubjrxfAIwrbLTYLT9o9Z7op9Hg0";s:8:"linkPage";s:0:"";s:7:"content";s:6:"美妆";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:3;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5DLZoRNagSJWeIKzWsc2YHP2qkcDvg8oE";s:8:"linkPage";s:0:"";s:7:"content";s:8:"3D数码";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:4;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5jyIkj1llduMLEcLjq2L6y8eVT2rrIQbk";s:8:"linkPage";s:0:"";s:7:"content";s:12:"宠物用品";s:5:"title";s:0:"";s:2:"id";s:0:"";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        $name = 'navigation';
        $configParams = 'a:5:{s:4:"name";s:10:"navigation";s:4:"base";a:1:{s:6:"padded";b:1;}s:4:"data";a:5:{i:0;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5ttrqiywBk3exbVQEMp2plbcPRN1r8YrU";s:8:"linkPage";s:0:"";s:7:"content";s:12:"休闲食品";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:1;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5kOxt0qhSTEN6VUe0BmAlBlcTX3hkM95y";s:8:"linkPage";s:0:"";s:7:"content";s:12:"生鲜果蔬";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:2;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5WxH8nyPibZHHOH0fy40E3rhi0D94KGAz";s:8:"linkPage";s:0:"";s:7:"content";s:12:"超市便利";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:3;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5dqvfLKLljqj6jFkb4dBbdNpdvPG3r8R2";s:8:"linkPage";s:0:"";s:7:"content";s:6:"美食";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:4;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a00a887c2bf43a6a7389cfc4a8faeba5ok2XBRVBrzSU6y7ucZDs7cJvS7bjh7FS";s:8:"linkPage";s:0:"";s:7:"content";s:6:"美家";s:5:"title";s:0:"";s:2:"id";s:0:"";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        // $name = 'nearbyShop';
        // $configParams = 'a:5:{s:4:"name";s:10:"nearbyShop";s:4:"base";a:3:{s:5:"title";s:12:"附近商家";s:6:"padded";b:0;s:11:"show_coupon";b:1;}s:11:"seletedTags";a:1:{i:0;a:10:{s:6:"tag_id";s:2:"24";s:10:"company_id";s:2:"42";s:8:"tag_name";s:6:"全部";s:9:"tag_color";s:7:"#ff1939";s:10:"font_color";s:7:"#ffffff";s:11:"description";s:0:"";s:8:"tag_icon";N;s:10:"front_show";s:1:"1";s:7:"created";s:10:"1639730523";s:7:"updated";s:10:"1639732054";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        // $configParams = unserialize($configParams);
        // $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        // $name = 'store';
        // $configParams = 'a:6:{s:4:"name";s:5:"store";s:4:"base";a:6:{s:5:"title";s:12:"推荐商铺";s:8:"subtitle";s:27:"热门商铺，官方推荐";s:6:"padded";b:0;s:15:"backgroundColor";s:4:"#FFF";s:11:"borderColor";s:7:"#FFBF00";s:6:"imgUrl";s:0:"";}s:4:"data";a:1:{i:0;a:4:{s:2:"id";s:3:"101";s:4:"name";s:12:"特色购物";s:4:"logo";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bxZsiiZARkIXx70VrEOdbVANzU96nH7hU";s:5:"items";a:3:{i:0;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63KD7Pz6GLWWnDtmwVx9uOmW9FrSorL08I";s:5:"title";s:13:"热销商品1";s:7:"goodsId";s:4:"1469";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63BEc16DPR3xUFprcdL56A2BPnoSTGsKRc";s:5:"price";i:1990;s:14:"distributor_id";i:101;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:28800;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}i:1;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63KD7Pz6GLWWnDtmwVx9uOmW9FrSorL08I";s:5:"title";s:13:"热销商品2";s:7:"goodsId";s:4:"1470";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63BEc16DPR3xUFprcdL56A2BPnoSTGsKRc";s:5:"price";i:1990;s:14:"distributor_id";i:101;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:28800;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}i:2;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63KD7Pz6GLWWnDtmwVx9uOmW9FrSorL08I";s:5:"title";s:13:"热销商品3";s:7:"goodsId";s:4:"1471";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/a37fe1712c0a338267fb17e148d0db63BEc16DPR3xUFprcdL56A2BPnoSTGsKRc";s:5:"price";i:1990;s:14:"distributor_id";i:101;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:28800;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}}}}s:11:"seletedTags";a:1:{i:0;a:11:{s:6:"tag_id";s:2:"95";s:10:"company_id";s:2:"42";s:8:"tag_name";s:6:"热销";s:9:"tag_color";s:7:"#ff1939";s:10:"font_color";s:7:"#ffffff";s:11:"description";N;s:8:"tag_icon";N;s:7:"created";s:10:"1641975443";s:7:"updated";s:10:"1641975443";s:14:"distributor_id";s:3:"101";s:10:"front_show";s:1:"1";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        // $configParams = unserialize($configParams);
        // $configParams = $this->wechatTemplateStore($configParams, $companyId);
        // $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        $name = 'goodsScroll';
        $configParams = 'a:6:{s:4:"name";s:11:"goodsScroll";s:4:"base";a:4:{s:5:"title";s:12:"当地必买";s:8:"subtitle";s:27:"看看大家都在买什么";s:6:"padded";b:1;s:13:"backgroundImg";s:0:"";}s:6:"config";a:7:{s:9:"seckillId";s:0:"";s:11:"leaderboard";b:0;s:9:"showPrice";b:0;s:4:"type";s:5:"goods";s:8:"moreLink";a:3:{s:2:"id";s:0:"";s:5:"title";s:0:"";s:8:"linkPage";s:0:"";}s:6:"status";s:7:"in_sale";s:11:"lastSeconds";i:1238180;}s:4:"data";a:3:{i:0;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:5:"title";s:13:"热销商品1";s:7:"goodsId";s:4:"1466";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH";s:5:"price";i:19990;s:14:"distributor_id";i:0;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:0;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}i:1;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:5:"title";s:13:"热销商品2";s:7:"goodsId";s:4:"1467";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH";s:5:"price";i:19990;s:14:"distributor_id";i:0;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:199990;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}i:2;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:5:"title";s:13:"热销商品3";s:7:"goodsId";s:4:"1468";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH";s:5:"price";i:19990;s:14:"distributor_id";i:0;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:199990;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $configParams = $this->wechatTemplateGoodsGrid($configParams, $companyId);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        $name = 'goodsGridTab';
        $configParams = 'a:7:{s:4:"name";s:12:"goodsGridTab";s:4:"base";a:4:{s:5:"title";s:0:"";s:8:"subtitle";s:0:"";s:6:"padded";b:0;s:9:"listIndex";i:0;}s:6:"config";a:4:{s:5:"brand";b:0;s:9:"showPrice";b:0;s:5:"style";s:4:"grid";s:8:"moreLink";a:3:{s:2:"id";s:0:"";s:5:"title";s:0:"";s:8:"linkPage";s:0:"";}}s:4:"list";a:2:{i:0;a:2:{s:8:"tabTitle";s:6:"newTab";s:9:"goodsList";a:2:{i:0;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:5:"title";s:13:"热销商品1";s:7:"goodsId";s:4:"1466";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH";s:5:"price";i:19990;s:14:"distributor_id";i:0;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:0;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}i:1;a:15:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:5:"title";s:13:"热销商品2";s:7:"goodsId";s:4:"1467";s:5:"brand";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH";s:5:"price";i:19990;s:14:"distributor_id";i:0;s:6:"nospec";b:1;s:12:"special_type";s:6:"normal";s:12:"member_price";i:0;s:12:"market_price";i:199990;s:9:"act_price";i:0;s:18:"promotion_activity";a:0:{}s:17:"promotionActivity";a:0:{}s:16:"cross_border_tax";i:0;s:21:"cross_border_tax_rate";i:0;}}}i:1;a:2:{s:8:"tabTitle";s:6:"newTab";s:9:"goodsList";a:0:{}}}s:4:"data";a:0:{}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $configParams = $this->wechatTemplateGoodsGridTab($configParams, $companyId);
        app('log')->info('configParams'.var_export($configParams, 1));
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        return true;
    }

    // 添加pc模板
    public function addPcTemplate($companyId)
    {
        //商户初始化微信模板数据创建
        $params = [
            'company_id' => $companyId,
            'template_title' => '默认店铺页面',
            'template_description' => '默认店铺页面',
            'page_type' => 'index',
            'status' => 1,
            'version' => 'v1.0.1',
        ];

        $themePcTemplateServices = new ThemePcTemplateServices();
        $result = $themePcTemplateServices->add($params);
        $newThemePcTemplateId = $result['theme_pc_template_id'];

        $themePcTemplateContentRepository = app('registry')->getManager('default')->getRepository(ThemePcTemplateContent::class);

        //header
        $name = 'header';
        $templateContent = '[]';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => 0,
            'name' => $name,
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //footer
        $name = 'footer';
        $templateContent = '[]';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => 0,
            'name' => $name,
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //页面内容
        $templateContent = '{"name":"页面配置","alias":"页面配置","text":"页面配置","type":"W0000","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":1190,"height":20,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":false,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":0,"y":0,"pageWidth":1190,"uuid":"b1428e4642"}';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => $newThemePcTemplateId,
            'name' => '',
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //自由面板
        $templateContent = '{"name":"自由面板","alias":"自由面板","text":"自由面板","type":"W0006","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":1190,"height":200,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":false,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":0,"y":0,"pageWidth":1190,"comptyle":"body","childWidgets":[{"name":"热区图","alias":"热区图","text":"热区图","type":"W0003","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":100,"height":100,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/6abe89639847fa4164d71aa1a391f22baoIM7VOMF2qHnEHim8J0f9MYWnwUahiY","data":[],"opacity":1,"proportion":true,"proportionDisabled":false,"proportionShow":false,"heightDisabled":false,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":196,"y":21,"pageWidth":1190,"uuid":"f5a8bc8912"},{"name":"导航菜单","alias":"导航菜单","text":"导航菜单","type":"W0007","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":550,"height":40,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[{"title":"商品","pathData":{"title":"商品列表","id":"2","url":"\/items","linkPage":"page","linkPageLabel":"页面"}},{"title":"购物车","pathData":{"title":"购物车","id":"4","url":"\/cart","linkPage":"page","linkPageLabel":"页面"}},{"title":"会员中心","pathData":{"title":"会员中心","id":"3","url":"\/member\/user-info","linkPage":"page","linkPageLabel":"页面"}}],"opacity":1,"proportion":"disabled","proportionDisabled":true,"proportionShow":false,"heightDisabled":true,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":146,"y":140,"pageWidth":1190,"wgtTheme":"rgba(255,0,0,1)","wgtTextColor":"#ffffffff","categoryData":[],"hover":false,"menuTop":40,"uuid":"b4a37fbfca"},{"name":"购物车","alias":"购物车","text":"购物车","type":"W0010","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":110,"height":38,"lineHeight":20,"fontSize":14,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":true,"heightShow":true,"widthDisabled":true,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":933,"y":57,"pageWidth":1190,"wgtTheme":"rgba(255,0,0,1)","wgtTextColor":"#ffffffff","uuid":"75e004a882"},{"name":"搜索","alias":"搜索","text":"搜索","type":"W0008","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":500,"height":38,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":"disabled","proportionDisabled":true,"proportionShow":false,"heightDisabled":true,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":375,"y":57,"pageWidth":1190,"wgtTheme":"rgba(255,0,0,1)","wgtTextColor":"#ffffffff","placeholder":"搜索","focusWidth":100,"isShowBtn":true,"uuid":"66b299d804"}],"uuid":"81e3018f34"}';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => $newThemePcTemplateId,
            'name' => '',
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //热区图
        $templateContent = '{"name":"热区图","alias":"热区图","text":"热区图","type":"W0003","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":1190,"height":476,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/03\/ab9df05eeba267340df6bcc775f3701ahdbzviDz5kaKtP8TIMqWXwGV9lGJo1Z0","data":[],"opacity":1,"proportion":true,"proportionDisabled":false,"proportionShow":false,"heightDisabled":false,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":0,"y":0,"pageWidth":1190,"uuid":"fb7649ade5"}';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => $newThemePcTemplateId,
            'name' => '',
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //商品橱窗
        $templateContent = '{"name":"商品橱窗","alias":"商品橱窗","text":"限时抢购","type":"W0005","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":1190,"height":564,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[{"text":"新客专享","data":[{"item_id":"923","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"923","brand_id":235,"item_name":"热销商品1","item_unit":"","item_bn":"S6010FE9A2048A","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"923","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1611726490,"updated":1612175348,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"923","consumeType":"every","itemName":"热销商品1","itemBn":"S6010FE9A2048A","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"promotion_activity":[{"promotion_id":"97","tag_type":"normal","activity_price":"9900","start_time":"1612175431","end_time":"1614700799","item_id":"923"}],"activity_price":"9900","tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"924","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"924","brand_id":235,"item_name":"热销商品2","item_unit":"","item_bn":"S6010FEA8A2838","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"924","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1611726504,"updated":1612175361,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"924","consumeType":"every","itemName":"热销商品2","itemBn":"S6010FEA8A2838","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"promotion_activity":[{"promotion_id":"97","tag_type":"normal","activity_price":"9900","start_time":"1612175431","end_time":"1614700799","item_id":"924"}],"activity_price":"9900","tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"925","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"925","brand_id":235,"item_name":"热销商品3","item_unit":"","item_bn":"S6010FEB22D0CB","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"925","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1611726514,"updated":1612175376,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"925","consumeType":"every","itemName":"热销商品3","itemBn":"S6010FEB22D0CB","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"promotion_activity":[{"promotion_id":"97","tag_type":"normal","activity_price":"9900","start_time":"1612175431","end_time":"1614700799","item_id":"925"}],"activity_price":"9900","tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"926","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"926","brand_id":235,"item_name":"热销商品4","item_unit":"","item_bn":"S6010FEBBF375A","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"926","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1611726524,"updated":1612175388,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"926","consumeType":"every","itemName":"热销商品4","itemBn":"S6010FEBBF375A","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"promotion_activity":[{"promotion_id":"97","tag_type":"normal","activity_price":"9900","start_time":"1612175431","end_time":"1614700799","item_id":"926"}],"activity_price":"9900","tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"977","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"977","brand_id":235,"item_name":"热销商品5","item_unit":"","item_bn":"S601A66F85ABDC","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"977","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1612343032,"updated":1612343032,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"977","consumeType":"every","itemName":"热销商品5","itemBn":"S601A66F85ABDC","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"978","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"978","brand_id":235,"item_name":"热销商品6","item_unit":"","item_bn":"S601A6700D8CC9","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"978","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1612343040,"updated":1612343040,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"978","consumeType":"every","itemName":"热销商品6","itemBn":"S601A6700D8CC9","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"},{"item_id":"979","item_type":"normal","consume_type":"every","is_show_specimg":false,"store":100,"barcode":"","sales":null,"approve_status":"onsale","rebate":0,"rebate_conf":[],"cost_price":0,"is_point":null,"point":0,"item_source":"mall","goods_id":"979","brand_id":235,"item_name":"热销商品7","item_unit":"","item_bn":"S601A67091274A","brief":"","price":9900,"market_price":0,"special_type":"normal","goods_function":null,"goods_series":null,"volume":null,"goods_color":null,"goods_brand":null,"item_address_province":"","item_address_city":"","regions_id":null,"brand_logo":null,"sort":0,"templates_id":104,"is_default":true,"nospec":true,"default_item_id":"979","pics":["https:\/\/preissue-b-img-cdn.yuanyuanke.cn\/image\/565\/2021\/02\/01\/93836f320e9be242d61b24201bfafc22KVtulZRBQ2S8jJD2RdBuF4egCapeOf5Q"],"distributor_id":0,"company_id":"565","enable_agreement":false,"date_type":"","item_category":"633","rebate_type":"default","weight":0,"begin_date":0,"end_date":0,"fixed_term":0,"tax_rate":0,"created":1612343049,"updated":1612343049,"video_type":"local","videos":"","video_pic_url":null,"audit_status":"approved","audit_reason":null,"is_gift":false,"is_package":false,"profit_type":0,"profit_fee":0,"is_profit":false,"crossborder_tax_rate":"","origincountry_id":"0","taxstrategy_id":"0","taxation_num":1,"type":0,"item_en_name":"","tdk_content":"{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}","itemId":"979","consumeType":"every","itemName":"热销商品7","itemBn":"S601A67091274A","companyId":"565","item_main_cat_id":"633","item_cat_id":["634"],"type_labels":[],"tagList":[],"itemMainCatName":"热销Top10","itemCatName":["[热销商品]"],"linkPage":"goods","linkPageLabel":"商品"}]}],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":true,"heightShow":true,"widthDisabled":true,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":0,"y":0,"pageWidth":1190,"imgWidth":200,"imgHeight":200,"uuid":"9e70b045ce"}';
        $jsonTemplateContent = json_decode($templateContent, true);
        if (isset($jsonTemplateContent['name']) && $jsonTemplateContent['name'] == '商品橱窗') {
            $_filter['company_id'] = $companyId;
            $itemsService = new ItemsService();
            $dataList = $itemsService->list($_filter, [], 100, 1);
            foreach ($dataList['list'] as $key => $itemsVal) {
                $jsonTemplateContent['data'][0]['data'][$key]['item_id'] = $itemsVal['item_id'];
                $jsonTemplateContent['data'][0]['data'][$key]['company_id'] = $itemsVal['company_id'];
                $jsonTemplateContent['data'][0]['data'][$key]['item_category'] = $itemsVal['item_category'];
                $jsonTemplateContent['data'][0]['data'][$key]['itemId'] = $itemsVal['item_id'];
                $jsonTemplateContent['data'][0]['data'][$key]['companyId'] = $itemsVal['company_id'];
            }

            $templateContent = json_encode($jsonTemplateContent, JSON_UNESCAPED_UNICODE);
        }
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => $newThemePcTemplateId,
            'name' => '',
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        //自由面板
        $templateContent = '{"name":"自由面板","alias":"自由面板","text":"自由面板","type":"W0006","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":1190,"height":400,"lineHeight":20,"fontSize":12,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":false,"heightShow":true,"widthDisabled":false,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":0,"y":0,"pageWidth":1190,"comptyle":"body","childWidgets":[{"name":"文本","alias":"文本","text":"","type":"W0011","isContainer":false,"isUpload":false,"hasGuide":true,"isChild":true,"dragable":true,"resizable":true,"left":50,"top":0,"z":0,"width":20,"height":4,"lineHeight":20,"fontSize":14,"fontWeight":"normal","fontStyle":"normal","textDecoration":"none","color":"#000000ff","textAlign":"left","marginTop":0,"marginRight":"auto","marginBottom":0,"marginLeft":"auto","paddingTop":0,"paddingRight":0,"paddingBottom":0,"paddingLeft":0,"borderTopWidth":0,"borderTopColor":"#ffffffff","borderRightWidth":0,"borderRightColor":"#ffffffff","borderBottomWidth":0,"borderBottomColor":"#ffffffff","borderLeftWidth":0,"borderLeftColor":"#ffffffff","foregroundColor":"#ffffffff","backgroundColor":"#ffffffff","backgroundImage":"","textShadow":"","textShadowColor":"#000000ff","textShadowH":0,"textShadowV":0,"textShadowBlur":0,"boxShadow":"","boxShadowColor":"#000000ff","boxShadowH":0,"boxShadowV":1,"boxShadowBlur":1,"borderStyle":"normal","borderColor":"#000000ff","borderWidth":1,"borderRadius":0,"borderEffect":"none","imgUrl":"","data":[],"opacity":1,"proportion":false,"proportionDisabled":true,"proportionShow":false,"heightDisabled":true,"heightShow":true,"widthDisabled":true,"href":[],"phoneNumber":"","clipboard":"","belong":"page","resizableAxis":"x","x":575,"y":164,"pageWidth":1190,"uuid":"522e9da949"}],"uuid":"715c9a196d"}';
        $data = [
            'company_id' => $companyId,
            'theme_pc_template_id' => $newThemePcTemplateId,
            'name' => '',
            'params' => $templateContent
        ];
        $themePcTemplateContentRepository->create($data);

        return true;
    }

    /**
     * @param $params
     * @param $companyId
     * @return array
     *
     * 替换微信小程序模板 goodsScroll 数据
     */
    private function wechatTemplateGoodsScroll($params, $companyId, $distributor_id = 0)
    {
        $_type = $params['config']['type'];
        $_filter['distributor_id'] = $distributor_id;
        //单商品
        if ($_type == 'goods') {
            $_filter['company_id'] = $companyId;
            $itemsService = new ItemsService();
            $itemsData = $itemsService->getInfo($_filter);
            $items_data[] = [
                'imgUrl' => $itemsData['pics'][0] ?? '',
                'title' => $itemsData['item_name'],
                'goodsId' => $itemsData['goods_id'],
                'brand' => $itemsData['brand_id'],
                'price' => $itemsData['price'],
                'distributor_id' => $itemsData['distributor_id'],
            ];

            $params['data'] = $items_data;
            $configParams = $params;

            return $configParams;
        }

        //秒杀活动
        if ($_type == 'seckill') {
            //查找秒杀活动
            $seckillActivityRepository = app('registry')->getManager('default')->getRepository(SeckillActivity::class);
            $_filter['company_id'] = $companyId;
            $seckillActivity = $seckillActivityRepository->getInfo($_filter);

            //替换秒杀活动id
            $seckillActivitySeckillId = $seckillActivity['seckill_id'];
            $params['config']['seckillId'] = $seckillActivitySeckillId;

            //查找秒杀活动商品
            $seckillRelGoodsRepository = app('registry')->getManager('default')->getRepository(SeckillRelGoods::class);
            $goods_filter = [
                'seckill_id' => $seckillActivitySeckillId
            ];
            $seckillRelGoodList = $seckillRelGoodsRepository->lists($goods_filter);
            if (empty($seckillRelGoodList)) {
                return [];
            }

            $items_data = [];
            foreach ($seckillRelGoodList['list'] as $_v) {
                $itemsService = new ItemsService();
                $itemsData = $itemsService->getInfo(['item_id' => $_v['item_id']]);
                app('log')->info('$_v'.var_export($_v, 1));
                app('log')->info('$itemsData'.var_export($itemsData, 1));
                $items_data[] = [
                    'act_price' => $_v['activity_price'],
                    'title' => $_v['item_title'],
                    'imgUrl' => $itemsData['pics'][0] ?? '',
                    'price' => $itemsData['price'],
                    'goodsId' => $itemsData['goods_id']
                ];
            }

            $params['data'] = $items_data;
            $configParams = $params;

            return $configParams;
        }

        return [];
    }

    /**
     * @param $params
     * @param $companyId
     *
     * 替换微信小程序模板 goodsGrid 数据
     */
    private function wechatTemplateGoodsGrid($params, $companyId, $distributor_id = 0)
    {
        $items_num = count($params['data']);
        $_filter['company_id'] = $companyId;
        $_filter['distributor_id'] = $distributor_id;
        $itemsService = new ItemsService();
        $datalist = $itemsService->getLists($_filter, '*', 1, $items_num);
        if (empty($datalist)) {
            return [];
        }
        $items_data = [];
        foreach ($datalist as $_v) {
            $_v['pics'] = json_decode($_v['pics'], true);
            $items_data[] = [
              //  'imgUrl' => $_v['pics'][0] ?? '',
                'imgUrl' => $_v['pics'][0] ?? '',
                'title' => $_v['item_name'],
                'goodsId' => $_v['goods_id'],
                'brand' => $_v['brand_id'],
                'price' => $_v['price'],
                'distributor_id' => $_v['distributor_id'],
            ];
        }

        $params['data'] = $items_data;
        $configParams = $params;

        return $configParams;
    }

    private function wechatTemplateGoodsGridTab($params, $companyId, $distributor_id = 0)
    {
        $items_num = count($params['data']);
        $_filter['company_id'] = $companyId;
        $_filter['distributor_id'] = $distributor_id;
        $itemsService = new ItemsService();
        $datalist = $itemsService->getLists($_filter, '*', 1, $items_num, );
        if (empty($datalist)) {
            return [];
        }
        $items_data = [];
        foreach ($datalist as $_v) {
            $_v['pics'] = json_decode($_v['pics'], true);
            $items_data[] = [
                //  'imgUrl' => $_v['pics'][0] ?? '',
                'imgUrl' => $_v['pics'][0] ?? '',
                'title' => $_v['item_name'],
                'goodsId' => $_v['goods_id'],
                'brand' => $_v['brand_id'],
                'price' => $_v['price'],
                'distributor_id' => $_v['distributor_id'],
            ];
        }

        $params['data'] = $items_data;
        $params['list'][0]['goodsList'] = $items_data;
        $configParams = $params;
        return $configParams;
    }

    /**
     * 替换店铺 store 数据
     * @param $params
     * @param $companyId
     * @param int $distributor_id
     * @return mixed
     */
    private function wechatTemplateStore($params, $companyId)
    {
        $store_num = count($params['data']);
        $_filter['company_id'] = $companyId;
        $distributorService = new DistributorService();
        $store = $distributorService->getDistributorOriginalList($_filter, 1, $store_num);
        $store_data = [];
        $itemsService = new ItemsService();
        foreach ($store['list'] as $key => $value) {
            $store_item = [];
            $store_item['id'] = $value['distributor_id'];
            $store_item['name'] = $value['name'];
            $store_item['logo'] = $value['logo'];
            $items_num = count($params['data'][0]['items']);
            $_filter['distributor_id'] = $value['distributor_id'];
            $datalist = $itemsService->getLists($_filter, '*', 1, $items_num);
            $items_data = [];
            if (!empty($datalist)) {
                foreach ($datalist as $_v) {
                    $items_data[] = [
                        'imgUrl' => $_v['pics'][0] ?? '',
                        'title' => $_v['item_name'],
                        'goodsId' => $_v['goods_id'],
                        'brand' => $_v['brand_id'],
                        'price' => $_v['price'],
                        'distributor_id' => $_v['distributor_id'],
                    ];
                }
            }
            $store_item['items'] = $items_data;
            $store_data[] = $store_item;
        }
        $params['data'] = $store_data;
        $configParams = $params;
        return $configParams;
    }




    /**
    * 新增模板
    * @param $companyId
    * @return bool
    */
    private function addWechatWeappTemplat($companyId)
    {
        $data = [
            'company_id' => $companyId,
            'template_name' => config('wechat.default_weishop_temp'),
            'status' => 'succ',
            'money' => 0,
        ];

        $templateService = new TemplateService();
        $templateService->openTemplate($data);
        return true;
    }

    // 初始化商户数据
    public function addMerchant($companyId)
    {
        $merchant_type = $this->addMerchantType($companyId);
        $merchantApply = $this->addMerchantApply($companyId, $merchant_type);
        $merchantData = $this->addMerchantInfo($companyId, $merchantApply);
        $this->addMerchantOperator($companyId, $merchantApply, $merchantData);
        $this->addDistributor($companyId, $merchantData['id']);
        return true;
    }

    /**
     * 增加商户类型
     * @param $companyId
     * @return mixed
     */
    private function addMerchantType($companyId)
    {
        $merchantSettingService = new MerchantSettingService();
        $params = [
            'name' => '热销',
            'parent_id' => 0,
            'is_show' => 1
        ];
        $merchantSettingService->createMerchantType($companyId, $params);
        $typeInfo = $merchantSettingService->getInfo(['company_id' => $companyId,'name' => '热销','parent_id' => 0]);
        $params = [
            'name' => '热销',
            'parent_id' => $typeInfo['id'],
            'is_show' => 1
        ];
        $merchantSettingService->createMerchantType($companyId, $params);
        $typeInfo = $merchantSettingService->getInfo(['company_id' => $companyId,'name' => '热销','parent_id' => $typeInfo['id']]);
        return $typeInfo['id'];
    }

    /**
     * 添加商户申请数据
     * @param $companyId
     * @return mixed
     */
    private function addMerchantApply($companyId, $merchant_type)
    {
        $data['company_id'] = $companyId;
        $data['mobile'] = '13816929962';
        $data['is_agree_agreement'] = 1;
        $data['merchant_type_id'] = $merchant_type;
        $data['settled_type'] = 'soletrader';
        $data['merchant_name'] = '商派';
        $data['social_credit_code_id'] = '111122223334445555';
        $data['province'] = '上海市';
        $data['city'] = '上海市';
        $data['area'] = '徐汇区';
        $data['regions_id'] = '["310000","310100","310104"]';
        $data['address'] = '上海徐汇区';
        $data['legal_name'] = '丽丽';
        $data['legal_cert_id'] = '111122223334445555';
        $data['legal_mobile'] = '13816929962';
        $data['bank_acct_type'] = 1;
        $data['card_id_mask'] = '111122223334445555';
        $data['bank_name'] = '招商银行';
        $data['bank_mobile'] = '';
        $data['license_url'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv';
        $data['legal_certid_front_url'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv';
        $data['legal_cert_id_back_url'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv';
        $data['bank_card_front_url'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv';
        $data['audit_status'] = 2;
        $data['source'] = 'admin';
        $data['audit_goods'] = 1;
        $data['disabled'] = 0;
        $merchantSettlementApplyService = new MerchantSettlementApplyService();
        $merchantSettlementApplyInfo = $merchantSettlementApplyService->getInfo(['company_id' => $companyId,'mobile' => $data['mobile']]);
        if (!empty($merchantSettlementApplyInfo)) {
            throw new ResourceException('该商户已存在');
        }
        $merchantApply = $merchantSettlementApplyService->create($data);
        return $merchantApply;
    }

    /**
     * 添加商户数据
     * @param $companyId
     * @param $merchantApply
     * @return mixed
     */
    private function addMerchantInfo($companyId, $merchantApply)
    {
        $data['merchant_name'] = $merchantApply['merchant_name'];
        $data['company_id'] = $companyId;
        $data['merchant_type_id'] = $merchantApply['merchant_type_id'];
        $data['settled_type'] = $merchantApply['settled_type'];
        $data['social_credit_code_id'] = $merchantApply['social_credit_code_id'];
        $data['address'] = $merchantApply['address'];
        $data['legal_name'] = $merchantApply['legal_name'];
        $data['legal_cert_id'] = $merchantApply['legal_cert_id'];
        $data['legal_mobile'] = $merchantApply['legal_mobile'];
        $data['bank_acct_type'] = $merchantApply['bank_acct_type'];
        $data['card_id_mask'] = $merchantApply['card_id_mask'];
        $data['bank_name'] = $merchantApply['bank_name'];
        $data['license_url'] = $merchantApply['license_url'];
        $data['legal_certid_front_url'] = $merchantApply['legal_certid_front_url'];
        $data['legal_cert_id_back_url'] = $merchantApply['legal_cert_id_back_url'];
        $data['bank_card_front_url'] = $merchantApply['bank_card_front_url'];
        $data['contract_url'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv';
        $data['audit_goods'] = $merchantApply['audit_goods'];
        $data['disabled'] = $merchantApply['disabled'];
        $data['settled_succ_sendsms'] = 1;
        $data['province'] = $merchantApply['province'];
        $data['city'] = $merchantApply['city'];
        $data['area'] = $merchantApply['area'];
        $data['regions_id'] = $merchantApply['regions_id'];
        $data['source'] = $merchantApply['source'];
        $data['email'] = '';
        $data['settlement_apply_id'] = $merchantApply['id'];
        $merchantService = new MerchantService();
        $merchantInfo = $merchantService->getInfo(['settlement_apply_id' => $merchantApply['id']]);
        if (!empty($merchantInfo)) {
            throw new ResourceException('该商户已存在');
        }
        $merchantData = $merchantService->create($data);
        return $merchantData;
    }

    /**
     * 添加商户管理员
     * @param $companyId
     * @param $merchantApply
     * @param $merchantData
     * @return mixed
     */
    private function addMerchantOperator($companyId, $merchantApply, $merchantData)
    {
        $data['mobile'] = $merchantApply['mobile'];
        $data['password'] = password_hash($merchantApply['mobile'], PASSWORD_DEFAULT);
        $data['operator_type'] = 'merchant';
        $data['login_name'] = $merchantApply['mobile'];
        $data['company_id'] = $companyId;
        $data['merchant_id'] = $merchantData['id'];
        $data['is_merchant_main'] = '1';
        $operatorsService = new OperatorsService();
        $operatorInfo = $operatorsService->getInfo(['company_id' => $companyId,'merchant_id' => $merchantData['id'],'mobile' => $merchantApply['mobile'],'operator_type' => 'merchant']);
        if (!empty($operatorInfo)) {
            throw new ResourceException('该商户管理员已存在');
        }
        $operatorData = $operatorsService->create($data);
        return $operatorData;
    }

    /**
     * 初始化店铺相关数据
     * @param $company_id
     * @param $merchant_id
     */
    private function addDistributor($company_id, $merchant_id)
    {
        $distributor_id = $this->addDistributorInfo($company_id, $merchant_id);
        $this->addDistributorManager($company_id, $merchant_id, $distributor_id);
        $this->addDistributorCatetory($company_id, $distributor_id);
        $this->addDistributorGoodsBrand($company_id, $distributor_id);
        $this->addDistributorShippingTemplates($company_id, $distributor_id);
        $this->addDistributorGoods($company_id, $distributor_id);
        $this->addDistributorWeiShopTemplate($company_id, $distributor_id);
    }

    /**
     * 添加店铺
     * @param $company_id
     * @param $merchant_id
     * @return mixed
     */
    private function addDistributorInfo($company_id, $merchant_id)
    {
        $data['mobile'] = '13816929962';
        $data['address'] = '上海商派网络科技有限公司';
        $data['name'] = 'demo';
        $data['is_valid'] = true;
        $data['province'] = '上海市';
        $data['city'] = '上海市';
        $data['area'] = '徐汇区';
        $data['regions_id'] = '["310000","310100","310104"]';
        $data['regions'] = '["\u4e0a\u6d77\u5e02","\u4e0a\u6d77\u5e02","\u5f90\u6c47\u533a"]';
        $data['contact'] = '丽丽';
        $data['logo'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bxZsiiZARkIXx70VrEOdbVANzU96nH7hU';
        $data['banner'] = 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bcU5YivJFVo7QjW0YbVAHlIfg3I4xFQ0w';
        $data['lng'] = '121.418292';
        $data['lat'] = '31.17527';
        $data['hour'] = '08:00-21:00';
        $data['is_delivery'] = '1';
        $data['shop_code'] = '0001';
        $data['source_from'] = '1';
        $data['is_distributor'] = '1';
        $data['is_domestic'] = '1';
        $data['is_direct_store'] = '1';
        $data['merchant_id'] = $merchant_id;
        $data['company_id'] = $company_id;
        $data['distribution_type'] = 1;
        $data['is_dada'] = 0;
        $data['dada_shop_create'] = 0;
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoSimple(['company_id' => $company_id,'merchant_id' => $merchant_id,'mobile' => $data['mobile']]);
        if (!empty($distributorInfo)) {
            throw new ResourceException('该店铺已存在');
        }
        $distributor = $distributorService->create($data);
        return $distributor['distributor_id'];
    }

    /**
     * 添加店铺管理员
     * @param $company_id
     * @param $merchant_id
     * @param $distributor_id
     */
    private function addDistributorManager($company_id, $merchant_id, $distributor_id)
    {
        $data = [
           'mobile' => '15026787264',
           'password' => password_hash('15026787264', PASSWORD_DEFAULT),
           'operator_type' => 'distributor',
           'company_id' => $company_id,
           'distributor_ids' => json_encode([['name' => 'demo','distributor_id' => $distributor_id]]),
           'username' => '15026787264',
           'login_name' => '15026787264',
           'merchant_id' => $merchant_id
       ];
        $operator = new OperatorsService();
        $operatorInfo = $operator->getInfo(['company_id' => $company_id,'merchant_id' => $merchant_id,'operator_type' => 'distributor','mobile' => '15026787264']);
        if (!empty($operatorInfo)) {
            throw new ResourceException('该店铺管理员已存在');
        }
        $result = $operator->create($data);
    }

    /**
     * 添加店铺商品分类
     * @param $company_id
     * @param $distributor_id
     */
    private function addDistributorCatetory($company_id, $distributor_id)
    {
        $params['form'] = '[{"category_name":"热销","sort":0,"category_level":1,"children":[{"category_name":"热销","sort":0,"category_level":2,"children":[{"category_name":"热销","sort":0,"category_level":3,"children":[],"created":-1,"image_url":"","goods_params":[]}],"created":-1,"image_url":"","goods_params":[]}],"created":-1,"image_url":"","goods_params":[]}]';
        $params['form'] = json_decode($params['form'], true);
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->saveItemsCategory($params['form'], $company_id, $distributor_id);
    }

    /**
     * 添加店铺商品品牌
     * @param $companyId
     * @param $distributor_id
     */
    private function addDistributorGoodsBrand($companyId, $distributor_id)
    {
        $params = '{
            "image_url": "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH",
            "attribute_id": "",
            "attribute_name": "ECshopX",
            "attribute_type": "brand"
        }';
        $input = json_decode($params, true);
        $input['company_id'] = $companyId;
        $input['distributor_id'] = $distributor_id;
        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->createAttr($input);
    }

    /**
     * 添加店铺运费模板
     * @param $companyId
     * @param $distributor_id
     */
    private function addDistributorShippingTemplates($companyId, $distributor_id)
    {
        $shippingTemplatesServices = new ShippingTemplatesService();
        $params = '{
                "name": "包邮",
                "status": "1",
                "is_free": "1",
                "fee_conf": [
                    {
                        "add_fee": "",
                        "start_fee": "",
                        "add_standard": "",
                        "start_standard": ""
                    }
                ],
                "free_conf": [
                    {
                        "area": "0",
                        "upmoney": "",
                        "freetype": "1",
                        "inweight": ""
                    }
                ],
                "valuation": "1"
            }';
        $params = json_decode($params, 1);
        $params['fee_conf'] = json_encode($params['fee_conf']);
        $params['free_conf'] = json_encode($params['free_conf']);
        $params['company_id'] = $companyId;
        $params['distributor_id'] = $distributor_id;
        $params['nopost_conf'] = json_encode([]);
        $result = $shippingTemplatesServices->createShippingTemplates($params);
    }
    /**
     * 添加店铺商品
     * @param $companyId
     * @param $distributor_id
     * @throws \Exception
     */
    public function addDistributorGoods($companyId, $distributor_id)
    {
        // 获取demo数据最后一个商品主类目id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('category_id')
            ->from('items_category')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('category_level', 3))
            ->andWhere($qb->expr()->eq('is_main_category', 1));
        $goodsMainCategoryId = $qb->execute()->fetchColumn();
        // 获取参数id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
            ->from('items_attributes')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_spec')));
        $goodsSpecId = $qb->execute()->fetchColumn();
        // 获取规格id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
            ->from('items_attributes')
            ->andWhere($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('item_params')));
        $goodsParamsId = $qb->execute()->fetchColumn();
        // 获取品牌id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('attribute_id')
            ->from('items_attributes')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->eq('attribute_type', $qb->expr()->literal('brand')));
        $goodsBrandId = $qb->execute()->fetchColumn();
        // 获取商品分类id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('category_id')
            ->from('items_category')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->eq('is_main_category', 0));
        $goodsCategoryId = $qb->execute()->fetchColumn();

        // 获取运费模板id
        $conn = app('registry')->getConnection('default');
        $qb = $conn->createQueryBuilder();
        $qb->select('template_id')
            ->from('shipping_templates')
            ->where($qb->expr()->eq('company_id', $companyId))
            ->andWhere($qb->expr()->eq('distributor_id', $distributor_id))
            ->andWhere($qb->expr()->eq('status', 1));
        $shippingTemplateId = $qb->execute()->fetchColumn();

        $itemsService = new ItemsService();

        $params1 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "0.01",
            "store": "1",
            "nospec": "true",
            "rebate": "",
            "videos": "",
            "volume": "",
            "weight": "",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61E14144A5220",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品1",
            "item_type": "normal",
            "item_unit": "",
            "point_num": "0",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "999",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params1 = json_decode($params1, true);

        $params1['company_id'] = $companyId;
        $params1['distributor_id'] = $distributor_id;
        $params1['brand_id'] = $goodsBrandId;
        $params1['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params1['templates_id'] = $shippingTemplateId;
        $params1['item_category'] = [$goodsCategoryId];
        $params1['item_main_cat_id'] = $goodsMainCategoryId;

        $params2 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "99.9",
            "store": "1",
            "nospec": "true",
            "rebate": "0",
            "videos": "",
            "volume": "",
            "weight": "0",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61E14152D2728",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品2",
            "item_type": "normal",
            "item_unit": "",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "999",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params2 = json_decode($params2, true);

        $params2['company_id'] = $companyId;
        $params2['distributor_id'] = $distributor_id;
        $params2['brand_id'] = $goodsBrandId;
        $params2['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params2['templates_id'] = $shippingTemplateId;
        $params2['item_category'] = [$goodsCategoryId];
        $params2['item_main_cat_id'] = $goodsMainCategoryId;

        $params3 = '{
            "pics": [
                "https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8"
            ],
            "sort": "1",
            "type": "0",
            "brief": "",
            "intro": "",
            "price": "99.9",
            "store": "1",
            "nospec": "true",
            "rebate": "0",
            "videos": "",
            "volume": "",
            "weight": "0",
            "barcode": "",
            "is_gift": "false",
            "item_bn": "S61E1415BB5E7C",
            "item_id": "",
            "brand_id": "235",
            "tax_rate": "0",
            "is_profit": "false",
            "item_name": "热销商品3",
            "item_type": "normal",
            "item_unit": "",
            "cost_price": "0",
            "spec_items": "null",
            "videos_url": "",
            "item_params": [
                {
                    "attribute_id": "234",
                    "attribute_value_id": "",
                    "attribute_value_name": ""
                }
            ],
            "item_source": "mall",
            "spec_images": "[]",
            "tdk_content": "{\"title\":\"\",\"mate_description\":\"\",\"mate_keywords\":\"\"}",
            "market_price": "999",
            "point_access": "order",
            "special_type": "normal",
            "taxation_num": "1",
            "templates_id": "104",
            "item_category": [
                "634"
            ],
            "approve_status": "onsale",
            "taxstrategy_id": "0",
            "is_show_specimg": "false",
            "item_main_cat_id": "633",
            "origincountry_id": "0",
            "crossborder_tax_rate": ""
        }';
        $params3 = json_decode($params3, true);

        $params3['company_id'] = $companyId;
        $params3['distributor_id'] = $distributor_id;
        $params3['brand_id'] = $goodsBrandId;
        $params3['item_params'][0]['attribute_id'] = $goodsParamsId;
        $params3['templates_id'] = $shippingTemplateId;
        $params3['item_category'] = [$goodsCategoryId];
        $params3['item_main_cat_id'] = $goodsMainCategoryId;
        $result = $itemsService->addItems($params1);
        $this->updateGoodsStatus($result);
        $result = $itemsService->addItems($params2);
        $this->updateGoodsStatus($result);
        $result = $itemsService->addItems($params3);
        $this->updateGoodsStatus($result);
    }

    /**
     * 更新店铺商品审核状态
     * @param $data
     */
    private function updateGoodsStatus($data)
    {
        $filter = [
            'company_id' => $data['company_id'],
            'item_id' => $data['item_id'],
        ];
        $param = ['audit_status' => 'approved'];
        $itemsService = new ItemsService();
        $itemsService->updateBy($filter, $param);
    }

    /**
     * 添加店铺小程序模板
     * @param $companyId
     * @param $distributor_id
     * @return bool
     * @throws \Throwable
     */
    private function addDistributorWeiShopTemplate($companyId, $distributor_id)
    {
        //商户初始化微信模板数据创建
        $params = [
            'company_id' => $companyId,
            'distributor_id' => $distributor_id,
            'template_title' => '店铺',
            'template_name' => 'yykweishop',
            'template_pic' => 'https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/09/27/07fcd2f4c93e1b1a27ab843a3bc4bef4hzBrebpk5dqu7Lp2paK94gwURNeQN4JH',
            'template_type' => 0,
            'status' => 1,
            'weapp_pages' => 'index',
        ];

        $pagesTemplateServices = new PagesTemplateServices();
        $result = $pagesTemplateServices->create($params);

        $new_pagesTemplateId = $result['pages_template_id'];

        //查找模板商户微信模板内容
        $templateName = 'yykweishop';
        $pageName = 'index';
        $configName = '';
        $version = 'v1.0.2';

        $entityRepository = app('registry')->getManager('default')->getRepository(WeappSetting::class);

        $name = 'slider';
        $configParams = 'a:6:{s:4:"name";s:6:"slider";s:4:"base";a:3:{s:5:"title";s:0:"";s:8:"subtitle";s:0:"";s:6:"padded";b:1;}s:6:"config";a:12:{s:7:"current";i:0;s:8:"interval";i:3000;s:7:"spacing";i:0;s:3:"dot";b:0;s:11:"dotLocation";s:5:"right";s:8:"dotColor";s:4:"dark";s:5:"shape";s:6:"circle";s:11:"numNavShape";s:4:"rect";s:8:"dotCover";b:1;s:7:"rounded";b:0;s:6:"padded";b:0;s:7:"content";b:1;}s:4:"data";a:2:{i:0;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/12/30/b3942b258e15e392d4e9dabe4028d9327y5RmsZRNBcqwcctJO7ZV7WnAXjqdqMY";s:8:"linkPage";s:0:"";s:7:"content";s:0:"";s:2:"id";s:0:"";s:8:"template";s:3:"one";}i:1;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2021/12/30/b3942b258e15e392d4e9dabe4028d932X3Oy2VaRoFQ6LQ6MwFIMcl6THK90vyCk";s:8:"linkPage";s:0:"";s:7:"content";s:0:"";s:2:"id";s:0:"";s:8:"template";s:3:"one";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);

        $name = 'navigation';
        $configParams = 'a:5:{s:4:"name";s:10:"navigation";s:4:"base";a:1:{s:6:"padded";b:1;}s:4:"data";a:5:{i:0;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:12:"基础护肤";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:1;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:12:"彩妆香水";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:2;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:12:"营养保健";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:3;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:12:"满减优惠";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:4;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:12:"分享拼单";s:5:"title";s:0:"";s:2:"id";s:0:"";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);

        $name = 'goodsScroll';
        $configParams = 'a:6:{s:4:"name";s:11:"goodsScroll";s:4:"base";a:4:{s:5:"title";s:12:"当地必买";s:8:"subtitle";s:27:"看看大家都在买什么";s:6:"padded";b:1;s:13:"backgroundImg";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";}s:6:"config";a:5:{s:9:"seckillId";s:0:"";s:11:"leaderboard";b:1;s:9:"showPrice";b:1;s:4:"type";s:5:"goods";s:8:"moreLink";a:3:{s:2:"id";s:0:"";s:5:"title";s:0:"";s:8:"linkPage";s:0:"";}}s:4:"data";a:3:{i:0;a:6:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8";s:5:"title";s:13:"热销商品3";s:7:"goodsId";s:4:"1477";s:5:"brand";N;s:5:"price";i:9990;s:14:"distributor_id";i:160;}i:1;a:6:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8";s:5:"title";s:13:"热销商品2";s:7:"goodsId";s:4:"1476";s:5:"brand";N;s:5:"price";i:9990;s:14:"distributor_id";i:160;}i:2;a:6:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/14/eef5275c1beb42dc8d58c6900e5ed086AqqNrLtjoEt9pPEcNNhHI19hTpKWhaZ8";s:5:"title";s:13:"热销商品1";s:7:"goodsId";s:4:"1475";s:5:"brand";N;s:5:"price";i:1;s:14:"distributor_id";i:160;}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $configParams = $this->wechatTemplateGoodsGrid($configParams, $companyId, $distributor_id);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        $name = 'showcase';
        $configParams = 'a:6:{s:4:"name";s:8:"showcase";s:4:"base";a:3:{s:5:"title";s:12:"旅游资讯";s:8:"subtitle";s:0:"";s:6:"padded";b:1;}s:6:"config";a:1:{s:5:"style";i:1;}s:4:"data";a:3:{i:0;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:0:"";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:1;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:0:"";s:5:"title";s:0:"";s:2:"id";s:0:"";}i:2;a:5:{s:6:"imgUrl";s:125:"https://preissue-b-img-cdn.yuanyuanke.cn/image/42/2022/01/12/16c76febe685d4249e419259ad979f9bXVvstgl3zcR3zTNn5dYee1b9CzfkxONv";s:8:"linkPage";s:0:"";s:7:"content";s:0:"";s:5:"title";s:0:"";s:2:"id";s:0:"";}}s:7:"user_id";i:0;s:14:"distributor_id";i:0;}';
        $configParams = unserialize($configParams);
        $entityRepository->setParams($companyId, $templateName, $pageName, $name, $configParams, $version, $new_pagesTemplateId);


        return true;
    }
}
