<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */

$api->version('v1', function($api) {
    // 服务类商品相关信息
    $api->group(['namespace' => 'GoodsBundle\Http\Api\V1\Action', 'middleware' => ['api.auth', 'activated', 'shoplog'], 'providers' => 'jwt'], function($api) {
        // 数值属性
        $api->post('/goods/servicelabels', ['name' => '添加数值属性', 'as' => 'goods.servicelabels.create', 'uses' => 'ServiceLabels@createServiceLabels']);
        $api->get('/goods/servicelabels', ['name' => '获取数值属性列表', 'as' => 'goods.servicelabels.lists', 'uses' => 'ServiceLabels@getServiceLabelsList']);
        $api->get('/goods/servicelabels/{label_id}', ['name' => '获取数值属性详情', 'as' => 'goods.servicelabels.detail', 'uses' => 'ServiceLabels@getServiceLabelsDetail']);
        $api->delete('/goods/servicelabels/{label_id}', ['name' => '删除数值属性', 'as' => 'goods.servicelabels.delete', 'uses' => 'ServiceLabels@deleteServiceLabels']);
        $api->put('/goods/servicelabels/{label_id}', ['name' => '更新数值属性', 'as' => 'goods.servicelabels.update', 'uses' => 'ServiceLabels@updateServiceLabels']);

        // 商品
        $api->post('/goods/set_commission_ratio', ['name' => '设置商品佣金费率', 'as' => 'goods.items.set_commission_ratio', 'uses' => 'Items@setCommissionRatio']);
        $api->put('/goods/audit/items', ['name' => '商品审核', 'as' => 'goods.items.audit', 'uses' => 'Items@auditItems']);
        $api->post('/goods/items', ['name' => '添加商品', 'as' => 'goods.items.create', 'uses' => 'Items@createItems']);
        $api->post('/goods/setItemsTemplate', ['name' => '更新商品运费模板', 'as' => 'goods.items.templates_change', 'uses' => 'Items@setItemsTemplate']);
        $api->post('/goods/setItemsCategory', ['name' => '更新商品分类', 'as' => 'goods.items.category_change', 'uses' => 'Items@setItemsCategory']);
        $api->get('/goods/items', ['name' => '获取商品列表', 'as' => 'goods.items.lists', 'uses' => 'Items@getItemsList']);
        $api->get('/goods/items/onsale', ['name' => '获取可销售的商品列表', 'as' => 'goods.items.onsale.lists', 'uses' => 'Items@getOnsaleItemsList']);
        $api->get('/goods/sku', ['name' => '获取商品列表', 'as' => 'goods.sku.lists', 'uses' => 'Items@getSkuList']);
        $api->get('/goods/items/{item_id}', ['name' => '获取商品详情', 'as' => 'goods.items.detail', 'uses' => 'Items@getItemsDetail']);
        $api->delete('/goods/items/{item_id}', ['name' => '删除商品', 'as' => 'goods.items.delete', 'uses' => 'Items@deleteItems']);
        $api->delete('/goods/items/{item_id}/response', ['name' => '删除商品', 'as' => 'goods.items.delete.response', 'uses' => 'Items@deleteItemsResponseData']);
        $api->put('/goods/items/{item_id}', ['name' => '更新商品', 'as' => 'goods.items.update', 'uses' => 'Items@updateItems']);
        $api->get('/goods/distributionGoodsWxaCodeStream', ['name' => '获取商品分销二维码', 'as' => 'goods.items.distributiongoodswxacode', 'uses' => 'Items@getDistributionGoodsWxaCodeStream']);
        $api->post('/goods/warning_store', ['name' => '设置商品预警库存', 'as' => 'goods.items.warning_store', 'uses' => 'Items@setItemWarningStore']);
        $api->post('/goods/setItemsSort', ['name' => '更新商品排序', 'as' => 'goods.items.sort', 'uses' => 'Items@setItemsSort']);
        $api->get('/goods/epidemicItems/list', ['name' => '疫情商品配置列表', 'as' => 'goods.epidemicItems.list', 'uses' => 'EpidemicItems@epidemicItemsList']);
        $api->get('/goods/epidemicRegister/list', ['name' => '疫情订单登记列表', 'middleware' => ['datapass'], 'as' => 'goods.epidemicRegister.list', 'uses' => 'EpidemicItems@epidemicRegisterList']);
        $api->post('/goods/epidemicRegister/export', ['name' => '疫情防控登记导出', 'middleware' => ['datapass'], 'as' => 'goods.epidemicRegister.export', 'uses' => 'EpidemicItems@exportEpidemicRegisterData']);
        //修改商品库存
        $api->put('/goods/itemstoreupdate', ['name' => '设置商品库存','middleware'=>'activated', 'as' => 'goods.store.upate', 'uses' =>'Items@batchUpdateItemStore']);
        $api->put('/goods/itemstatusupdate', ['name' => '设置商品状态','middleware'=>'activated', 'as' => 'goods.status.upate', 'uses' =>'Items@batchUpdateItemStatus']);
        // 分类
        $api->get('/goods/category', ['name'=> '获取商品分类列表', 'as' => 'goods.category.lists', 'uses' => 'ItemsCategory@getCategory']);
        $api->get('/goods/category/{category_id}', ['name'=> '获取单条分类数据', 'as' => 'goods.category.get', 'uses' => 'ItemsCategory@getCategoryInfo']);
        $api->post('/goods/category', ['name' => '添加分类', 'as' => 'goods.category.create', 'uses' => 'ItemsCategory@createCategory']);
        $api->post('/goods/createcategory', ['name' => '添加分类', 'as' => 'goods.createcategory.create', 'uses' => 'ItemsCategory@createClassification']);
        $api->delete('/goods/category/{category_id}', ['name' => '删除分类', 'as' => 'goods.category.delete', 'uses' => 'ItemsCategory@deleteCategory']);
        $api->put('/goods/category/{category_id}', ['name' => '更新单条分类信息', 'as' => 'goods.category.update', 'uses' => 'ItemsCategory@updateCategory']);

        $api->post('/goods/attributes', ['name' => '新增商品属性', 'as' => 'goods.attributes.add', 'uses' => 'ItemsAttributes@addItemsAttributes']);
        $api->put('/goods/attributes/{attribute_id}', ['name' => '更新商品属性', 'as' => 'goods.attributes.update', 'uses' => 'ItemsAttributes@updateItemsAttributes']);
        $api->get('/goods/attributes', ['name' => '获取商品属性列表', 'as' => 'goods.attributes.list', 'uses' => 'ItemsAttributes@getItemsAttrList']);
        $api->delete('/goods/attributes/{attribute_id}', ['name' => '删除商品属性', 'as' => 'goods.attributes.delete', 'uses' => 'ItemsAttributes@deleteItemsAttributes']);

        //修改商品的价格、库存、上下架状态
        $api->put('/goods/itemsupdate', ['name' => '修改商品价格、库存、上下架状态', 'as' => 'goods.itemsupdate', 'uses' => 'Items@updateItemsPriceStoreStatus']);

        //保存商品会员价
        $api->post('/goods/memberprice/save', ['name'=> '保存商品会员价', 'as' => 'goods.member.price', 'uses' => 'MemberPrice@saveMemberPrice']);
        //获取会员价信息
        $api->get('/goods/memberprice/{item_id}', ['name' => '获取会员价列表', 'as' => 'goods.items.detail', 'uses' => 'MemberPrice@getMemberPriceList']);

        // 添加商品标签
        $api->post('/goods/tag', ['name'=> '添加商品标签','middleware'=>'activated', 'as' => 'goods.tag.add', 'uses' =>'ItemsTags@createTags']);

        // 删除商品标签
        $api->delete('/goods/tag/{tag_id}', ['name'=> '删除商品标签','middleware'=>'activated', 'as' => 'goods.tag.delete', 'uses' =>'ItemsTags@deleteTag']);

        // 更新商品标签
        $api->put('/goods/tag', ['name'=> '更新商品标签','middleware'=>'activated', 'as' => 'goods.tag.update', 'uses' =>'ItemsTags@updateTags']);

        // 获取商品标签列表
        $api->get('/goods/tag', ['name'=> '获取商品标签列表','middleware'=>'activated', 'as' => 'goods.tag.list', 'uses' =>'ItemsTags@getTagsList']);

        // 获取商品标签详情
        $api->get('/goods/tag/{tag_id}', ['name'=> '获取商品标签详情','middleware'=>'activated', 'as' => 'goods.tag.get', 'uses' =>'ItemsTags@getTagsInfo']);

        // 商品关联标签
        $api->post('/goods/reltag', ['name'=> '商品关联标签','middleware'=>'activated', 'as' => 'goods.tag.rel', 'uses' =>'ItemsTags@tagsRelItem']);

        // 获取商品关联标签
        $api->get('/goods/tagsearch', ['name'=> '获取商品关联标签','middleware'=>'activated', 'as' => 'goods.tagsearch', 'uses' =>'ItemsTags@getItemIdsByTagids']);

        $api->post('/goods/rebateconf', ['name'=> '保存商品会员价','middleware'=>'activated', 'as' => 'goods.rebateconf', 'uses' =>'Items@updateItemsRebateConf']);

        $api->post('/goods/exportApiFileName', ['name'=>'导出商品信息', 'as' => 'goods.export', 'uses' =>'ExportItems@exportItemsDataApiReturn']);

        $api->post('/goods/export', ['name'=>'导出商品信息', 'as' => 'goods.export', 'uses' =>'ExportItems@exportItemsData']);
        $api->post('/goods/tag/export', ['name'=>'导出商品标签信息', 'as' => 'goods.tag.export', 'uses' =>'ExportItems@exportItemsTagData']);
        $api->post('/goods/code/export', ['name'=>'导出商品码', 'as' => 'goods.tag.export', 'uses' =>'ExportItems@exportItemsCodeData']);
        $api->get('/goods/goodsbycoupon/{coupon_id}', ['name' => '根据优惠券获取优惠券可使用的商品', 'as' => 'getGoodsByCoupon.get', 'uses' => 'Items@getGoodsByCoupon']);

        // 商品导购分润配置
        $api->get('/goods/profit/{item_id}', ['name'=>'商品导购分润配置获取', 'as' => 'goods.profit.info', 'uses' =>'ItemsProfit@getItemsProfit']);
        $api->post('/goods/profit/save', ['name'=>'商品导购分润配置保存', 'as' => 'goods.profit.save', 'uses' =>'ItemsProfit@saveGoodsProfit']);
        $api->post('/goods/category/profit/save', ['name'=>'商品类目导购分润配置保存', 'as' => 'goods.category.profit.save', 'uses' =>'ItemsCategoryProfit@saveItemsCategoryProfit']);

        //设置关键词搜索
        $api->post('/goods/keywords', ['name' => '设置关键词', 'as' => 'goods.Keywords.set', 'uses' => 'Items@setKeywords']);
        $api->delete('/goods/keywords/{id}', ['name' => '删除关键词', 'as' => 'goods.Keywords.delete', 'uses' => 'Items@delKeywords']);
        $api->get('/goods/keywords', ['name' => '获取关键词', 'as' => 'goods.Keywords.get', 'uses' => 'Items@getKeywords']);
        $api->get('/goods/keywordsDetail', ['name' => '获取关键词详情', 'as' => 'goods.Keywords.getByShop', 'uses' => 'Items@getKeyWordsDetail']);
        //从oms同步数据
        $api->post('/goods/sync/items', ['name' => '从oms同步商品数据', 'as' => 'goods.sync.items', 'uses' => 'SyncFromOme@syncItems']);
        $api->post('/goods/sync/itemCategory', ['name' => '从oms同步商品分类', 'as' => 'goods.sync.itemCategory', 'uses' => 'SyncFromOme@syncItemCategory']);
        $api->post('/goods/sync/itemSpec', ['name' => '从oms同步商品规格', 'as' => 'goods.sync.itemSpec', 'uses' => 'SyncFromOme@syncItemSpec']);
        $api->post('/goods/sync/brand', ['name' => '从oms同步品牌', 'as' => 'goods.sync.brand', 'uses' => 'SyncFromOme@syncBrand']);

        $api->put('/goods/itemsisgiftupdate', ['name' => '批量设置商品为赠品', 'middleware' => 'activated', 'as' => 'goods.isgift.upate', 'uses' => 'Items@batchUpdateItemIsgift']);

        // 商品佣金配置  用于斗拱平台结算佣金
        $api->get('/goods/commission/{item_id}', ['name'=>'商品佣金配置获取', 'as' => 'goods.commission.info', 'uses' =>'ItemsCommission@getItemsCommission']);
        $api->post('/goods/commission/save', ['name'=>'商品佣金配置保存', 'as' => 'goods.commission.save', 'uses' =>'ItemsCommission@saveGoodsCommission']);
        //上传商品信息到聚水潭
        $api->post('/goods/upload/items', ['name'=>'上传商品信息到聚水潭', 'as' => 'goods.jishuitan.items.upload', 'uses' =>'Jushuitan@uploadItems']);
        //同步聚水潭商品库存
        $api->post('/goods/query/inventory', ['name'=>'同步聚水潭商品库存', 'as' => 'goods.jushuitan.inventory.query', 'uses' =>'Jushuitan@queryInventory']);

        //上传商品信息到旺店通
        $api->post('/goods/upload/wdterp/items', ['name'=>'上传商品信息到旺店通', 'as' => 'goods.wdterp.items.upload', 'uses' =>'WdtErp@uploadItems']);

        // 重新推送药品数据
        $api->post('/goods/medicineItems/sync', ['name'=>'重新推送药品数据', 'as' => 'goods.medicineItems.sync', 'uses' =>'Items@syncMedicine']);

        // 商品参数添加
        $api->post('/goods/items/params', ['name' => '添加商品参数接口', 'as' => 'goods.items.params.create', 'uses' => 'Items@createItemsParams']);

    });
});
