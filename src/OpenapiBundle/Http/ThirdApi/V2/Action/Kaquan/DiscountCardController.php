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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Kaquan;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller;
use Swagger\Annotations as SWG;

use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Traits\DiscountCard\DiscountCardTrait;
use OpenapiBundle\Services\Kaquan\DiscountCardService;

/**
 * 优惠券相关
 * Class DiscountCardController
 * @package OpenapiBundle\Http\Api\V2\Action\Kaquan
 */
class DiscountCardController extends Controller
{

    use DiscountCardTrait;

    public function __construct()
    {
        $this->service = new DiscountCardService();
    }
    /**
     * @SWG\Post(
     *     path="/ecx.discountcard.list",
     *     tags={"优惠券"},
     *     summary="优惠券 - 查询",
     *     description="优惠券 - 查询",
     *     operationId="getDiscountCardList",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.discountcard.list" ),
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页面，从1开始计数（不填默认1）", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）,最大为500", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="string", default="10", description="列表数据总数量"),
     *               @SWG\Property(property="is_last_page", type="string", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property(property="pager", type="object", description="分页相关信息",
     *                   @SWG\Property(property="page", type="string", default="1", description="当前页数"),
     *                   @SWG\Property(property="page_size", type="string", default="20", description="每页显示数量"),
     *               ),
     *               @SWG\Property(property="list", type="array", description="列表信息（集合）",
     *                   @SWG\Items(type="object",
     *                       @SWG\Property(property="card_id", type="string", default="1", description="ID"),
     *                       @SWG\Property(property="card_type", type="string", default="discount", description="卡券类型,discount:折扣券;cash:满减券;"),
     *                       @SWG\Property(property="title", type="string", default="满500元打8折", description="卡券名称"),
     *                       @SWG\Property(property="description", type="string", default="满500元打8折", description="卡券使用优惠说明"),
     *                       @SWG\Property(property="discount", type="string", default="80", description="折扣券打折额度（百分比)"),
     *                       @SWG\Property(property="reduce_cost", type="string", default="80", description="满减券减免金额（单位:元）"),
     *                       @SWG\Property(property="date_type", type="string", default="discount", description="有效期的类型,DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                       @SWG\Property(property="begin_time", type="string", default="1728662400", description="有效期-开始时间。date_type=DATE_TYPE_FIX_TIME_RANGE时为时间戳；date_type=DATE_TYPE_FIX_TERM时为固定天数；"),
     *                       @SWG\Property(property="end_time", type="string", default="1738252800", description="有效期-结束时间。date_type=DATE_TYPE_FIX_TIME_RANGE时为时间戳；date_type=DATE_TYPE_FIX_TERM时为统一过期时间，未设置统一过期时间时为0；"),
     *                       @SWG\Property(property="fixed_term", type="string", default="30", description="有效期的有效天数，固定期限类型的为null"),
     *                       @SWG\Property(property="least_cost", type="string", default="500", description="优惠券起用金额（单位:元）"),
     *                       @SWG\Property(property="most_cost", type="string", default="500", description="优惠券最高消费限额（单位:元）。card_type=discount时有值"),
     *                       @SWG\Property(property="use_all_items", type="string", default="true", description="适用商品，true:全部商品；false:部分商品；category:指定商品管理分类；tag:指定商品标签；brand:指定商品品牌"),
     *                       @SWG\Property(property="rel_data", type="object", default="", description="适用商品数据"),
     *                   ),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getDiscountCardList(Request $request)
    {
        $params = $request->all();
        $page = $this->getPage();
        $page_size = $this->getPageSize();
        $filter = [
            'company_id' => $this->getCompanyId(),
        ];
        $result = $this->service->list($filter, $page, $page_size);
        $this->handleDataToList($result["list"]);
        $this->api_response('true', '操作成功', $result, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.discountcard.send",
     *     tags={"优惠券"},
     *     summary="优惠券 - 单张券发放",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="userGetCard",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.discountcard.send" ),
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="plat_account", in="query", description="商派会员Id", required=true, type="string"),
     *     @SWG\Parameter(name="card_id", in="query", description="优惠券ID", required=true, type="string"),
     *     @SWG\Parameter(name="activity_name", in="query", description="参与活动名称", required=true, type="string"),
     *     @SWG\Parameter(name="source_type", in="query", description="卡券来源文字描述，CRM本地领取;商城本地领取;CRM后台发放;商城后台发放;", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", example="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",
     *                 @SWG\Property(property="code", type="string", default="115599190956", description="卡券code"),
     *                 @SWG\Property(property="begin_date", type="string", default="1728662400", description="有效期开始时间"),
     *                 @SWG\Property(property="end_date", type="string", default="1728662400", description="有效期结束时间"),
     *                 @SWG\Property(property="status", type="string", default="", description="用户领取的优惠券使用状态，unused:未使用;redeemed:已核销;expired:已过期;"),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function userGetCard(Request $request)
    {
        $requestData = $request->all();
        if ($messageBag = validation($requestData, [
            "plat_account" => ["required"],
            "card_id" => ["required"],
            "source_type" => ["required"],
        ], [
            "plat_account.*" => "商派会员Id参数错误",
            "card_id.*" => "优惠券ID参数错误",
            "source_type.*" => "卡券来源参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $requestData['company_id'] = $this->getCompanyId();
        $result = $this->service->userSendDiscountCard($requestData);
        // 处理数据
        $this->handleDataToResult($result);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.userdiscount.list",
     *     tags={"优惠券"},
     *     summary="会员优惠券列表 - 查询",
     *     description="会员的已领取、已发放的优惠券列表 - 查询",
     *     operationId="getUserDiscountList",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.userdiscount.list" ),
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *     @SWG\Parameter(name="page", in="query", description="当前页面，从1开始计数（不填默认1）", required=false, type="integer"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页显示数量（不填默认20条）,最大为500", required=false, type="integer"),
     *     @SWG\Parameter(name="plat_account", in="query", description="商派会员Id", required=true, type="string"),
     *     @SWG\Parameter(name="code", in="query", description="卡券code", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="success", description=""),
     *             @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="total_count", type="string", default="10", description="列表数据总数量"),
     *               @SWG\Property(property="is_last_page", type="string", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property(property="pager", type="object", description="分页相关信息",
     *                   @SWG\Property(property="page", type="string", default="1", description="当前页数"),
     *                   @SWG\Property(property="page_size", type="string", default="20", description="每页显示数量"),
     *               ),
     *               @SWG\Property(property="list", type="array", description="列表信息（集合）",
     *                   @SWG\Items(type="object",
     *                       @SWG\Property(property="code", type="string", default="115599190956", description="卡券code"),
     *                       @SWG\Property(property="plat_account", type="string", default="1", description="商派会员Id"),
     *                       @SWG\Property(property="card_id", type="string", default="1", description="卡券ID"),
     *                       @SWG\Property(property="begin_date", type="string", default="1728662400", description="有效期开始时间"),
     *                       @SWG\Property(property="end_date", type="string", default="1728662400", description="有效期结束时间"),
     *                       @SWG\Property(property="status", type="string", default="", description="用户领取的优惠券使用状态，unused:未使用;redeemed:已核销;expired:已过期;"),
     *                   ),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getUserDiscountList(Request $request)
    {
        $requestData = $request->all();
        if ($messageBag = validation($requestData, [
            "plat_account" => ["required"],
        ], [
            "plat_account.*" => "商派会员Id参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $filter = [
            'company_id' => $this->getCompanyId(),
            'user_id' => $requestData['plat_account'],
        ];
        if (isset($requestData['code']) && $requestData['code']) {
            $filter['code'] = $requestData['code'];
        }
        $page = $this->getPage();
        $pageSize = $this->getPageSize();
        $result = $this->service->getUserDiscountList($filter, $page, $pageSize);
        $this->handleUserDataToList($result['list']);
        return $this->response->array($result);
    }

}
