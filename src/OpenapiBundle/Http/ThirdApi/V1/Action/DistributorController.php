<?php

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Distributor\DistributorFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Rules\MobileRule;
use OpenapiBundle\Services\Distributor\DistributorService;
use OpenapiBundle\Traits\Distributor\DistributorTrait;
use Swagger\Annotations as SWG;
use WechatBundle\Services\WeappService;

/**
 * 店铺信息
 * Class DistributorController
 * @package OpenapiBundle\Http\ThirdApi\V1\Action
 */
class DistributorController extends Controller
{
    use DistributorTrait;

    /**
     * @SWG\Get(
     *     path="/ecx.distributor.list",
     *     tags={"店铺"},
     *     summary="店铺信息 - 查询列表",
     *     description="店铺信息 - 查询列表",
     *     operationId="list",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="distributor_name", in="query", description="店铺名称", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="query", description="店铺状态（0废弃、1启用、2禁用）", required=false, type="integer"),
     *     @SWG\Parameter(name="province", in="query", description="省（店铺所在省，需按管理后台对应标准名称进行填写）", required=false, type="string"),
     *     @SWG\Parameter(name="city", in="query", description="市（店铺所在省，需按管理后台对应标准名称进行填写）", required=false, type="string"),
     *     @SWG\Parameter(name="area", in="query", description="区（店铺所在省，需按管理后台对应标准名称进行填写）", required=false, type="string"),
     *     @SWG\Parameter(name="contact_username", in="query", description="店铺联系人姓名", required=false, type="string"),
     *     @SWG\Parameter(name="contact_mobile", in="query", description="店铺联系人手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", default="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"total_count","list","is_last_page","pager"},
     *               @SWG\Property(property="total_count", type="integer", default="96", description="列表数据总数量"),
     *               @SWG\Property(property="list", type="array", description="",
     *                 @SWG\Items(required={"distributor_id","shop_code","status","distributor_name","contact_username","contact_mobile","province","city","area","region_codes","region_names","address","lng","lat","hour","logo","is_ziti","is_delivery","is_auto_sync_goods","is_dada","is_default","created","updated"},
     *                           @SWG\Property(property="distributor_id", type="integer", default="150", description="店铺ID"),
     *                           @SWG\Property(property="shop_code", type="string", default="787877787", description="店铺号"),
     *                           @SWG\Property(property="status", type="integer", default="2", description="店铺状态（0废弃、1启用、2禁用）"),
     *                           @SWG\Property(property="distributor_name", type="string", default="333", description="店铺名称"),
     *                           @SWG\Property(property="contact_username", type="string", default="333", description="联系人姓名"),
     *                           @SWG\Property(property="contact_mobile", type="string", default="0", description="联系方式"),
     *                           @SWG\Property(property="province", type="string", default="上海市", description="省（店铺所在省，需按管理后台对应标准名称进行填写）"),
     *                           @SWG\Property(property="city", type="string", default="上海市", description="市（店铺所在市，需按管理后台对应标准名称进行填写）"),
     *                           @SWG\Property(property="area", type="string", default="徐汇区", description="区（店铺所在区，需按管理后台对应标准名称进行填写）"),
     *                           @SWG\Property(property="region_codes", type="array", description="字符串数组，国家行政区划编码（数组：省,市,区）",
     *                             @SWG\Items(
     *                             ),
     *                           ),
     *                           @SWG\Property(property="region_names", type="array", description="字符串数组，行政区划名称（数组：省,市,区）",
     *                             @SWG\Items(
     *                             ),
     *                           ),
     *                           @SWG\Property(property="address", type="string", default="上海市徐汇区宜山路桂林路站4号口内", description="详细地址（店铺所在详细地址）"),
     *                           @SWG\Property(property="lng", type="string", default="121.416804", description="经度（店铺所在地址经度）"),
     *                           @SWG\Property(property="lat", type="string", default="31.174823", description="纬度（店铺所在地址经度）"),
     *                           @SWG\Property(property="hour", type="string", default="08:00-21:00", description="经营时间（开始-结束）"),
     *                           @SWG\Property(property="logo", type="string", default="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreP1UY5dVXI22yAOxvMPHLC0iaUkhrlhJsBVHPRABGoTCn8ptRQpvso3lzTf8xjVbemhTFl2zMNHyg/0?wx_fmt=png", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）"),
     *                           @SWG\Property(property="is_ziti", type="integer", default="1", description="是否支持自提（0否，1是，默认0）"),
     *                           @SWG\Property(property="is_delivery", type="integer", default="1", description="是否支持快递（0否，1是，默认1）	"),
     *                           @SWG\Property(property="is_auto_sync_goods", type="integer", default="1", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）"),
     *                           @SWG\Property(property="is_dada", type="integer", default="0", description="是否开启同城配（0否，1是，默认0）"),
     *                           @SWG\Property(property="is_default", type="integer", default="0", description="是否默认店铺（0否，1是，默认0）"),
     *                           @SWG\Property(property="created", type="string", default="2021-07-07 22:22:43", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                           @SWG\Property(property="updated", type="string", default="2021-07-07 22:40:09", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *                 ),
     *               ),
     *               @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *               @SWG\Property(property="pager", type="object", description="分页相关信息",required={"page","page_size"},
     *                    @SWG\Property(property="page", type="integer", default="1", description="当前页数"),
     *                    @SWG\Property(property="page_size", type="integer", default="10", description="每页显示数量（默认20条）"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function list()
    {
        // 设置过滤条件
        $filter = (new DistributorFilter())->get();
        // 列表查询
        $result = (new DistributorService())->list($filter, $this->getPage(), $this->getPageSize(), ["distributor_id" => "DESC"]);
        // 处理数据
        $this->handleDataToList($result["list"]);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.distributor.create",
     *     tags={"店铺"},
     *     summary="店铺信息 - 创建",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="create",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="formData", description="店铺号", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_name", in="formData", description="店铺名称", required=true, type="string"),
     *     @SWG\Parameter(name="contact_username", in="formData", description="联系人姓名", required=true, type="string"),
     *     @SWG\Parameter(name="contact_mobile", in="formData", description="联系方式", required=true, type="string"),
     *     @SWG\Parameter(name="hour", in="formData", description="经营时间（开始-结束）", required=false, type="string"),
     *     @SWG\Parameter(name="is_ziti", in="formData", description="是否支持自提（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_delivery", in="formData", description="是否支持快递（0否，1是，默认1）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_auto_sync_goods", in="formData", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_dada", in="formData", description="是否开启同城配（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_default", in="formData", description="是否默认店铺（0否，1是，默认0）	", required=false, type="integer"),
     *     @SWG\Parameter(name="logo", in="formData", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="integer", example="200", description=""),
     *            @SWG\Property(property="message", type="string", example="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"distributor_id","shop_code","status","distributor_name","contact_username","contact_mobile","province","city","area","region_codes","region_names","address","lng","lat","hour","logo","is_ziti","is_delivery","is_auto_sync_goods","is_dada","is_default","created","updated"},
     *               @SWG\Property(property="distributor_id", type="integer", default="150", description="店铺ID"),
     *               @SWG\Property(property="shop_code", type="string", default="787877787", description="店铺号"),
     *               @SWG\Property(property="status", type="integer", default="2", description="店铺状态（0废弃、1启用、2禁用）"),
     *               @SWG\Property(property="distributor_name", type="string", default="333", description="店铺名称"),
     *               @SWG\Property(property="contact_username", type="string", default="333", description="联系人姓名"),
     *               @SWG\Property(property="contact_mobile", type="string", default="0", description="联系方式"),
     *               @SWG\Property(property="province", type="string", default="上海市", description="省（店铺所在省，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="city", type="string", default="上海市", description="市（店铺所在市，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="area", type="string", default="徐汇区", description="区（店铺所在区，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="region_codes", type="array", description="字符串数组，国家行政区划编码（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="region_names", type="array", description="字符串数组，行政区划名称（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="address", type="string", default="上海市徐汇区宜山路桂林路站4号口内", description="详细地址（店铺所在详细地址）"),
     *               @SWG\Property(property="lng", type="string", default="121.416804", description="经度（店铺所在地址经度）"),
     *               @SWG\Property(property="lat", type="string", default="31.174823", description="纬度（店铺所在地址经度）"),
     *               @SWG\Property(property="hour", type="string", default="08:00-21:00", description="经营时间（开始-结束）"),
     *               @SWG\Property(property="logo", type="string", default="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreP1UY5dVXI22yAOxvMPHLC0iaUkhrlhJsBVHPRABGoTCn8ptRQpvso3lzTf8xjVbemhTFl2zMNHyg/0?wx_fmt=png", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）"),
     *               @SWG\Property(property="is_ziti", type="integer", default="1", description="是否支持自提（0否，1是，默认0）"),
     *               @SWG\Property(property="is_delivery", type="integer", default="1", description="是否支持快递（0否，1是，默认1）	"),
     *               @SWG\Property(property="is_auto_sync_goods", type="integer", default="1", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）"),
     *               @SWG\Property(property="is_dada", type="integer", default="0", description="是否开启同城配（0否，1是，默认0）"),
     *               @SWG\Property(property="is_default", type="integer", default="0", description="是否默认店铺（0否，1是，默认0）"),
     *               @SWG\Property(property="created", type="string", default="2021-07-07 22:22:43", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *               @SWG\Property(property="updated", type="string", default="2021-07-07 22:40:09", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function create(Request $request)
    {
        $requestData = $request->all();
        if ($messageBag = validation($requestData, [
            "shop_code" => ["required", "regex:/^[A-Za-z0-9-]+$/"],
            "distributor_name" => ["required"],
            "contact_username" => ["required"],
            "contact_mobile" => ["required", new MobileRule()],
            "hour" => ["nullable"],
            "is_ziti" => ["nullable", "integer", Rule::in([0, 1])],
            "is_delivery" => ["nullable", "integer", Rule::in([0, 1])],
            "is_auto_sync_goods" => ["nullable", "integer", Rule::in([0, 1])],
            "is_dada" => ["nullable", "integer", Rule::in([0, 1])],
            "is_default" => ["nullable", "integer", Rule::in([0, 1])],
            "logo" => ["nullable", "string"],
        ], [
            "shop_code.*" => "店铺号参数错误",
            "distributor_name.*" => "店铺名称参数错误",
            "contact_username.*" => "联系人姓名参数错误",
            "contact_mobile.*" => "联系方式参数错误",
            "hour.*" => "经营时间（开始-结束）参数错误",
            "is_ziti.*" => "是否支持自提参数错误",
            "is_delivery.*" => "是否支持快递参数错误",
            "is_auto_sync_goods.*" => "是否自动同步总部商品参数错误",
            "is_dada.*" => "是否开启同城配参数错误",
            "is_default.*" => "是否默认店铺参数错误",
            "logo.*" => "店铺Logo图片Url参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 添加企业id
        $requestData["company_id"] = $this->getCompanyId();
        $result = (new DistributorService())->create($requestData);
        // 处理数据
        $list[] = &$result;
        $this->handleDataToList($list);
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/ecx.distributor.update",
     *     tags={"店铺"},
     *     summary="店铺信息 - 更新",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="update",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="formData", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="distributor_name", in="formData", description="店铺名称", required=false, type="string"),
     *     @SWG\Parameter(name="contact_username", in="formData", description="联系人姓名", required=false, type="string"),
     *     @SWG\Parameter(name="contact_mobile", in="formData", description="联系方式", required=false, type="string"),
     *     @SWG\Parameter(name="hour", in="formData", description="经营时间（开始-结束）", required=false, type="string"),
     *     @SWG\Parameter(name="is_ziti", in="formData", description="是否支持自提（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_delivery", in="formData", description="是否支持快递（0否，1是，默认1）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_auto_sync_goods", in="formData", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_dada", in="formData", description="是否开启同城配（0否，1是，默认0）", required=false, type="integer"),
     *     @SWG\Parameter(name="is_default", in="formData", description="是否默认店铺（0否，1是，默认0）	", required=false, type="integer"),
     *     @SWG\Parameter(name="logo", in="formData", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="formData", description="店铺状态（0废弃、1启用、2禁用）", required=false, type="integer"),
     *     @SWG\Parameter(name="province", in="formData", description="省（店铺所在省，省、市、区需同时修改）", required=false, type="string"),
     *     @SWG\Parameter(name="city", in="formData", description="市（店铺所在省，省、市、区需同时修改）", required=false, type="string"),
     *     @SWG\Parameter(name="area", in="formData", description="区（店铺所在省，省、市、区需同时修改）", required=false, type="string"),
     *     @SWG\Parameter(name="address", in="formData", description="详细地址（店铺所在详细地址）", required=false, type="string"),
     *     @SWG\Parameter(name="lng", in="formData", description="经度（店铺所在地址经度）", required=false, type="string"),
     *     @SWG\Parameter(name="lat", in="formData", description="纬度（店铺所在地址经度）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", example="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"distributor_id","shop_code","status","distributor_name","contact_username","contact_mobile","province","city","area","region_codes","region_names","address","lng","lat","hour","logo","is_ziti","is_delivery","is_auto_sync_goods","is_dada","is_default","created","updated"},
     *               @SWG\Property(property="distributor_id", type="integer", default="150", description="店铺ID"),
     *               @SWG\Property(property="shop_code", type="string", default="787877787", description="店铺号"),
     *               @SWG\Property(property="status", type="integer", default="2", description="店铺状态（0废弃、1启用、2禁用）"),
     *               @SWG\Property(property="distributor_name", type="string", default="333", description="店铺名称"),
     *               @SWG\Property(property="contact_username", type="string", default="333", description="联系人姓名"),
     *               @SWG\Property(property="contact_mobile", type="string", default="0", description="联系方式"),
     *               @SWG\Property(property="province", type="string", default="上海市", description="省（店铺所在省，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="city", type="string", default="上海市", description="市（店铺所在市，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="area", type="string", default="徐汇区", description="区（店铺所在区，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="region_codes", type="array", description="字符串数组，国家行政区划编码（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="region_names", type="array", description="字符串数组，行政区划名称（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="address", type="string", default="上海市徐汇区宜山路桂林路站4号口内", description="详细地址（店铺所在详细地址）"),
     *               @SWG\Property(property="lng", type="string", default="121.416804", description="经度（店铺所在地址经度）"),
     *               @SWG\Property(property="lat", type="string", default="31.174823", description="纬度（店铺所在地址经度）"),
     *               @SWG\Property(property="hour", type="string", default="08:00-21:00", description="经营时间（开始-结束）"),
     *               @SWG\Property(property="logo", type="string", default="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreP1UY5dVXI22yAOxvMPHLC0iaUkhrlhJsBVHPRABGoTCn8ptRQpvso3lzTf8xjVbemhTFl2zMNHyg/0?wx_fmt=png", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）"),
     *               @SWG\Property(property="is_ziti", type="integer", default="1", description="是否支持自提（0否，1是，默认0）"),
     *               @SWG\Property(property="is_delivery", type="integer", default="1", description="是否支持快递（0否，1是，默认1）	"),
     *               @SWG\Property(property="is_auto_sync_goods", type="integer", default="1", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）"),
     *               @SWG\Property(property="is_dada", type="integer", default="0", description="是否开启同城配（0否，1是，默认0）"),
     *               @SWG\Property(property="is_default", type="integer", default="0", description="是否默认店铺（0否，1是，默认0）"),
     *               @SWG\Property(property="created", type="string", default="2021-07-07 22:22:43", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *               @SWG\Property(property="updated", type="string", default="2021-07-07 22:40:09", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function update(Request $request)
    {
        $requestData = $request->all();
        if ($messageBag = validation($requestData, [
            "shop_code" => ["nullable", "regex:/^[A-Za-z0-9-]+$/"],
            "distributor_name" => ["nullable"],
            "contact_username" => ["nullable"],
            "contact_mobile" => ["nullable", new MobileRule()],
            "hour" => ["nullable"],
            "is_ziti" => ["nullable", "integer", Rule::in([0, 1])],
            "is_delivery" => ["nullable", "integer", Rule::in([0, 1])],
            "is_auto_sync_goods" => ["nullable", "integer", Rule::in([0, 1])],
            "is_dada" => ["nullable", "integer", Rule::in([0, 1])],
            "is_default" => ["nullable", "integer", Rule::in([0, 1])],
            "logo" => ["nullable", "string"],
            "status" => ["nullable", "integer", Rule::in(array_keys(DistributorService::STATUS_MAP))],
            "province" => ["nullable", "string"],
            "city" => ["nullable", "string"],
            "area" => ["nullable", "string"],
            "address" => ["nullable", "string"],
            "lng" => ["nullable", "numeric"],
            "lat" => ["nullable", "numeric"],
        ], [
            "shop_code.*" => "店铺号参数错误",
            "distributor_name.*" => "店铺名称参数错误",
            "contact_username.*" => "联系人姓名参数错误",
            "contact_mobile.*" => "联系方式参数错误",
            "hour.*" => "经营时间（开始-结束）参数错误",
            "is_ziti.*" => "是否支持自提参数错误",
            "is_delivery.*" => "是否支持快递参数错误",
            "is_auto_sync_goods.*" => "是否自动同步总部商品参数错误",
            "is_dada.*" => "是否开启同城配参数错误",
            "is_default.*" => "是否默认店铺参数错误",
            "logo.*" => "店铺Logo图片Url参数错误",
            "status.*" => "店铺状态参数错误",
            "province.*" => "省参数错误",
            "city.*" => "市参数错误",
            "area.*" => "区参数错误",
            "address.*" => "详细地址参数错误",
            "lng.*" => "经度参数错误",
            "lat.*" => "纬度参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        // 添加企业id
        $requestData["company_id"] = $this->getCompanyId();
        // 过滤条件
        $filter = [
            "company_id" => $this->getCompanyId(),
            "shop_code" => $requestData["shop_code"]
        ];
        $result = (new DistributorService())->updateDetail($filter, $requestData);
        // 处理数据
        $list[] = &$result;
        $this->handleDataToList($list);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.distributor.detail",
     *     tags={"店铺"},
     *     summary="店铺信息 - 查询详情",
     *     description="店铺信息 - 查询详情",
     *     operationId="detail",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", example="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"distributor_id","shop_code","status","distributor_name","contact_username","contact_mobile","province","city","area","region_codes","region_names","address","lng","lat","hour","logo","is_ziti","is_delivery","is_auto_sync_goods","is_dada","is_default","created","updated"},
     *               @SWG\Property(property="distributor_id", type="integer", default="150", description="店铺ID"),
     *               @SWG\Property(property="shop_code", type="string", default="787877787", description="店铺号"),
     *               @SWG\Property(property="status", type="integer", default="2", description="店铺状态（0废弃、1启用、2禁用）"),
     *               @SWG\Property(property="distributor_name", type="string", default="333", description="店铺名称"),
     *               @SWG\Property(property="contact_username", type="string", default="333", description="联系人姓名"),
     *               @SWG\Property(property="contact_mobile", type="string", default="0", description="联系方式"),
     *               @SWG\Property(property="province", type="string", default="上海市", description="省（店铺所在省，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="city", type="string", default="上海市", description="市（店铺所在市，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="area", type="string", default="徐汇区", description="区（店铺所在区，需按管理后台对应标准名称进行填写）"),
     *               @SWG\Property(property="region_codes", type="array", description="字符串数组，国家行政区划编码（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="region_names", type="array", description="字符串数组，行政区划名称（数组：省,市,区）",
     *                 @SWG\Items(
     *                 ),
     *               ),
     *               @SWG\Property(property="address", type="string", default="上海市徐汇区宜山路桂林路站4号口内", description="详细地址（店铺所在详细地址）"),
     *               @SWG\Property(property="lng", type="string", default="121.416804", description="经度（店铺所在地址经度）"),
     *               @SWG\Property(property="lat", type="string", default="31.174823", description="纬度（店铺所在地址经度）"),
     *               @SWG\Property(property="hour", type="string", default="08:00-21:00", description="经营时间（开始-结束）"),
     *               @SWG\Property(property="logo", type="string", default="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkreP1UY5dVXI22yAOxvMPHLC0iaUkhrlhJsBVHPRABGoTCn8ptRQpvso3lzTf8xjVbemhTFl2zMNHyg/0?wx_fmt=png", description="店铺Logo图片Url（url地址需已加入小程序域名白名单）"),
     *               @SWG\Property(property="is_ziti", type="integer", default="1", description="是否支持自提（0否，1是，默认0）"),
     *               @SWG\Property(property="is_delivery", type="integer", default="1", description="是否支持快递（0否，1是，默认1）	"),
     *               @SWG\Property(property="is_auto_sync_goods", type="integer", default="1", description="店铺商品是否自动上架且总部发货（0否，1是，默认0）"),
     *               @SWG\Property(property="is_dada", type="integer", default="0", description="是否开启同城配（0否，1是，默认0）"),
     *               @SWG\Property(property="is_default", type="integer", default="0", description="是否默认店铺（0否，1是，默认0）"),
     *               @SWG\Property(property="created", type="string", default="2021-07-07 22:22:43", description="创建时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *               @SWG\Property(property="updated", type="string", default="2021-07-07 22:40:09", description="更新时间（日期格式:yyyy-MM-dd HH:mm:ss）"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        // 请求参数
        $requestData = $request->only(["shop_code"]);
        app('log')->info(__FUNCTION__.__LINE__.':'. json_encode(['requestData' => $requestData]));
        if ($messageBag = validation($requestData, [
            "shop_code" => "required",
        ], [
            "shop_code.*" => "店铺号必填"
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }
        $filter = (new DistributorFilter($requestData))->get();
        app('log')->info(__FUNCTION__.__LINE__.':'. json_encode(['filter' => $filter]));
        // 查询单条数据
        $result = (new DistributorService())->findByIdOrCode($filter);
        app('log')->info(__FUNCTION__.__LINE__.':'. json_encode(['result' => $result]));
        if (!empty($result)) {
            // 处理数据
            $list[] = &$result;
            $this->handleDataToList($list);
        } else {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
        }
        app('log')->info(__FUNCTION__.__LINE__.':'. json_encode(['result' => $result]));
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.distributor.download",
     *     tags={"店铺"},
     *     summary="店铺信息 - 生成店铺码",
     *     description="店铺信息 - 生成店铺码",
     *     operationId="download",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="shop_code", in="query", description="店铺号", required=false, type="string"),
     *     @SWG\Parameter(name="distributor_name", in="query", description="店铺名称", required=false, type="string"),
     *     @SWG\Parameter(name="status", in="query", description="店铺状态", required=false, type="integer"),
     *     @SWG\Parameter(name="template_name", in="query", description="微信小程序的模板名称", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"code","message","data"},
     *            @SWG\Property(property="code", type="string", default="E0000", description=""),
     *            @SWG\Property(property="message", type="string", example="success", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"url"},
     *               @SWG\Property(property="url", type="string", default="http://xxx.com/xxxxx", description="二维码的图片url"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function download(Request $request)
    {
        // 店铺服务
        $distributorService = new DistributorService();
        // 获取模板名称
        $templateName = (string)$request->input("template_name");
        // 获取店铺相关的筛选条件
        $requestData = $request->only(["shop_code", "distributor_name", "status"]);
        $filter = (new DistributorFilter($requestData))->get();
        $distributorInfo = $distributorService->find($filter);
        if (empty($distributorInfo)) {
            throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
        }
        // 获取微信小程序的appid
        $wxaappId = (new WeappService())->getWxappidByTemplateName($filter["company_id"], $templateName);
        if (!$wxaappId) {
            throw new ErrorException(ErrorCode::WECHAT_ERROR, "没有开通此小程序");
        }
        // 获取二维码图片的数据流
        $result = $distributorService->getQRCodeUrl($filter["company_id"], $wxaappId, (int)$distributorInfo['distributor_id']);
        return $this->response->array($result);
    }
}
