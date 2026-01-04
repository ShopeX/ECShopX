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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Distributor;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Distributor\DistributorItemFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Services\Distributor\DistributorItemService;
use OpenapiBundle\Services\Distributor\DistributorService;
use OpenapiBundle\Traits\Distributor\DistributorItemTrait;
use Swagger\Annotations as SWG;
use WechatBundle\Services\WeappService;

/**
 * 店铺商品
 * Class DistributorItemController
 * @package OpenapiBundle\Http\ThirdApi\V2\Action\Distributor
 */
class DistributorItemController extends Controller
{
    use DistributorItemTrait;

    /**
     * @SWG\Get(
     *     path="/ecx.distributor_item.list",
     *     tags={"店铺"},
     *     summary="店铺商品信息 - 查询列表",
     *     description="店铺商品信息 - 查询列表",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="item_code", in="query", description="商品货号", required=false, type="string"),
     *     @SWG\Parameter(name="item_name", in="query", description="商品名称", required=false, type="string"),
     *     @SWG\Parameter(name="goods_can_sale", in="query", description="店铺中spu商品是否上架（0未上架，1已上架）", required=false, type="string"),
     *     @SWG\Parameter(name="is_total_store", in="query", description="店铺商品是否总部发货（0否，1是）", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="query", description="商品状态（onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show 前台仅展示）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="list", type="array", description="店铺商品列表信息（集合）",
     *                 @SWG\Items(required={"distributor_id","item_id","item_code","item_name","store","price","is_can_sale","is_total_store","status"},
     *                           @SWG\Property(property="distributor_id", type="integer", default="151", description="店铺ID"),
     *                           @SWG\Property(property="item_id", type="integer", default="6007", description="商品ID"),
     *                           @SWG\Property(property="item_code", type="string", default="S60EC3A0858CDB", description="商品货号"),
     *                           @SWG\Property(property="item_name", type="string", default="订单", description="商品名称"),
     *                           @SWG\Property(property="store", type="integer", default="11", description="商品库存"),
     *                           @SWG\Property(property="price", type="string", default="3.00", description="商品价格"),
     *                           @SWG\Property(property="goods_can_sale", type="integer", default="0", description="（spu纬度）店铺商品是否上架（0未上架，1已上架）"),
     *                           @SWG\Property(property="is_total_store", type="integer", default="1", description="商品是否总部发货（0否，1是）"),
     *                           @SWG\Property(property="status", type="string", default="onsale", description="商品状态（onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show 前台仅展示）"),
     *                 ),
     *               ),
     *              @SWG\Property(property="total_count", type="integer", default="96", description="列表数据总数量"),
     *              @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *              @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                   @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                   @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *              ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 获取参数并做验证
        $requestData = $request->all();
        if (!isset($requestData["shop_code"])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, "店铺号参数错误");
        }
        // 过滤条件
        $filter = (new DistributorItemFilter($requestData))->get();
        // 设置店铺信息
        $distributorInfo = [];
        $result = (new DistributorItemService())->itemSpuList($filter, $this->getPage(), $this->getPageSize(), ["item_id" => "DESC"], "*", true, $distributorInfo);
        if (!empty($result["list"])) {
            $this->handleDataToList($result["list"], $filter, $distributorInfo);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.distributor_item.update",
     *     tags={"店铺"},
     *     summary="店铺商品信息 - 更新",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="item_code", in="query", description="商品货号", required=true, type="string"),
     *     @SWG\Parameter(name="is_can_sale", in="query", description="店铺商品是否上架（0未上架、1已上架）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_total_store", in="query", description="店铺商品是否总部发货（0否、1是，当前只支持更新spu级商品）", required=false, type="integer"),
     *     @SWG\Parameter(name="store", in="query", description="店铺商品库存（若总部发货, 商品库存将不会更新）", required=false, type="integer"),
     *     @SWG\Parameter(name="price", in="query", description="店铺商品价格（若总部发货, 商品库存将不会更新），单位是元", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", example="success", description=""),
     *             @SWG\Property(property="data", type="string", example="", description=""),
     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        $requestData = $request->only(["shop_code", "item_code", "is_can_sale", "is_total_store", "store", "price"]);
        if ($messageBag = validation($requestData, [
            "shop_code" => ["required"],
            "item_code" => ["required"],
            "is_can_sale" => ["nullable", "integer", Rule::in([0, 1])],
            "is_total_store" => ["nullable", "integer", Rule::in([0, 1])],
            "store" => ["nullable"],
            "price" => ["nullable"],
        ], [
            "shop_code.*" => "店铺号参数有误",
            "item_code.*" => "商品货号参数错误",
            "is_can_sale.*" => "店铺商品是否上架参数错误",
            "is_total_store.*" => "店铺商品是否总部发货参数错误",
            "store.*" => "店铺商品库存参数错误",
            "price.*" => "店铺商品价格参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 过滤条件
        $filter = (new DistributorItemFilter($requestData))->get();
        (new DistributorItemService())->save($filter, $requestData);
        return $this->response->array([]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.distributor_item.download",
     *     tags={"店铺"},
     *     summary="店铺商品信息 - 生成店铺码",
     *     description="店铺信息生成店铺码",
     *     operationId="download",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="item_code", in="query", description="商品货号", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", example="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"url"},
     *               @SWG\Property(property="url", type="integer", default="http://xxx.com/xxxx", description="店铺商品的二维码信息"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function download(Request $request)
    {
        $requestData = $request->only(["shop_code", "item_code"]);
        if ($messageBag = validation($requestData, [
            "shop_code" => ["required"],
            "item_code" => ["required"],
        ], [
            "shop_code.*" => "店铺号参数有误",
            "item_code.*" => "商品货号参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $requestData["company_id"] = $this->getCompanyId();

        // 获取小程序的app id
        $wxAppid = (new WeappService())->getWxappidByTemplateName($requestData["company_id"]);
        if (!$wxAppid) {
            throw new ResourceException('没有开通此小程序，不能下载');
        }

        // 查询店铺
        $distributorInfo = (new DistributorService())->findByIdOrCode($requestData);
        if (empty($distributorInfo)) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND, "未查询到该店铺");
        }

        // 查询商品
        $itemsService = new ItemsService();
        $item = $itemsService->getItem(["company_id" => $this->getCompanyId(), "item_bn" => $requestData["item_code"]]);
        if (empty($item)) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }

        $result = (new DistributorItemService())->getQRCodeUrl($this->getCompanyId(), $wxAppid, (int)$distributorInfo['distributor_id'], (int)$item["item_id"]);
//        $result = $itemsService->getDistributionGoodsWxaCode($wxAppid, $item['item_id'], $distributorInfo['distributor_id'], 1);
        return $this->response->array($result);
    }
}
