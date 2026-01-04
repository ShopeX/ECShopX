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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorsService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use DistributionBundle\Services\DistributorService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use Exception;
use CompanysBundle\Services\CompanysService;

class Shops extends BaseController
{
    /** @var OperatorsService */
    private $operatorsService;

    /**
     * Shops constructor.
     * @param OperatorsService  $operatorsService
     */
    public function __construct(OperatorsService $operatorsService)
    {
        $this->operatorsService = new $operatorsService();
    }

    /**
     * @SWG\Definition(
     *     definition="Wxshop",
     *     @SWG\Property(property="wx_shop_id", type="integer", example="1", description="自增id"),
     *     @SWG\Property(property="map_poi_id", type="string",example="18291581753863139488", description="位置点id"),
     *     @SWG\Property(property="store_name", type="string", example="1", description="门店名称"),
     *     @SWG\Property(property="poi_id", type="string", example="1", description="门店id"),
     *     @SWG\Property(property="lng", type="string", example="1", description="纬度"),
     *     @SWG\Property(property="lat", type="string", example="1", description="精度"),
     *     @SWG\Property(property="address", type="string", example="1", description="地址"),
     *     @SWG\Property(property="pic_list", type="string", example="1", description="门店图片"),
     *     @SWG\Property(property="contract_phone", type="string", example="1", description="联系电话"),
     *     @SWG\Property(property="hour", type="string", example="1", description="营业时间"),
     *     @SWG\Property(property="add_type", type="integer", example="1", description="类型"),
     *     @SWG\Property(property="credential", type="string", example="1", description="经营资质证件号"),
     *     @SWG\Property(property="company_name", type="string", example="1", description="公司名称"),
     *     @SWG\Property(property="qualification_list", type="string", example="1", description="相关证明材料"),
     *     @SWG\Property(property="card_id", type="string", example="1", description="卡券id"),
     *     @SWG\Property(property="status", type="integer", example="1", description="状态"),
     *     @SWG\Property(property="company_id", type="integer", example="1", description="公司id"),
     *     @SWG\Property(property="is_domestic", type="integer", example="1", description="是否是中国国内门店"),
     *     @SWG\Property(property="country", type="string", example="1", description="国家"),
     *     @SWG\Property(property="city", type="string", example="1", description="城市"),
     *     @SWG\Property(property="is_direct_store", type="integer", example="1", description="是否直营店"),
     *     @SWG\Property(property="resource_id", type="string", example="1", description="资源id"),
     *     @SWG\Property(property="resource_name", type="string", example="1", description="资源名"),
     *     @SWG\Property(property="is_open", type="boolean", example="1", description="是否开启"),
     *     @SWG\Property(property="expired_at", type="string", example="1", description="有效期"),
     *     @SWG\Property(property="qqmapimg", type="string", example="1", description=""),
     *     @SWG\Property(property="created", type="string", example="1", description="创建时间"),
     *     @SWG\Property(property="updated", type="string", example="1", description="更新时间"),
     * )
     */





