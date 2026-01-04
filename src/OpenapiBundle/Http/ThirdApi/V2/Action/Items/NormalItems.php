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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Items;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use GoodsBundle\Services\ItemsService;
use OpenapiBundle\Services\Items\ItemsService as OpenapiItemsService;

class NormalItems extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.item.entity.get",
     *     summary="获取一个实体产品的详细信息",
     *     tags={"商品"},
     *     description="根据is_default=true的sku的货号，获取一个实体产品(SPU)的详细信息",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.entity.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_bn", description="货号 is_default=true的sku的货号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="goods_id", type="string", example="1", description="产品ID"),
     *                  @SWG\Property( property="item_bn", type="string", example="S60865A0139CC4", description="商品编号"),
     *                  @SWG\Property( property="item_name", type="string", example="家用家具", description="商品标题"),
     *                  @SWG\Property( property="brief", type="string", example="", description="简洁的描述"),
     *                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="商品排序"),
     *                  @SWG\Property( property="brand_id", type="string", example="1485", description="品牌id"),
     *                  @SWG\Property( property="templates_id", type="string", example="1", description="运费模板id"),
     *                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                  @SWG\Property( property="pics", type="object",
     *                      @SWG\Items( type="string", example="https://bbctest.aixue7.com/image/1/2021/04/25/9538dcdea464455ca646d9a10ae775a1bU5BdXKs2ufLxGjH6hzfzaISu7hVzHcv", description="商品图片地址"),
     *                  ),
     *                  @SWG\Property( property="nospec", type="boolean", example="false", description="商品是否为单规格 true:单规格 false:多规格"),
     *                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                  @SWG\Property( property="weight", type="string", example="0", description="商品重量 单位：kg"),
     *                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                  @SWG\Property( property="price", type="string", example="1200", description="销售金额,单位为‘分’"),
     *                  @SWG\Property( property="market_price", type="string", example="1200", description="原价,单位为‘分’"),
     *                  @SWG\Property( property="cost_price", type="string", example="1000", description="价格,单位为‘分’"),
     *                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show :前台仅展示;"),
     *                  @SWG\Property( property="store", type="string", example="1000", description="商品库存"),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="", description="商品管理分类叶子ID"),
     *                  @SWG\Property( property="item_category", type="object", example="", description="商品销售分类叶子ID"),
     *                  @SWG\Property( property="update_time", type="string", example="1644989675", description="更新时间"),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_bn", type="string", example="S60865A0139CC4", description="商品编号"),
     *                          @SWG\Property( property="is_default", type="boolean", example="true", description="商品是否为默认商品"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show :前台仅展示;"),
     *                          @SWG\Property( property="weight", type="string", example="0", description="商品重量 单位：kg"),
     *                          @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                          @SWG\Property( property="price", type="string", example="1200", description="销售金额,单位为‘分’"),
     *                          @SWG\Property( property="market_price", type="string", example="1200", description="原价,单位为‘分’"),
     *                          @SWG\Property( property="cost_price", type="string", example="1000", description="成本价,单位为‘分’"),
     *                          @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                          @SWG\Property( property="store", type="string", example="", description="商品库存"),
     *                          @SWG\Property( property="item_spec", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="spec_id", type="string", example="10", description="规格项ID"),
     *                                  @SWG\Property( property="spec_value_id", type="string", example="20", description="规格值ID"),
     *                                  @SWG\Property( property="spec_name", type="string", example="文字规格", description="规格项名称"),
     *                                  @SWG\Property( property="spec_custom_value_name", type="string", example="null", description="自定义规格值名称"),
     *                                  @SWG\Property( property="spec_value_name", type="string", example="二", description="规格值名称"),
     *                                  @SWG\Property( property="spec_image_url", type="string", example="null", description="规格图片地址"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="spec_id", type="string", example="10", description="规格项ID"),
     *                          @SWG\Property( property="spec_name", type="string", example="文字规格", description="规格项名称"),
     *                          @SWG\Property( property="is_image", type="string", example="false", description="属性是否需要配置图片"),
     *                          @SWG\Property( property="spec_values", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="spec_value_id", type="string", example="20", description="规格值ID"),
     *                                  @SWG\Property( property="spec_value_name", type="string", example="二", description="规格值名称"),
     *                                  @SWG\Property( property="spec_custom_value_name", type="string", example="二", description="自定义规格值名称"),
     *                                  @SWG\Property( property="spec_image_url", type="string", example="null", description="规格图片地址"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="spec_value_id", type="string", example="12", description="规格值ID"),
     *                          @SWG\Property( property="spec_custom_value_name", type="string", example="null", description="自定义规格值名称"),
     *                          @SWG\Property( property="spec_value_name", type="string", example="兰", description="规格值名称"),
     *                          @SWG\Property( property="spec_image_url", type="string", example="http://bbctest.aixue7.com/1/2019/09/24/5d4b4813aa21b09131aa0fba3a797098j5SYzqNhfVkEGyO7x392s0qAT9o3einY", description="规格图片地址"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getItemSpuDetail(Request $request)
    {
        // Built with ShopEx Framework
        $params = $request->all('item_bn');
        $rules = [
            'item_bn' => ['required', '货号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->getItemSpuDetail($companyId, $params['item_bn']);
        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.item.entity.status.update",
     *     summary="商品状态更改",
     *     tags={"商品"},
     *     description="根据is_default=true的sku的货号，批量更改商品(SPU)的状态。最多可更新1000个商品。至少有一个商品更新成功时，就回返回更新成功。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.entity.status.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_bn", description="is_default=true的sku的货号 json_array [货号,货号]" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="status", description="需要变更的状态 onsale:前台可销售;instock:不可销售" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function batchUpdateItemsStatus(Request $request)
    {
        $params = $request->all('item_bn', 'status');
        $rules = [
            'item_bn' => ['required', '商品货号必填'],
            'status' => ['required|in:onsale,instock', '状态必填,且必须是 onsale 或 instock '],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['item_bn'] = json_decode($params['item_bn'], 1);
        if (!is_array($params['item_bn'])) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '请填写正确的货号格式');
        }
        if (count($params['item_bn']) > 1000) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '商品状态可更改的最大数量为1000');
        }

        $companyId = $request->get('auth')['company_id'];
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->batchUpdateItemsStatus($companyId, $params['item_bn'], $params['status']);
        return $this->response->array(['status' => $return]);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.item.entity.delete",
     *     summary="删除单个实体商品",
     *     tags={"商品"},
     *     description="根据is_default=true的sku的货号，删除单个实体商品",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.entity.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_bn", description="is_default=true的sku的货号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function deleteItems(Request $request)
    {
        $params = $request->all('item_bn');
        $rules = [
            'item_bn' => ['required', '商品货号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        try {
            $openapiItemsService = new OpenapiItemsService();
            $openapiItemsService->deleteItems($companyId, $params['item_bn']);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_DELETE_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.item.entity.add",
     *     summary="创建单个实体商品",
     *     tags={"商品"},
     *     description="创建单个实体商品（SPU）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.entity.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_main_cat_id", description="商品三级类目ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_name", description="商品标题" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="brief", description="商品副标题" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="templates_id", description="运费模板ID ecx.shipping.templates.get接口可以查询运费模板数据。" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_id", description="品牌ID ecx.item.brand.get接口可以查询品牌数据。" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_category", description="分类ID，ID为叶子分类的ID。json_array 格式：[叶子分类ID,叶子分类ID]。ecx.item.category.get接口可以查询到分类数据。" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_unit", description="计量单位" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="排序" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="is_gift", description="是否为赠品 true:是;false:否; 如果是true,在商城小程序里，仅做展示，不能购买" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="pics", description="图片地址，最多9张图片，文件格式为bmp、png、jpeg、jpg或gif，大小不超过2M。 json_array 格式：[图片地址,图片地址]" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="intro", description="详情描述" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params", description="商品参数JSON" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_id", description="参数项ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_value_id", description="参数值ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_value_name", description="参数值名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="nospec", description="是否为单规格 true:单规格;false:多规格;" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_show_specimg", description="是否在商详页成列图片规格 true:是;false:否。 nospec=false时使用"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="approve_status", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show: 前台仅展示。 nospec=true时，必填"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_bn", description="商品货号。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="weight", description="重量（kg）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="volume", description="体积。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="price", description="销售价（元）。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="market_price", description="原价（元）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="cost_price", description="成本价（元）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="barcode", description="商品条形码。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="store", description="库存。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images", description="规格图片地址JSON。nospec=false时，选填。在其中一个规格项的规格值下面设置图片，最多5张。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.spec_value_id", description="规格值ID。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.item_spec", description="规格值名称。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.item_image_url", description="图片地址数组，最多5张。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items", description="规格数据JSON。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.is_default", description="是否为默认规格 true:是;false:否。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.is_default", description="是否为默认规格 true:是;false:否。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.approve_status", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show: 前台仅展示。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.store", description="商品库存。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_bn", description="商品货号。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.weight", description="重量（kg）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.volume", description="体积。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.price", description="销售价（元）。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.cost_price", description="成本价（元）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.market_price", description="原价（元）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.barcode", description="条形码。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.sku_id", description="规格值ID以_连接后的字符串。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec", description="规格具体数据数组。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_id", description="规格项ID。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_value_id", description="规格值ID。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_value_name", description="规格值名称。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_custom_value_name", description="规格值自定义名称。nospec=false时，必填。"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createItems(Request $request)
    {
        $params = $request->all('item_main_cat_id', 'item_name', 'brief', 'sort', 'item_unit', 'item_category', 'brand_id', 'templates_id', 'is_gift', 'pics', 'intro', 'nospec', 'is_show_specimg', 'approve_status', 'item_bn', 'weight', 'volume', 'price', 'market_price', 'cost_price', 'barcode', 'store', 'item_params', 'spec_images', 'spec_items');
        $rules = [
            'item_main_cat_id' => ['required', '管理分类必填'],
            'item_name' => ['required', '商品名称必填'],
            'pics' => ['required', '请上传商品图片'],
            // 'sort'                  => ['required|integer', '排序值必须为整数'],
            'templates_id' => ['required', '运费模板必填'],
            'brand_id' => ['required', '品牌必填'],
            'item_category' => ['required', '商品分类ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['pics'] = json_decode($params['pics'], 1);
        if (!is_array($params['pics']) || !$params['pics'] || count($params['pics']) != count($params['pics'], 1)) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '商品图片格式错误');
        }
        $params['item_category'] = json_decode($params['item_category'], 1);
        if (!is_array($params['item_category']) || !$params['item_category'] || count($params['item_category']) != count($params['item_category'], 1)) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '商品分类格式错误');
        }

        $params['company_id'] = $request->get('auth')['company_id'];
        $params['item_type'] = 'normal';
        $params['special_type'] = 'normal';
        $params['item_source'] = 'openapi';

        try {
            $itemsService = new ItemsService();
            $result = $itemsService->addItems($params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, $e->getMessage());
        }

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.item.entity.update",
     *     summary="修改单个实体商品",
     *     tags={"商品"},
     *     description="根据item_bn修改单个实体商品（SPU）。",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.item.entity.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="old_item_bn", description="老商品货号 is_default=true的sku的商品货号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_main_cat_id", description="商品三级类目ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_name", description="商品标题" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="brief", description="商品副标题" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="templates_id", description="运费模板ID ecx.shipping.templates.get接口可以查询运费模板数据" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="brand_id", description="品牌ID ecx.item.brand.get接口可以查询品牌数据" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="item_category", description="分类ID，ID为叶子分类的ID。json_array 格式：[叶子分类ID,叶子分类ID]。ecx.item.category.get接口可以查询到分类数据。" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_unit", description="计量单位" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="排序" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="is_gift", description="是否为赠品 true:是;false:否; 如果是true,在商城小程序里，仅做展示，不能购买" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="pics", description="图片地址，最多9张图片，文件格式为bmp、png、jpeg、jpg或gif，大小不超过2M。json_array 格式:[图片地址,图片地址]" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="intro", description="详情描述" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params", description="商品参数JSON" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_id", description="参数项ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_value_id", description="参数值ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_params.0.attribute_value_name", description="参数值名称" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="nospec", description="是否为单规格 true:单规格;false:多规格;" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="is_show_specimg", description="是否在商详页成列图片规格 true:是;false:否。 nospec=false时使用"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="approve_status", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show: 前台仅展示。 nospec=true时，必填"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="item_bn", description="商品货号。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="weight", description="重量（kg）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="volume", description="体积。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="price", description="销售价（元）。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="market_price", description="原价（元）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="cost_price", description="成本价（元）。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="barcode", description="商品条形码。nospec=true时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="store", description="库存。nospec=true时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images", description="规格图片地址JSON。nospec=false时，选填。在其中一个规格项的规格值下面设置图片，最多5张。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.spec_value_id", description="规格值ID。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.item_spec", description="规格值名称。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_images.0.item_image_url", description="图片地址数组，最多5张。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items", description="规格数据JSON。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.is_default", description="是否为默认规格 true:是;false:否。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.is_default", description="是否为默认规格 true:是;false:否。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.approve_status", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show: 前台仅展示。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.store", description="商品库存。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_bn", description="商品货号。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.weight", description="重量（kg）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.volume", description="体积。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.price", description="销售价（元）。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.cost_price", description="成本价（元）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.market_price", description="原价（元）。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.barcode", description="条形码。nospec=false时，选填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.sku_id", description="规格值ID以_连接后的字符串。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec", description="规格具体数据数组。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_id", description="规格项ID。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_value_id", description="规格值ID。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_value_name", description="规格值名称。nospec=false时，必填。"),
     *     @SWG\Parameter( in="query", type="string", required=false, name="spec_items.0.item_spec.0.spec_custom_value_name", description="规格值自定义名称。nospec=false时，必填。"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="状态"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateItems(Request $request)
    {
        $params = $request->all('old_item_bn', 'item_main_cat_id', 'item_name', 'brief', 'sort', 'item_unit', 'item_category', 'brand_id', 'templates_id', 'is_gift', 'pics', 'intro', 'nospec', 'is_show_specimg', 'approve_status', 'item_bn', 'weight', 'volume', 'price', 'market_price', 'cost_price', 'barcode', 'store', 'item_params', 'spec_images', 'spec_items');
        $rules = [
            'old_item_bn' => ['required', '老的商品货号必填'],
            'item_main_cat_id' => ['required', '管理分类必填'],
            'item_name' => ['required', '商品名称必填'],
            'pics' => ['required', '请上传商品图片'],
            // 'sort'                  => ['required|integer', '排序值必须为整数'],
            'templates_id' => ['required', '运费模板必填'],
            'brand_id' => ['required', '品牌必填'],
            'item_category' => ['required', '商品分类ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['pics'] = json_decode($params['pics'], 1);
        if (!is_array($params['pics']) || !$params['pics'] || count($params['pics']) != count($params['pics'], 1)) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '商品图片格式错误');
        }
        $params['item_category'] = json_decode($params['item_category'], 1);
        if (!is_array($params['item_category']) || !$params['item_category'] || count($params['item_category']) != count($params['item_category'], 1)) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, '商品分类格式错误');
        }
        $companyId = $request->get('auth')['company_id'];
        $params['company_id'] = $companyId;
        $params['item_type'] = 'normal';
        $params['special_type'] = 'normal';
        $params['item_source'] = 'openapi';
        if ($params['item_params']) {
            $params['item_params'] = json_decode($params['item_params'], 1);
        }

        try {
            $itemsService = new ItemsService();
            // 查询商品数据
            $filter = [
                'company_id' => $companyId,
                'item_bn' => $params['old_item_bn'],
                'is_default' => true,
            ];
            $cols = ['item_id', 'nospec'];
            $itemsInfo = $itemsService->getSimpleInfo($filter, $cols);
            if (!$itemsInfo) {
                throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
            }
            $old_nospec = $itemsInfo['nospec'] === true ? 'true' : 'false';
            if ($itemsInfo['nospec'] != $params['nospec']) {
                throw new ErrorException(ErrorCode::GOODS_ERROR, '多规格和单规格不能切换');
            }
            $params['item_id'] = $itemsInfo['item_id'];
            unset($params['old_item_bn']);
            $result = $itemsService->addItems($params);
        } catch (\Exception $e) {
            throw new ErrorException(ErrorCode::GOODS_ERROR, $e->getMessage());
        }
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.items.entity.get",
     *     summary="实体商品列表搜索",
     *     tags={"商品"},
     *     description="可以根据商品状态、品牌、分类查询实体商品列表数据（返回SPU列表）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.items.entity.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="approve_status", description="商品状态 onsale:前台可销售;offline_sale:前端不展示;instock:不可销售;only_show:前台仅展示;" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="brand_id", description="品牌ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_id", description="分类ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_begin", description="查询商品更新开始时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="time_end", description="查询商品更新结束时间 2019-09-01 00:00:00" ),
     *     @SWG\Parameter( in="query", type="boolean", required=false, name="is_self", description="是否自营" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="sku_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5971", description="商品ID"),
     *                          @SWG\Property( property="item_bn", type="string", example="S60E525795F83C", description="货号"),
     *                          @SWG\Property( property="item_name", type="string", example="热销商品4", description="商铺你名称"),
     *                          @SWG\Property( property="price", type="number", example="900", description="商品价格"),
     *                          @SWG\Property( property="store", type="number", example="100", description="商品库存"),
     *                          @SWG\Property( property="approve_status", type="string", example="onsale", description="上架状态"),
     *                          @SWG\Property( property="nospec", type="boolean", example="true", description="是否多规格"),
     *                          @SWG\Property( property="category_name", type="array", description="商品分类", @SWG\Items(type="string")),
     *                          @SWG\Property( property="is_self", type="boolean", example="true", description="是否自营"),
     *                          @SWG\Property( property="update_time", type="string", example="1644989675", description="更新时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'approve_status', 'brand_id', 'category_id', 'time_begin', 'time_end', 'is_self');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        $filter = [
            'company_id' => $companyId,
            'item_type' => 'normal',
            'is_default' => true,
        ];
        if ($params['approve_status']) {
            $filter['approve_status'] = $params['approve_status'];
        }
        if ($params['brand_id']) {
            $filter['brand_id'] = $params['brand_id'];
        }
        if ($params['category_id']) {
            $filter['category_id'] = $params['category_id'];
        }

        if (isset($params['time_begin'], $params['time_end']) && $params['time_begin'] && $params['time_end']) {
            if (strtotime($params['time_begin']) > strtotime($params['time_end'])) {
                throw new ErrorException(ErrorCode::ORDER_AFTERSALES_HANDLE_ERROR, '开始时间不能大于结束时间');
            }
        }
        if (isset($params['time_begin']) && $params['time_begin']) {
            $filter['updated|gte'] = strtotime($params['time_begin']);
        }
        if (isset($params['time_end']) && $params['time_end']) {
            $filter['updated|lte'] = strtotime($params['time_end']);
        }

        if (isset($params['is_self'])) {
            if ($params['is_self'] == 'true') {
                $filter['distributor_id'] = 0;
            } else {
                $filter['distributor_id|gt'] = 0;   
            }
        }

        $itemsService = new ItemsService();
        $result = $itemsService->getItemsList($filter, $params['page'], $params['page_size']);
        $openapiItemsService = new OpenapiItemsService();
        $return = $openapiItemsService->formateItemsList($companyId, $result, (int)$params['page'], (int)$params['page_size']);
        return $this->response->array($return);
    }
}
