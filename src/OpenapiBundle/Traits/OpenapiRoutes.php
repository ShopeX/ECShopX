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

namespace OpenapiBundle\Traits;

use Illuminate\Http\Request;

trait OpenapiRoutes
{
    public function getRoute($version)
    {
        $routes = array(
            '1.0' => [
                'ecx.product.sku_list' => ['uses' => 'Item@list'], //商品列表查询-A1
                'ecx.product.stock_update' => ['uses' => 'Item@updateItemStore'], //库存同步-A2
                'ecx.product.goods_list' => ['uses' => 'Item@goodsList'], //导购获取云店商品列表
                'ecx.order.deliver' => ['uses' => 'Delivery@createDelivery'], //订单发货接口-A4
                'ecx.coupon.create' => ['uses' => 'Coupon@userGetCard'], //云店优惠券发放-A18
                'ecx.coupon.verify' => ['uses' => 'Coupon@userConsumeCard'], //优惠券状态更新-A21
                'ecx.coupon.update' => ['uses' => 'Coupon@updateUserCard'], //优惠券信息更新-A25
                'ecx.coupon.list' => ['uses' => 'Coupon@getCouponList'], //获取优惠券列表
                'ecx.member.query' => ['uses' => 'Member@memberInfo'], //根据手机号查询会员接口
                'ecx.member.create' => ['uses' => 'Member@memberCreate'], //会员信息创建
                'ecx.member.basicInfo' => ['uses' => 'Member@basicInfo'], //会员基础信息
                'ecx.member.orderList' => ['uses' => 'Member@getMemberOrderLists'], //会员订单列表,已完成订单
                'ecx.member.browserHistoryList' => ['uses' => 'Member@geMembertBrowseList'], //会员浏览足迹
                'ecx.member.list' => ['uses' => 'Member@memberInfoList'], //会员基础信息
                'ecx.member.frequentItems' => ['uses' => 'Member@frequentItems'], //会员常购清单
                'ecx.member.browserHistory' => ['uses' => 'Member@browseHistory'], //会员浏览足迹
                'ecx.order.list' => ['uses' => 'Order@list'], //会员订单信息
                'ecx.salesperson.push' => ['uses' => 'ShopSalesperson@pushSalesperson'], //创建更新导购
                'ecx.salesperson.bathStatusUpdate' => ['uses' => 'ShopSalesperson@bathUpdateSalespersonStatus'], //更新导购状态
                'ecx.salesperson.destroy' => ['uses' => 'ShopSalesperson@destroySalesperson'], //删除导购
                'ecx.salesperson.updateStores' => ['uses' => 'ShopSalesperson@updateSalespersonStores'], //更新导购的绑定店铺
                //图片上传相关接口
                'ecx.image.upload_token' => ['uses' => 'Image@getUploadToken'], //获取token
                'ecx.image.upload_localimage' => ['uses' => 'Image@uploadeImage'], //本地存储上传图片
                'ecx.image.list' => ['uses' => 'Image@getImageList'], //获取图片列表
                'ecx.image.save' => ['uses' => 'Image@saveImage'], //保存图片链接
                'ecx.image.del' => ['uses' => 'Image@deleteImage'], //保存图片链接
                'ecx.wxapp.qrcode' => ['uses' => 'Wxapp@getWxCode'], //获取导购任务二维码
                'ecx.wxapp.shoplist' => ['uses' => 'Wxapp@getWxShopLists'], //获取微信店铺数据
                'ecx.weappid.get' => ['uses' => 'Wxapp@getWeappId'], //获取yykweishop模板的appid
                'ecx.jurisdiction.role' => ['uses' => 'Jurisdiction@role'], //角色从导购同步到本地
                'ecx.jurisdiction.sysuser' => ['uses' => 'Jurisdiction@sysuser'], //系统用户从导购同步到本地
                'ecx.jurisdiction.getuser' => ['uses' => 'Jurisdiction@getuser'], //导购获取云店账号
                'ecx.discountcard.info' => ['uses' => 'DiscountCardController@getDiscountCardDetail'], //卡券详情
                'ecx.discountcard.list' => ['uses' => 'DiscountCardController@getDiscountCardList'], //卡券列表
                'ecx.company.info' => ['uses' => 'Company@getInfo'], //导购获取云店基本信息
                // 操作权限[内部调用]
                'exc.operator.resetpwd' => ['uses' => 'Operator@resetPassword'], //管理员重置密码通知云店token失效
                "ecx.distributor.detail" => ["uses" => "DistributorController@detail"], // 查询 店铺详情
            ],
            "2.0" => [
                /* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 订单 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
                'ecx.orders.incr.get' => ['uses' => 'Orders\Order@getIncrOrderList', 'method' => Request::METHOD_GET], //增量订单搜索
                'ecx.orders.sold.get' => ['uses' => 'Orders\Order@getList', 'method' => Request::METHOD_GET], //订单搜索
                'ecx.order.list' => ['uses' => 'Orders\Order@list'], //会员订单信息
                'ecx.order.get' => ['uses' => 'Orders\Order@getDetail', 'method' => Request::METHOD_GET], //订单详情
                'ecx.order.deliver' => ['uses' => 'Orders\Delivery@createDelivery'], //订单发货接口-A4
                'ecx.order.deliver.get' => ['uses' => 'Orders\Delivery@getOrderDeliveryList', 'method' => Request::METHOD_GET], // 订单发货单列表
                'ecx.order.cancel.reasons.get' => ['uses' => 'Orders\Order@getCancelReasons', 'method' => Request::METHOD_GET], //订单取消原因
                'ecx.order.cancel' => ['uses' => 'Orders\Order@orderCancel', 'method' => Request::METHOD_POST], //订单取消
                'ecx.order.writeoff' => ['uses' => 'Orders\Order@orderWriteoff', 'method' => Request::METHOD_POST], //自提订单核销
                'ecx.logistics.enabled.get' => ['uses' => 'Orders\Order@getEnabledLogisticsList', 'method' => Request::METHOD_GET], //获取开启中的物流公司
                'ecx.order.cancel.confirm' => ['uses' => 'Orders\Order@confirmCancel', 'method' => Request::METHOD_POST], // 取消订单，确认审核
                'ecx.shipping.templates.get' => ['uses' => 'Orders\Order@getShippingtemplates', 'method' => Request::METHOD_GET], //获取运费模板
                'ecx.trades.get' => ['uses' => 'Orders\Order@getTradeList', 'method' => Request::METHOD_GET], //获取交易单列表
                'ecx.aftersales.get' => ['uses' => 'Orders\Aftersales@getAftersalesList', 'method' => Request::METHOD_GET], //获取售后列表
                'ecx.aftersales.incr.get' => ['uses' => 'Orders\Aftersales@getIncrAftersalesList', 'method' => Request::METHOD_GET], //获取增量售后单列表
                'ecx.aftersales.detail.get' => ['uses' => 'Orders\Aftersales@getAftersalesDetail'], //获取售后详情
                'ecx.refund.get' => ['uses' => 'Orders\Aftersales@getRefundList', 'method' => Request::METHOD_GET], //获取退款单列表
                'ecx.refund.incr.get' => ['uses' => 'Orders\Aftersales@getIncrRefundList', 'method' => Request::METHOD_GET], //获取增量退款单列表
                'ecx.refund.detail.get' => ['uses' => 'Orders\Aftersales@getRefundDetail'], //获取退款单详情
                /* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 订单 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

                /* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 会员标签分类 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
                'ecx.member.tagcategory.add' => ['uses' => 'Member\MemberTagCategory@createTagCategory', 'method' => Request::METHOD_POST],// 新增会员标签分类
                'ecx.member.tagcategory.delete' => ['uses' => 'Member\MemberTagCategory@deleteTagCategory', 'method' => Request::METHOD_DELETE],// 删除会员标签分类
                'ecx.member.tagcategory.update' => ['uses' => 'Member\MemberTagCategory@updateTagCategory', 'method' => Request::METHOD_POST],// 更新会员标签分类
                'ecx.member.tagcategorys.get' => ['uses' => 'Member\MemberTagCategory@getTagCategoryList', 'method' => Request::METHOD_GET],// 查询会员标签分类列表
                /* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 会员标签分类 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

                /* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 会员标签 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
                'ecx.member.tag.add' => ['uses' => 'Member\MemberTag@createTag', 'method' => Request::METHOD_POST],// 新增会员标签
                'ecx.member.tag.delete' => ['uses' => 'Member\MemberTag@deleteTag', 'method' => Request::METHOD_DELETE],// 删除会员标签
                'ecx.member.tag.update' => ['uses' => 'Member\MemberTag@updateTag', 'method' => Request::METHOD_POST],// 修改会员标签
                'ecx.member.tags.get' => ['uses' => 'Member\MemberTag@getTagsList', 'method' => Request::METHOD_GET],// 查询会员标签列表
                'ecx.member.tagging.batch.cover' => ['uses' => 'Member\MemberTag@batchCoverMemberTags', 'method' => Request::METHOD_POST],// 为会员批量打标签(覆盖)
                'ecx.member.tagging.batch.update' => ['uses' => 'Member\MemberTag@batchUpdateMemberTags', 'method' => Request::METHOD_POST],// 为会员批量打标签(不覆盖)
                'ecx.member.tagged.delete' => ['uses' => 'Member\MemberTag@deleteMemberTagged', 'method' => Request::METHOD_DELETE],// 删除会员已打标签
                'ecx.member.tagged.get' => ['uses' => 'Member\MemberTag@getMemberTagged', 'method' => Request::METHOD_GET],// 查询会员已打标签
                'ecx.tag.members.get' => ['uses' => 'Member\MemberTag@getTagMembers', 'method' => Request::METHOD_GET],// 查询标签关联会员列表
                /* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 会员标签 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

                /* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 会员储值 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
                'ecx.member.rechargerule.add' => ['uses' => 'Member\MemberRecharge@createRechargeRule', 'method' => Request::METHOD_POST],// 新增储值面额规则
                'ecx.member.rechargerule.delete' => ['uses' => 'Member\MemberRecharge@deleteRechargeRule', 'method' => Request::METHOD_DELETE],// 删除储值面额规则
                'ecx.member.rechargerule.update' => ['uses' => 'Member\MemberRecharge@updateRechargeRule', 'method' => Request::METHOD_POST],// 修改储值面额规则
                'ecx.member.rechargerule.get' => ['uses' => 'Member\MemberRecharge@getRechargeRuleList', 'method' => Request::METHOD_GET],// 查询储值面额规则列表
                'ecx.member.recharge.trade.get' => ['uses' => 'Member\MemberRecharge@getRechargeTradeList', 'method' => Request::METHOD_GET],// 查询储值交易记录
                /* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 会员储值 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

                /* ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ 商品--实体商品 ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ ↓↓↓↓↓ */
                'ecx.items.entity.get' => ['uses' => 'Items\NormalItems@getList', 'method' => Request::METHOD_GET], //商品搜索
                'ecx.item.brand.add' => ['uses' => 'Items\ItemsAttributes@createItemBrand', 'method' => Request::METHOD_POST],// 添加品牌
                'ecx.item.brand.delete' => ['uses' => 'Items\ItemsAttributes@deleteItemBrand', 'method' => Request::METHOD_DELETE],// 删除品牌
                'ecx.item.brand.update' => ['uses' => 'Items\ItemsAttributes@updateItemBrand', 'method' => Request::METHOD_POST],// 修改品牌
                'ecx.item.brand.get' => ['uses' => 'Items\ItemsAttributes@getItemBrandList', 'method' => Request::METHOD_GET],// 获取品牌列表
                'ecx.item.category.add' => ['uses' => 'Items\ItemsCategory@createItemCategory', 'method' => Request::METHOD_POST],// 添加分类
                'ecx.item.category.delete' => ['uses' => 'Items\ItemsCategory@deleteItemCategory', 'method' => Request::METHOD_DELETE],// 删除分类
                'ecx.item.category.update' => ['uses' => 'Items\ItemsCategory@updateItemCategory', 'method' => Request::METHOD_POST],// 修改分类
                'ecx.item.category.get' => ['uses' => 'Items\ItemsCategory@getItemCategoryList', 'method' => Request::METHOD_GET],// 获取商品分类列表
                'ecx.item.maincategory.get' => ['uses' => 'Items\ItemsCategory@getItemMainCategoryList', 'method' => Request::METHOD_GET],// 获取商品主类目列表
                'ecx.item.maincategory.detail.get' => ['uses' => 'Items\ItemsCategory@getItemMainCategoryDetail', 'method' => Request::METHOD_GET],// 获取商品主类目详情，包含规格和参数列表
                'ecx.item.entity.get' => ['uses' => 'Items\NormalItems@getItemSpuDetail', 'method' => Request::METHOD_GET],// 根据item_bn获取商品SPU详情
                'ecx.item.entity.status.update' => ['uses' => 'Items\NormalItems@batchUpdateItemsStatus', 'method' => Request::METHOD_POST],// 根据item_bn,批量更新商品状态
                'ecx.item.entity.delete' => ['uses' => 'Items\NormalItems@deleteItems', 'method' => Request::METHOD_DELETE],// 根据item_bn,删除单个商品SPU
                'ecx.item.entity.add' => ['uses' => 'Items\NormalItems@createItems', 'method' => Request::METHOD_POST],// 创建单个实体商品SPU
                'ecx.item.entity.update' => ['uses' => 'Items\NormalItems@updateItems', 'method' => Request::METHOD_POST],// 编辑单个实体商品SPU
                'ecx.item.store.sync' => ['uses' => 'Items\StoreController@sync', 'method' => Request::METHOD_PUT],// 同步总部/店铺商品库存
                'ecx.item.store.update' => ['uses' => 'Items\StoreController@update', 'method' => Request::METHOD_PUT],// 增/减 总部/店铺商品库存
                'ecx.item.store.get' => ['uses' => 'Items\StoreController@detail', 'method' => Request::METHOD_GET],// 查询总部/店铺商品库存
                'ecx.item.price.sync' => ['uses' => 'Items\PriceController@sync', 'method' => Request::METHOD_PUT],// 同步总部/店铺商品价格
                'ecx.item.price.get' => ['uses' => 'Items\PriceController@detail', 'method' => Request::METHOD_GET],// 查询总部/店铺商品价格
                'ecx.product.sku_list' => ['uses' => 'Items\Item@list'], //商品列表查询-A1
                'ecx.product.stock_update' => ['uses' => 'Items\Item@updateItemStore'], //库存同步-A2
                'ecx.product.goods_list' => ['uses' => 'Items\Item@goodsList'], //导购获取云店商品列表
                /* ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ 商品--实体商品 ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ ↑↑↑↑↑ */

                /** 会员基础信息相关  开始 **/
                "ecx.member.create" => ["uses" => "Member\MemberController@create", "method" => Request::METHOD_POST], // 创建会员（单个）
                "ecx.member.list" => ["uses" => "Member\MemberController@list", "method" => Request::METHOD_GET], // 获取会员（多个）
                "ecx.member.detail" => ["uses" => "Member\MemberController@detail", "method" => Request::METHOD_GET], // 获取会员（详情）
                "ecx.member.batch_create" => ["uses" => "Member\MemberController@batchCreate", "method" => Request::METHOD_POST], // 创建会员（批量）
                "ecx.member_info.update" => ["uses" => "Member\MemberController@updateDetail", "method" => Request::METHOD_PATCH], // 更新会员信息
                "ecx.member_mobile.update" => ["uses" => "Member\MemberController@updateMobile", "method" => Request::METHOD_PATCH], // 更新会员手机号
                "ecx.member_card_code_grade.update" => ["uses" => "Member\MemberController@updateCardCodeAndGrade", "method" => Request::METHOD_PATCH], // 更新会员卡号、等级
                "ecx.member_order.list" => ["uses" => "Member\MemberOrderController@list", "method" => Request::METHOD_GET], // 获取会员订单（多个）
                "ecx.member_operate_log.list" => ["uses" => "Member\MemberOperateLogController@list", "method" => Request::METHOD_GET], // 获取会员操作日志（多个）
                "ecx.member.card_grade.update" => ["uses" => "Member\MemberController@updateGrade", "method" => Request::METHOD_POST], // 更新会员等级
                /** 会员基础信息相关  结束 **/

                /** 会员积分相关  开始 **/
                "ecx.member_point_log.list" => ["uses" => "Member\MemberPointController@list", "method" => Request::METHOD_GET], // 查询会员积分历史记录（多个）
                "ecx.member_point_order.list" => ["uses" => "Member\MemberOrderController@pointList", "method" => Request::METHOD_GET], // 查询会员订单积分（多个）
                "ecx.member_point.update" => ["uses" => "Member\MemberPointController@update", "method" => Request::METHOD_PATCH], // 增/减会员积分
                "ecx.member_point.detail" => ["uses" => "Member\MemberPointController@detail", "method" => Request::METHOD_GET], // 查询会员订单积分
                /** 会员积分相关  结束 **/

                /** 会员卡相关  开始 **/
                "ecx.member_card.detail" => ["uses" => "Member\MemberCardController@detail", "method" => Request::METHOD_GET], // 查询 会员卡基础设置
                "ecx.member_card.update" => ["uses" => "Member\MemberCardController@update", "method" => Request::METHOD_POST], // 修改 会员卡基础设置
                "ecx.member_card_grade.list" => ["uses" => "Member\MemberCardGradeController@list", "method" => Request::METHOD_GET], // 查询 会员卡等级列表
                "ecx.member_card_grade.detail" => ["uses" => "Member\MemberCardGradeController@detail", "method" => Request::METHOD_GET], // 查询 会员关联等级信息
                "ecx.member_card_grade.create" => ["uses" => "Member\MemberCardGradeController@create", "method" => Request::METHOD_POST], // 新增 会员卡等级设置
                "ecx.member_card_grade.update" => ["uses" => "Member\MemberCardGradeController@update", "method" => Request::METHOD_PATCH], // 修改 会员卡等级设置
                "ecx.member_card_grade.delete" => ["uses" => "Member\MemberCardGradeController@delete", "method" => Request::METHOD_DELETE], // 删除 会员卡等级设置
                "ecx.member_card_vip_grade.list" => ["uses" => "Member\MemberCardVipGradeController@list", "method" => Request::METHOD_GET], // 查询 付费会员卡等级列表
                "ecx.member_card_vip_grade.detail" => ["uses" => "Member\MemberCardVipGradeController@detail", "method" => Request::METHOD_GET], // 查询 会员关联付费等级信息
                "ecx.member_card_vip_grade.create" => ["uses" => "Member\MemberCardVipGradeController@create", "method" => Request::METHOD_POST], // 新增 付费会员卡等级设置
                "ecx.member_card_vip_grade.update" => ["uses" => "Member\MemberCardVipGradeController@update", "method" => Request::METHOD_PATCH], // 修改 付费会员卡等级设置
                "ecx.member_card_vip_grade.delete" => ["uses" => "Member\MemberCardVipGradeController@delete", "method" => Request::METHOD_DELETE], // 删除 付费会员卡等级设置
                "ecx.member_card_vip_grade_order.list" => ["uses" => "Member\MemberCardVipGradeController@orderList", "method" => Request::METHOD_GET], // 查询 付费会员卡等级购买记录
                "ecx.member_card_grade_rel.list" => ["uses" => "Member\MemberController@listByGradeOrVipGrade", "method" => Request::METHOD_GET], // 查询 会员等级关联的会员列表
                "ecx.card_grade.batch_save" => ["uses" => "Member\MemberCardGradeController@batchSave", "method" => Request::METHOD_POST], // 批量修改 会员卡等级设置

                /** 会员卡相关  结束 **/

                /** 优惠券相关  开始 **/
                "ecx.discountcard.list" => ["uses" => "Kaquan\DiscountCardController@getDiscountCardList", "method" => Request::METHOD_POST], // 获取优惠券列表
                "ecx.discountcard.send" => ["uses" => "Kaquan\DiscountCardController@userGetCard", "method" => Request::METHOD_POST], // 单张优惠券发放
                "ecx.userdiscount.list" => ["uses" => "Kaquan\DiscountCardController@getUserDiscountList", "method" => Request::METHOD_POST], // 获取会员已领取优惠券列表
                /** 优惠券相关  结束 **/

                /** 店铺相关 开始 **/
                "ecx.distributor.list" => ["uses" => "Distributor\DistributorController@list", "method" => Request::METHOD_GET], // 查询 店铺列表
                "ecx.distributor.create" => ["uses" => "Distributor\DistributorController@create", "method" => Request::METHOD_POST], // 新增 店铺
                "ecx.distributor.update" => ["uses" => "Distributor\DistributorController@update", "method" => Request::METHOD_PATCH], // 更新 店铺
                "ecx.distributor.detail" => ["uses" => "Distributor\DistributorController@detail", "method" => Request::METHOD_GET], // 查询 店铺详情
                "ecx.distributor.download" => ["uses" => "Distributor\DistributorController@download", "method" => Request::METHOD_GET], // 获取 店铺二维码的图片信息
                "ecx.distributor_item.list" => ["uses" => "Distributor\DistributorItemController@list", "method" => Request::METHOD_GET], // 查询 店铺商品列表
                "ecx.distributor_item.update" => ["uses" => "Distributor\DistributorItemController@update", "method" => Request::METHOD_PATCH], // 更新 店铺商品
                "ecx.distributor_item.download" => ["uses" => "Distributor\DistributorItemController@download", "method" => Request::METHOD_GET], // 获取 店铺商品二维码的图片信息
                /** 店铺相关 结束 **/
            ]
        );
        return $routes[$version] ?? false;
    }

    /**
     * 判断请求的接口的响应体不是json内容
     * @param string $version 接口版本号
     * @param string $method 接口的方法
     * @return string 获取响应体类型
     */
    public function getResponseTypeByVersionAndMethod(string $version, string $method): string
    {
        $routes = [
            "1.0" => [
                "ecx.wxapp.qrcode" => "image", //获取导购任务二维码
            ],
        ];
        return $routes[$version][$method] ?? "json";
    }
}