    /**
     * @SWG\Post(
     *     path="/shops/wxshops",
     *     summary="添加微信门店",
     *     tags={"企业"},
     *     description="添加微信门店",
     *     operationId="createWxShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="map_poi_id",
     *         in="query",
     *         description="从腾讯地图换取的位置点id，即search_map_poi接口返回的sosomap_poi_uid字段",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="store_name",
     *         in="query",
     *         description="腾讯地图门店名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="纬度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="经度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="address",
     *         in="query",
     *         description="腾讯地图门店的地址",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="category",
     *         in="query",
     *         description="微信门店的经营类目",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pic_list",
     *         in="query",
     *         description="门店图片，可传多张图片,pic_list字段是一个json",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="contract_phone",
     *         in="query",
     *         description="联系电话,固定电话需加区号；区号、分机号均用“-”连接",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="hour",
     *         in="query",
     *         description="营业时间，格式11:11-12:12",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="add_type",
     *         in="query",
     *         description="经营资质主体,若地点的经营资质名称与帐号主体名称不一致，请选择相关主体。",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="credential",
     *         in="query",
     *         description="经营资质证件号,请填写15位营业执照注册号或9位组织机构代码（如12345678-9）或18位或20位统一社会信用代码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_name",
     *         in="query",
     *         description="主体名字 临时素材mediaid，如果复用公众号主体，则company_name为空，如果不复用公众号主体，则company_name为具体的主体名字",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="qualification_list",
     *         in="query",
     *         description="相关证明材料，临时素材mediaid，不复用公众号主体时，才需要填",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券id，如果不需要添加卡券，该参数可为空，目前仅开放支持会员卡、买单和刷卡支付券，不支持自定义code，需要先去公众平台卡券后台创建cardid",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="country",
     *         in="query",
     *         description="非中国国家名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         description="非中国国家城市名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_domestic",
     *         in="query",
     *         description="是否是中国国内门店",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_direct_store",
     *         in="query",
     *         description="是否为直营店",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Wxshop"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createWxShops(Request $request)
    {
        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            throw new ResourceException("您没有添加门店的权限", 400500);
        }

        $validator = app('validator')->make($request->all(), [
            'store_name' => 'required',
            'address' => 'required',
            'pic_list' => 'required',
            'contract_phone' => 'required|regex:/^\d{3,4}\-?\d{7,8}(\-?\d{2,6})?$/',
            'hour' => 'required',
            'add_type' => 'required|in:1,2,3',
            'company_name' => 'required_if:add_type,2|max:30',
            'credential' => 'max:30',
            'qualification_list' => 'required_if:add_type,2',
        ], [
            'store_name.*' => '请填写门店名称',
            'address.*' => '请填写门店经纬度',
            'pic_list.*' => '请上传门店图片',
            'contract_phone.required' => '请填写客服电话',
            'contract_phone.regex' => '客服电话格式错误',
            'hour.*' => '请选择营业时间',
            'add_type.*' => '请选择经营资质主体',
            'company_name.required_if' => '请填写经营资质名称',
            'company_name.max' => '经营资质名称长度超过限制',
            'credential.*' => '经营资质证件号长度超过限制',
            'qualification_list.*' => '请上传相关证明材料',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $data = $request->input();
        // 验证图片是否是url地址
        foreach ($data['pic_list'] as $v) {
            $tmp['pic'] = $v;
            $validator = app('validator')->make($tmp, [
                'pic' => 'required|url',
            ]);
            if ($validator->fails()) {
                throw new ResourceException('请填写正确图片地址.', $validator->errors());
            }
        }

        $shopsService = new ShopsService(new WxShopsService());
        $authInfo = app('auth')->user()->get();
        $data['company_id'] = $authInfo['company_id'];
        $data['pic_list'] = json_encode($data['pic_list']);
        $tmp_start = date('H:i', strtotime($data['hour'][0]));
        $tmp_end = date('H:i', strtotime($data['hour'][1]));
        $data['hour'] = $tmp_start . ' - ' . $tmp_end;
        $data['distributor_id'] = app('auth')->user()->get('distributor_id');
        $result = $shopsService->addShops($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/shops/wxshops/{wx_shop_id}",
     *     summary="更新微信门店",
     *     tags={"企业"},
     *     description="更新微信门店",
     *     operationId="createWxShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="path",
     *         description="系统中门店id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="map_poi_id",
     *         in="query",
     *         description="从腾讯地图换取的位置点id，即search_map_poi接口返回的sosomap_poi_uid字段",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="store_name",
     *         in="query",
     *         description="腾讯地图门店名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lng",
     *         in="query",
     *         description="纬度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="lat",
     *         in="query",
     *         description="经度",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pic_list",
     *         in="query",
     *         description="门店图片，可传多张图片,pic_list字段是一个json",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="contract_phone",
     *         in="query",
     *         description="联系电话",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="hour",
     *         in="query",
     *         description="营业时间，格式11:11-12:12",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="add_type",
     *         in="query",
     *         description="经营资质主体,若地点的经营资质名称与帐号主体名称不一致，请选择相关主体。",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="credential",
     *         in="query",
     *         description="经营资质证件号,请填写15位营业执照注册号或9位组织机构代码（如12345678-9）或18位或20位统一社会信用代码",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_name",
     *         in="query",
     *         description="主体名字 临时素材mediaid，如果复用公众号主体，则company_name为空，如果不复用公众号主体，则company_name为具体的主体名字",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="qualification_list",
     *         in="query",
     *         description="相关证明材料，临时素材mediaid，不复用公众号主体时，才需要填",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="card_id",
     *         in="query",
     *         description="卡券id，如果不需要添加卡券，该参数可为空，目前仅开放支持会员卡、买单和刷卡支付券，不支持自定义code，需要先去公众平台卡券后台创建cardid",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="country",
     *         in="query",
     *         description="非中国国家名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         description="非中国国家城市名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_domestic",
     *         in="query",
     *         description="是否是中国国内门店",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="is_direct_store",
     *         in="query",
     *         description="是否为直营店",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Wxshop"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateWxShops($wx_shop_id, Request $request)
    {
        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        $validator = app('validator')->make($request->all(), [
            'store_name' => 'required',
            'address' => 'required',
            'pic_list' => 'required',
            'contract_phone' => 'required|regex:/^\d{3,4}\-?\d{7,8}(\-?\d{2,6})?$/',
            'hour' => 'required',
            'add_type' => 'required|in:1,2,3',
            'company_name' => 'required_if:add_type,2|max:30',
            'credential' => 'max:30',
            'qualification_list' => 'required_if:add_type,2',
        ], [
            'store_name.*' => '请填写门店名称',
            'address.*' => '请填写门店经纬度',
            'pic_list.*' => '请上传门店图片',
            'contract_phone.required' => '请填写客服电话',
            'contract_phone.regex' => '客服电话格式错误',
            'hour.*' => '请选择营业时间',
            'add_type.*' => '请选择经营资质主体',
            'company_name.required_if' => '请填写经营资质名称',
            'company_name.max' => '经营资质名称长度超过限制',
            'credential.*' => '经营资质证件号长度超过限制',
            'qualification_list.*' => '请上传相关证明材料',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $data = $request->input();

        // 验证图片是否是url地址
        foreach ($data['pic_list'] as $v) {
            $tmp['pic'] = $v;
            $validator = app('validator')->make($tmp, [
                'pic' => 'required|url',
            ]);
            if ($validator->fails()) {
                throw new ResourceException('门店图片必填且要微信返回的正确图片地址.', $validator->errors());
            }
        }

        $shopsService = new ShopsService(new WxShopsService());
        $authInfo = app('auth')->user()->get();
        $data['company_id'] = $authInfo['company_id'];
        // $data['qualification_list'] = $data['qualification_list'];
        $data['pic_list'] = json_encode($data['pic_list']);
        $data['wx_shop_id'] = $wx_shop_id;
        $tmp_start = date('H:i', strtotime($data['hour'][0]));
        $tmp_end = date('H:i', strtotime($data['hour'][1]));
        $data['hour'] = $tmp_start . ' - ' . $tmp_end;
        $result = $shopsService->updateShops($data, []);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/shops/wxshops/setDefaultShop",
     *     summary="设置默认门店",
     *     tags={"企业"},
     *     description="设置默认门店",
     *     operationId="setDefaultShop",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="query",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean")
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setDefaultShop(request $request)
    {
        $wx_shop_id = $request->input('wx_shop_id');

        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        if (!$wx_shop_id) {
            return $this->response->error('门店必选！', 411);
        }
        $companyId = app('auth')->user()->get('company_id');
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->setDefaultWxShops($companyId, $wx_shop_id);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/shops/wxshops/setShopStatus",
     *     summary="设置门店状态",
     *     tags={"企业"},
     *     description="设置门店状态",
     *     operationId="setShopStatus",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="query",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="门店状态",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                     type="boolean",
     *                     property="status"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setShopStatus(request $request)
    {
        $wx_shop_id = $request->input('wx_shop_id');

        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        $status = $request->input('status');
        if (!$wx_shop_id) {
            return $this->response->error('门店必选！', 411);
        }
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->openOrClose($wx_shop_id, $status);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/shops/wxshops/setShopResource",
     *     summary="激活门店",
     *     tags={"企业"},
     *     description="激活门店",
     *     operationId="setResource",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="query",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="resource_id",
     *         in="query",
     *         description="资源包id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="object",
     *                     @SWG\Property(property="resource_id", type="string",description="资源包id", example="1"),
     *                     @SWG\Property(property="resource_name", type="string",description="资源包名称", example="1好店"),
     *                     @SWG\Property(property="company_id", type="string",description="公司id", example="1"),
     *                     @SWG\Property(property="eid", type="string",description="企业id", example="6616100808851"),
     *                     @SWG\Property(property="passport_uid", type="string",description="passport_uid", example="88161001409335"),
     *                     @SWG\Property(property="shop_num", type="string",description="资源门店数", example="1"),
     *                     @SWG\Property(property="left_shop_num", type="string",description="可用门店数", example="0"),
     *                     @SWG\Property(property="source", type="string",description="资源来源", example="1好店"),
     *                     @SWG\Property(property="available_days", type="string",description="可用天数", example="15"),
     *                     @SWG\Property(property="active_at", type="string",description="激活时间", example="1562643012"),
     *                     @SWG\Property(property="expired_at", type="string",description="过期时间", example="1562643012"),
     *                     @SWG\Property(property="active_code", type="string",description="激活码", example=""),
     *                     @SWG\Property(property="issue_id", type="string",description="在线开通工单号", example=""),
     *                     @SWG\Property(property="goods_code", type="string",description="商品code", example=""),
     *                     @SWG\Property(property="product_code", type="string",description="基础系统code", example="")
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setResource(request $request)
    {
        $wx_shop_id = $request->input('wx_shop_id');

        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        $resource_id = $request->input('resource_id');
        if (!$wx_shop_id) {
            return $this->response->error('门店必选！', 411);
        }
        if (!$resource_id) {
            return $this->response->error('资源包必选', 411);
        }
        $companyId = app('auth')->user()->get('company_id');
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->setResource($companyId, $wx_shop_id, $resource_id);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Delete(
     *     path="/shops/wxshops/{wx_shop_id}",
     *     summary="删除微信门店",
     *     tags={"企业"},
     *     description="添加微信门店",
     *     operationId="deleteWxShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="path",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteWxShops($wx_shop_id)
    {
        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            throw new Exception("您没有此项操作权限", 400500);
        }

        $params['wx_shop_id'] = $wx_shop_id;
        $validator = app('validator')->make($params, [
            'wx_shop_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('删除门店出错.', $validator->errors());
        }

        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->deleteShops($wx_shop_id);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/shops/wxshops/{wx_shop_id}",
     *     summary="获取单个微信门店详情",
     *     tags={"企业"},
     *     description="获取单个微信门店详情",
     *     operationId="getWxShopsDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="wx_shop_id",
     *         in="path",
     *         description="微信门店id，非微信方的mp_poi_id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Wxshop"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsDetail($wx_shop_id)
    {
        $shopIds = app('auth')->user()->get('shop_ids');
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
        }
        if ($shopIds && !in_array($wx_shop_id, $shopIds)) {
            return $this->response->array([]);
        }
        $validator = app('validator')->make(['wx_shop_id' => $wx_shop_id], [
            'wx_shop_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取门店详情出错.', $validator->errors());
        }
        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getShopsDetail($wx_shop_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取门店信息有误，请确认您的门店的ID.');
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/shops/wxshops",
     *     summary="获取微信门店列表",
     *     tags={"企业"},
     *     description="获取微信门店列表",
     *     operationId="getWxShopsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="isValid",
     *         in="query",
     *         description="门店是否过期",
     *         type="boolean",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer", description="总数", example="1"),
     *                     @SWG\Property(property="list", type="array", description="列表", @SWG\Items(
     *                         ref="#/definitions/Wxshop"
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsList(request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 1000);

        if ($request->input('isValid') == "true") {
            $filter['expired_at|gt'] = time();
        }
        if ($request->input('is_valid') == "true") {
            $filter['expired_at|gt'] = time();
        }

        if ($request->input('name')) {
            $filter['store_name|contains'] = $request->input('name');
        }

        $filter['distributor_id'] = 0;
        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        }

        $filter['address|contains'] = [];
        if ($request->input('province')) {
            $filter['address|contains'][] = $request->input('province');
        }
        if ($request->input('city')) {
            $filter['address|contains'][] = $request->input('city');
        }
        if ($request->input('area')) {
            $filter['address|contains'][] = $request->input('area');
        }
        if (!$filter['address|contains']) {
            unset($filter['address|contains']);
        }
        if ($request->input('poi_id')) {
            $filter['poi_id'] = $request->input('poi_id');
        }

        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        if (isset($authInfo['shop_ids']) && $authInfo['shop_ids']) {
            $filter['wx_shop_id'] = array_column($authInfo['shop_ids'], 'shop_id');
        }

        if ($request->input('wx_shop_id')) {
            $filter['wx_shop_id'] = $request->input('wx_shop_id');
        }

        //增加店铺类型的筛选
        if ($request->input('is_direct_store')) {
            $filter['is_direct_store'] = $request->input('is_direct_store');
        }

        $shopsService = new ShopsService(new WxShopsService());
        $result = $shopsService->getShopsList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/shops/wxshops/sync",
     *     summary="同步微信门店到本地",
     *     tags={"企业"},
     *     description="同步微信门店到本地",
     *     operationId="syncWxShops",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function syncWxShops()
    {
        $shopsService = new ShopsService(new WxShopsService());
        $shopsService->syncWxShops();

        return $this->response->noContent();
    }

    /**
     * @SWG\Put(
     *     path="/shops/wxshops/setting",
     *     summary="配置门店通用配置信息",
     *     tags={"企业"},
     *     description="配置门店通用配置信息",
     *     operationId="setWxShopsSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="logo", in="query", description="门店通用logo", required=true, type="string"),
     *     @SWG\Parameter( name="intro", in="query", description="门店通用简介", required=true, type="string"),
     *     @SWG\Parameter( name="brand_name", in="query", description="门店品牌名称", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function setWxShopsSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        // 整理参数
        $params = [
            'logo' => $request->input('logo'),
            'intro' => $request->input('intro'),
            'brand_name' => $request->input('brand_name'),
            'background' => $request->input('background', '') // 背景图
        ];

        // 如果存在总店，则也同步到总店中
        $distributorService = new DistributorService();
        $selfDistributorId = $distributorService->getDistributorSelf($companyId, false);
        if ($selfDistributorId > 0) {
            // 更新
            $distributorService->updateDistributor($selfDistributorId, [
                "company_id" => $companyId,
                "name" => $params["brand_name"],
                "logo" => $params["logo"]
            ]);
        }

        // 保存到自营店
        $shopsService = new ShopsService(new WxShopsService());
        $shopsService->setWxShopsSetting($companyId, $params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/shops/wxshops/setting",
     *     summary="获取门店通用配置信息",
     *     tags={"企业"},
     *     description="获取门店通用配置信息",
     *     operationId="getWxShopsSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="brand_name", type="string", description="品牌名称"),
     *                 @SWG\Property(property="logo", type="string", description="门店通用logo"),
     *                 @SWG\Property(property="intro", type="string", description="门店通用简介"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getWxShopsSetting()
    {
        $companyId = app('auth')->user()->get('company_id');

        // 获取自营店信息
        $shopsService = new ShopsService(new WxShopsService());
        $data = $shopsService->getWxShopsSetting($companyId);

        // 获取总店信息
        $selfDistributorInfo = (new DistributorService())->getDistributorSelf($companyId, true);

        if (!empty($selfDistributorInfo) && $data) {
            $data["brand_name"] = $selfDistributorInfo["name"] ?? "";
            $data["logo"] = $selfDistributorInfo["logo"] ?? "";
        }

        $companysService = new CompanysService();
        $companyInfo = $companysService->getInfo(['company_id' => $companyId]);
        $data['company_name'] = $companyInfo['company_name'];

        return $this->response->array($data);
    }
}
