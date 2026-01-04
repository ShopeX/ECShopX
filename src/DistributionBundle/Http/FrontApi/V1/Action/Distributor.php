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

namespace DistributionBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsTagsService;
use CompanysBundle\Services\SettingService;
use MerchantBundle\Services\MerchantService;
use WorkWechatBundle\Entities\WorkWechatRel;
use DistributionBundle\Services\SliderService;
use KaquanBundle\Services\DiscountCardService;
use KaquanBundle\Services\KaquanService;
use MembersBundle\Services\MemberAddressService;
use ThirdPartyBundle\Services\Map\MapService;
use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;
use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\BasicConfigService;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Services\DistributionService;
use DistributionBundle\Services\AdvertisementService;
use App\Http\Controllers\Controller as BaseController;
use DistributionBundle\Services\DistributorSmsService;
use Swagger\Annotations as SWG;
use Exception;
use DistributionBundle\Services\PickupLocationService;
use DistributionBundle\Services\DistributeCountService;

use DistributionBundle\Services\DistributorTagsService;
use DistributionBundle\Services\DistributorWhiteListService;
use DistributionBundle\Services\DistributorSalesCountService;
use DistributionBundle\Services\DistributorAftersalesAddressService;
use ThirdPartyBundle\Services\Map\TencentMapService as TencentMapRequest;

class Distributor extends BaseController
{
    public $code_type = ['bind'];

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor",
     *     summary="获取店铺详情",
     *     tags={"店铺"},
     *     description="获取此案普详情",
     *     operationId="getDistributor",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distributor_id", type="string", example="21", description="分销商id"),
     *                  @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                  @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="mobile", type="string", example="18098987759", description="手机号"),
     *                  @SWG\Property( property="address", type="string", example="鹿岭路6号三亚悦榕庄", description="具体地址"),
     *                  @SWG\Property( property="name", type="string", example="标准版测试用店铺，开启自提自动同步", description="名称"),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                  @SWG\Property( property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/1nDJByqmW2cfI9RKuteqnL3P5AW0cNlWGP9TTnPgYakZECiafK6Tl43UxQzI598U2OZbnMagIRQCEdTbaSvbhRQ/0?wx_fmt=gif", description="店铺logo"),
     *                  @SWG\Property( property="contract_phone", type="string", example="18098987759", description="联系电话"),
     *                  @SWG\Property( property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/1nDJByqmW2fN5gAtA3mq4kJK7fUeDLuJia1XicD09yExRV5h3mm3x8s9TjpiczDLLaLY655MnyKcHdicnCSjvAiaY0A/0?wx_fmt=jpeg", description="店铺banner"),
     *                  @SWG\Property( property="contact", type="string", example="松子", description="联系人"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="lng", type="string", example="109.498", description="地图纬度"),
     *                  @SWG\Property( property="lat", type="string", example="18.21967", description="地图经度"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                  @SWG\Property( property="is_default", type="string", example="1", description="是否默认"),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="true", description="是否审核店铺商品"),
     *                  @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="460000", description=""),
     *                  ),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="海南省", description=""),
     *                  ),
     *                  @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                  @SWG\Property( property="province", type="string", example="海南省", description="省"),
     *                  @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                  @SWG\Property( property="city", type="string", example="三亚", description="市"),
     *                  @SWG\Property( property="area", type="string", example="天涯区", description="区"),
     *                  @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                  @SWG\Property( property="created", type="string", example="1571302265", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1595559722", description="修改时间"),
     *                  @SWG\Property( property="shop_code", type="string", example="abc12QQ", description="店铺号"),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="5", description="企业微信的部门ID"),
     *                  @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="0", description="地区id"),
     *                  @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                  @SWG\Property( property="rate", type="string", example="", description=""),
     *                  @SWG\Property( property="store_address", type="string", example="海南省三亚天涯区鹿岭路6号三亚悦榕庄", description=""),
     *                  @SWG\Property( property="store_name", type="string", example="标准版测试用店铺，开启自提自动同步", description="店铺名称"),
     *                  @SWG\Property( property="phone", type="string", example="18098987759", description=""),
     *                  @SWG\Property( property="config", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributor(Request $request)
    {
        $authInfo = $request->get('auth');

        $distributorService = new DistributorService();
        $filter = [
            'mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'is_valid' => 'true',
        ];
        $result = $distributorService->getInfo($filter);

        $basicConfigService = new BasicConfigService();
        $config = $basicConfigService->getInfoById($authInfo['company_id']);
        $result['config'] = $config;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/is_valid",
     *     summary="验证店铺id是否有效",
     *     tags={"店铺"},
     *     description="验证店铺id是否有效",
     *     operationId="getDistributorIsValid",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="当前位置经度", type="number", ),
     *     @SWG\Parameter( name="lat", in="query", description="当前位置纬度", type="number", ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="number", ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_score", description="是否展示评分【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_marketing_activity", description="是否展示满减满赠等活动信息【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_sales_count", description="是否为每个店铺展示自己的销量数【0 不展示】【1 展示】" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="distributor_id", type="string", example="1", description=""),
     *                  @SWG\Property( property="shop_id", type="string", example="1", description=""),
     *                  @SWG\Property( property="is_distributor", type="string", example="true", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description=""),
     *                  @SWG\Property( property="mobile", type="string", example="13412341234", description=""),
     *                  @SWG\Property( property="address", type="string", example="宜山路700号(近桂林路)", description=""),
     *                  @SWG\Property( property="name", type="string", example="普天信息产业园测试1", description=""),
     *                  @SWG\Property( property="auto_sync_goods", type="string", example="true", description=""),
     *                  @SWG\Property( property="logo", type="string", example="null", description=""),
     *                  @SWG\Property( property="contract_phone", type="string", example="13412341234", description=""),
     *                  @SWG\Property( property="banner", type="string", example="null", description=""),
     *                  @SWG\Property( property="contact", type="string", example="测试人", description=""),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description=""),
     *                  @SWG\Property( property="lng", type="string", example="121.417435", description=""),
     *                  @SWG\Property( property="lat", type="string", example="31.176539", description=""),
     *                  @SWG\Property( property="child_count", type="string", example="15", description=""),
     *                  @SWG\Property( property="is_default", type="string", example="0", description=""),
     *                  @SWG\Property( property="is_audit_goods", type="string", example="true", description=""),
     *                  @SWG\Property( property="is_ziti", type="string", example="true", description=""),
     *                  @SWG\Property( property="regions_id", type="array",
     *                      @SWG\Items( type="string", example="310000", description=""),
     *                  ),
     *                  @SWG\Property( property="regions", type="array",
     *                      @SWG\Items( type="string", example="上海市", description=""),
     *                  ),
     *                  @SWG\Property( property="is_domestic", type="string", example="1", description=""),
     *                  @SWG\Property( property="is_direct_store", type="string", example="1", description=""),
     *                  @SWG\Property( property="province", type="string", example="上海市", description=""),
     *                  @SWG\Property( property="is_delivery", type="string", example="true", description=""),
     *                  @SWG\Property( property="city", type="string", example="上海市", description=""),
     *                  @SWG\Property( property="area", type="string", example="黄浦区", description=""),
     *                  @SWG\Property( property="hour", type="string", example="08:00 - 20:00", description=""),
     *                  @SWG\Property( property="created", type="string", example="1560930295", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1609910945", description=""),
     *                  @SWG\Property( property="shop_code", type="string", example="null", description=""),
     *                  @SWG\Property( property="wechat_work_department_id", type="string", example="0", description=""),
     *                  @SWG\Property( property="distributor_self", type="string", example="0", description=""),
     *                  @SWG\Property( property="regionauth_id", type="string", example="1", description=""),
     *                  @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                  @SWG\Property( property="rate", type="string", example="", description=""),
     *                  @SWG\Property( property="store_address", type="string", example="宜山路700号(近桂林路)", description=""),
     *                  @SWG\Property( property="store_name", type="string", example="普天信息产业园测试1", description=""),
     *                  @SWG\Property( property="phone", type="string", example="13412341234", description=""),
     *                  @SWG\Property( property="status", type="string", example="false", description=""),
     *                  @SWG\Property( property="old_valid", type="string", example="true", description=""),
     *                  @SWG\Property(property="scoreList", type="array", description="店铺的评分信息",
     *                      @SWG\Items(type="object", required={"avg_star", "default"},
     *                          @SWG\Property(property="avg_star", type="string", default="5.0", description="平均分，保留1位小数"),
     *                          @SWG\Property(property="default", type="integer", default="1", description="该店铺是否有人评分【0 否】【1 是】"),
     *                      ),
     *                  ),
     *                  @SWG\Property(property="marketingActivityList", type="array",description="店铺满折满减满赠营销活动",
     *                      @SWG\Items(type="object", required={"marketing_id","marketing_type","marketing_name","condition_type","condition_value","promotion_tag","valid_grade","join_limit","start_time","end_time","canjoin_repeat","use_shop","in_proportion"},
     *                          @SWG\Property(property="marketing_id", type="integer", default="1", description="活动id"),
     *                          @SWG\Property(property="marketing_type", type="string", default="", description="活动类型【full_discount 满折】【full_minus 满减】【full_gift 满赠】【self_select 任选优惠】【plus_price_buy 加价购】【member_preference 会员优先购】"),
     *                          @SWG\Property(property="marketing_name", type="string", default="", description="活动名称"),
     *                          @SWG\Property(property="condition_type", type="string", default="", description="促销规则的类型【quantity 按总件数】【totalfee 按总金额】"),
     *                          @SWG\Property(property="condition_value", type="string", default="", description="促销规则"),
     *                          @SWG\Property(property="promotion_tag", type="string", default="", description="促销标签"),
     *                          @SWG\Property(property="valid_grade", type="string", default="", description="会员级别集合"),
     *                          @SWG\Property(property="join_limit", type="integer", default="", description="可参与次数"),
     *                          @SWG\Property(property="start_time", type="integer", default="", description="促销开始的时间（时间戳）"),
     *                          @SWG\Property(property="end_time", type="integer", default="", description="促销结束的时间（时间戳）"),
     *                          @SWG\Property(property="canjoin_repeat", type="integer", default="", description="促销活动是否上不封顶【0 封顶】【1 不封顶】"),
     *                          @SWG\Property(property="use_shop", type="integer", default="", description="适用店铺【0 全场可用】【1 指定店铺可用】"),
     *                          @SWG\Property(property="in_proportion", type="integer", default="", description="满赠活动中，是否按比例赠送【0 否】【1 是】"),
     *                      ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorIsValid(Request $request)
    {
        $result = [];

        $authInfo = $request->get('auth');
        $userId = (int)$authInfo['user_id'];
        $distributorService = new DistributorService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'is_valid' => 'true', 
        ];
        $postdata = $request->all();
        $lng = $request->input('lng') ? $request->input('lng') : 0;
        $lat = $request->input('lat') ? $request->input('lat') : 0;

        $setting = (new SettingService())->getOpenDistributorDivided($authInfo['company_id']);
        if($setting['status'] === true){//开启了，就找没开启店铺
            if(isset($postdata['show_type']) && $postdata['show_type'] === 'self'){
                $result = (new DistributorWhiteListService())->getWhiteListDistributor($userId,$authInfo['company_id'],$postdata['distributor_id'] ?? 0,$lng,$lat);
                return $this->response->array($result);
            }else{
                $filter['open_divided'] = 0;
            }
//            $filter['open_divided'] = 0;
//            if(isset($postdata['show_type'])){
//                $service = new DistributorWhiteListService();
//                if($postdata['show_type'] === 'self'){//获取自己的名单
//                    $distributorId = $service->getUserWhiteListDistributor($userId,$authInfo['company_id']);
//                    $postdata['self_distributor_id'] = $distributorId;
//                    $filter['open_divided'] = 1;
////                $filter['distributor_id'] = $distributorId;
//                }
//            }
        }
        if ((!$lng || !$lat) && $userId > 0) {
            $addressDetail = (new MemberAddressService())->getDefaultAddress((int)$filter["company_id"], (int)$authInfo['company_id']);
            // 用户会优先根据授权时的经纬度
            // 如果用户授权拒绝，则去获取用户默认收货地址里的经纬度
            // 如果用户默认收货地址里的经纬度不存在(新老数据的问题)，则根据收货地址的城市和详情去获取经纬度
            // 用户拒绝授权就获取用户的默认地址下的经纬度
            $lat = $addressDetail["lat"] ?? ""; // 获取默认地址下的经度
            $lng = $addressDetail["lng"] ?? ""; // 获取默认地址下的纬度
            if (empty($lat) || empty($lng)) {
                $addressCity = $addressDetail["city"] ?? "";
                $addressAdrdetail = $addressDetail["adrdetail"] ?? "";
                if (!empty($addressCity) && !empty($addressAdrdetail)) {
                    $mapData = MapService::make((int)$authInfo['company_id'])->getLatAndLng($addressCity, $addressAdrdetail);
                    $lng = $mapData->getLng();
                    $lat = $mapData->getLat();
//                    (new TencentMapRequest)->getLngAndLat($lng, $lat, $addressCity, $addressAdrdetail);
                }
            }
        }

        //验证参数todo
        $validator = app('validator')->make($request->all(), [
            'lng' => 'sometimes|numeric|between:-180.0,180.0',
            'lat' => 'sometimes|numeric|between:-90.0,90.0',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('经纬度范围错误.', $validator->errors());
        }
        // 1/0 是否是无店铺
        $postdata['isNostores'] = $postdata['isNostores'] ?? 0;

        if (isset($postdata['distributor_id']) && $postdata['distributor_id']) {//普通店铺
            $filter['distributor_id'] = $postdata['distributor_id'] === 'false' ? 0 : $postdata['distributor_id'];
            unset($filter['open_divided']);
            $result = $distributorService->getInfo($filter);
            if (!$result) {
                throw new ResourceException('店铺查询错误.');
            }
            $result['status'] = true;
            if ($result && isset($result['is_valid']) && $result['is_valid'] == 'true') {
                $result['status'] = false;
            }
            $result['old_valid'] = true;
            $result['phone'] = $result['mobile'];
            $result['store_address'] = $result['address'];
            $result['store_name'] = $result['name'];

//            if(!empty($postdata['show_type']) && ($postdata['show_type'] === 'self')){
//                if(!in_array($postdata['distributor_id'],$postdata['self_distributor_id']) && $result['open_divided'] == 1){
//                    $result = [];
//                }
//            }
            // return $this->response->array($result);
        } elseif ($postdata['isNostores'] == 1) {// 关闭前端店铺
            $result = [];
            if (!$lng || !$lat) {
                $address = '北京市东城区东长安街';
                $mapData = MapService::make((int)$authInfo["company_id"])->getLatAndLng("", $address);
                $lng = $mapData->getLng();
                $lat = $mapData->getLat();
//                $area_result = get_latlng_by_address($address);
//                $lng = $area_result['location']['lng'] ?? '';
//                $lat = $area_result['location']['lat'] ?? '';
//                $lng = $area_result['result']['location']['lng'] ?? '';
//                $lat = $area_result['result']['location']['lat'] ?? '';
            }
            if ($lng && $lat) {
                $filter['is_ziti'] = true;
                $params = $filter;
                $params['user_id'] = $authInfo['user_id'];
                $params['cart_type'] = $postdata['cart_type'] ?? 'cart';
                $params['order_type'] = $postdata['order_type'] ?? 'service';
                $params['seckill_id'] = $postdata['seckill_id'] ?? '';
                $params['seckill_ticket'] = $postdata['seckill_ticket'] ?? '';
                $params['iscrossborder'] = $postdata['iscrossborder'] ?? '';
                $params['bargain_id'] = $postdata['bargain_id'] ?? 0;
                // 根据条件，获取购买商品都有库存的店铺id
                $distributor_ids = $distributorService->getShopIdsByNostores($filter, $params);
                if ($distributor_ids) {
                    $filter['distributor_id'] = $distributor_ids;
                    $result = $distributorService->getNearShopData($filter, $lat, $lng);
                    $result['is_dada'] = $result['is_dada'] == '1' ? true : false;
                }
            }
        } elseif ($lng && $lat) { //经纬度
            if (isset($filter['distributor_id'])) {
                unset($filter['distributor_id']);
            }
            $isShopDivided = 0;
            if($setting['status'] === true){
                $filter['distributor_self'] = 0;
                $isShopDivided = 1;//走默认店铺给特殊字段
            }
            $result = $distributorService->getNearShopData($filter, $lat, $lng,$isShopDivided);
            if($isShopDivided === 0){
                if(isset($postdata['distributor_id']) && $postdata['distributor_id'] == 0){
                    if($result['distributor_self'] == 1){
                        $result['distributor_id'] = 0;
                    }
                }
            }
//            if(!empty($postdata['show_type']) && $postdata['show_type'] === 'self'){
//                if(empty($postdata['self_distributor_id'])){
//                    $result = [];
//                }else{
//                    $filter['distributor_id'] = $postdata['self_distributor_id'];
//                    $result = $distributorService->getNearShopData($filter, $lat, $lng);
//                    if($result['distributor_self'] == 1){
//                        if($setting['status'] === true){
//                            $result = [];
//                        }
//                    }
//                }
//            }else{
//                $result = $distributorService->getNearShopData($filter, $lat, $lng);
//            }
        } else {
            if (isset($postdata['distributor_id']) && $postdata['distributor_id'] == 0) {//总店-取自提配置信息
//                if(isset($postdata['self_distributor_id'])){
//                    $result = $distributorService->getDistributorSelf($authInfo['company_id'], true,['distributor_id'=>$postdata['self_distributor_id']]);
//                }else{
//                    $result = $distributorService->getDistributorSelf($authInfo['company_id'], true);
//                }
                if(empty($userId)){
                    if($setting['status'] === true){

                        $filter['distributor_id'] = 0;
                        $result = $distributorService->getInfo($filter);
                    }else{
                        $result = $distributorService->getDistributorSelf($authInfo['company_id'], true);
                        $result['distributor_id'] = 0;
                    }
                }else{
                    if($setting['status'] === true){
                        $result = $distributorService->getDistributorSelf($authInfo['company_id'], true,['open_divided'=>0]);
                    }else{
                        $result = $distributorService->getDistributorSelf($authInfo['company_id'], true);
                        $result['distributor_id'] = 0;
                    }
                }


                $result['is_delivery'] = $result['is_delivery'] ?? true;
                $result['is_ziti'] = $result['is_ziti'] ?? false;
//                $result['distributor_id'] = 0;
                $result['is_valid'] = 'true';
            } else {//默认店铺
                $filter['distributor_id'] = 0;
                $result = $distributorService->getInfo($filter);
            }
        }

        if ($result && $result['is_valid'] == 'true') {
            $result['status'] = false;
            $result['old_valid'] = false;
            $result['phone'] = fixeddecrypt($result['mobile'] ?? '');
            $result['store_address'] = $result['address'] ?? '';
            $result['store_name'] = $result['name'] ?? '';

            $distributorList = [&$result];
            // 追加评分
            if ($request->input("show_score")) {
                $distributorService->appendScore((int)$authInfo['company_id'], $distributorList);
            }

            // 追加店铺满折满减满赠
            if ($request->input("show_marketing_activity")) {
                $distributorService->appendPromotionsMarketingActivity((int)$authInfo['company_id'], $distributorList);
            }

            // 追加销量
            if ($request->input("show_sales_count")) {
                $distributorService->appendSalesCount((int)$authInfo['company_id'], $distributorList);
            }
            if($setting['status'] === true){
                if(isset($postdata['distributor_id']) && $postdata['distributor_id'] == 0){
                    if($result['is_default'] == 'true'){
                        $result = [
                            'distributor_id' => 0,
                            'is_delivery' => true,
                            'is_ziti' => false,
                        ];
                    }
                }
            }
       
            return $this->response->array($result);
        } else {
            $result = [
                'distributor_id' => 0,
                'is_delivery' => true,
                'is_ziti' => false,
            ];
            return $this->response->array($result);
        }
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/count",
     *     summary="获取店铺统计",
     *     tags={"店铺"},
     *     description="获取店铺统计",
     *     operationId="getDistributorCount",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="cashWithdrawalRebate", type="stirng", example="可提现佣金 单位为分"),
     *                 @SWG\Property(property="freezeCashWithdrawalRebate", type="stirng", example="申请提现佣金，冻结提现佣金"),
     *                 @SWG\Property(property="itemTotalPrice", type="stirng", example="分销商品总金额"),
     *                 @SWG\Property(property="noCloseRebate", type="stirng", example="未结算佣金"),
     *                 @SWG\Property(property="rebateTotal", type="stirng", example="分销佣金总金额"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorCount(Request $request)
    {
        $authInfo = $request->get('auth');

        $distributorService = new DistributorService();
        $filter = [
            'mobile' => $authInfo['mobile'],
            'company_id' => $authInfo['company_id'],
            'is_valid' => 'true',
        ];

        $result = $distributorService->getInfo($filter);
        $data = array();
        if ($result) {
            $distributeCountService = new DistributeCountService();
            $data = $distributeCountService->getDistributorCount($authInfo['company_id'], $result['distributor_id']);
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/list",
     *     summary="获取店铺列表",
     *     tags={"店铺"},
     *     description="获取店铺列表",
     *     operationId="getDistributorList",
     *     @SWG\Parameter(name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(in="query", type="string", required=false, name="lat", description="纬度" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="lng", description="经度" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="page", description="页数" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="pageSize", description="每页条数" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="company_id", description="公司id" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="search_type", description="搜索类型【1 基于店铺的名称或店铺商品名称做模糊搜索】【2 基于店铺的名称或店铺的地址做模糊搜索】，默认值为1" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="name", description="搜索内容" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="province", description="筛选的省份" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="city", description="筛选的城市" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="area", description="筛选的区域" ),
     *     @SWG\Parameter(in="query", type="string", required=false,
     *     name="type", description="查询类型, 【0 正常流程】【1 基于省市区过滤】【2 基于默认收货地址强制定位】" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="card_id", description="卡券id, 返回适用的所有店铺列表" ),
     *     @SWG\Parameter(in="query", type="string", required=false, name="sort_type", description="排序类型【0 根据添加时间倒序】【1 根据距离排序，由近到远】【2 根据距离排序，由远到近】【3 根据店铺销量排序，由高到低】【4 根据店铺销量排序，由低到高】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="distributor_tag_id", description="店铺标签的id，支持多个，如果是多个需要用逗号隔开" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="is_ziti", description="是否支持自提【null 不筛选】【0 不支持自提】【1 支持自提】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="is_delivery", description="是否支持快递【null 不筛选】【0 不支持快递】【1 支持快递】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="is_dada", description="是否支持同城配【null 不筛选】【0 不支持同城配】【1 支持同城配】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_tag", description="是否为每个店铺展示自己的店铺标签【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_discount", description="是否为每个店铺展示自己的店铺优惠券【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_marketing_activity", description="是否为每个店铺展示自己的店铺满折满减满赠活动【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_sales_count", description="是否为每个店铺展示自己的销量数【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_score", description="是否为每个店铺展示自己的评分【0 不展示】【1 展示】" ),
     *     @SWG\Parameter(in="query", type="integer", required=false, name="show_items", description="是否为每个店铺展示自己的店铺商品【0 不展示】【1 展示】" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 required={"is_recommend","total_count","list","tagList","default_address"},
     *                 @SWG\Property(property="is_recommend", type="integer", default="0", description="判断是否是推荐门店， 0为附近门店不是推荐门店，1为推荐门店"),
     *                  @SWG\Property(property="total_count", type="string", default="9", description="总条数"),
     *                  @SWG\Property(property="list", type="array",
     *                      @SWG\Items( type="object",required={"distributor_id","company_id","mobile","address","name","created","updated","is_valid","province","city","area","regions_id","regions","contact","child_count","shop_id","is_default","is_ziti","lng","lat","hour","auto_sync_goods","logo","banner","is_audit_goods","is_delivery","shop_code","review_status""source_from","distributor_self","is_distributor","contract_phone","is_domestic","is_direct_store","wechat_work_department_id","regionauth_id","is_open","rate","store_address","store_name","phone","distance_show","distance_unit","tagList"},
     *                          @SWG\Property(property="distributor_id", type="string", default="105", description="店铺id"),
     *                          @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                          @SWG\Property(property="mobile", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="address", type="string", default="徐汇区", description="地址"),
     *                          @SWG\Property(property="name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="created", type="string", default="1608012760", description="创建时间"),
     *                          @SWG\Property(property="updated", type="string", default="1610533984", description="更新时间"),
     *                          @SWG\Property(property="is_valid", type="string", default="true", description=""),
     *                          @SWG\Property(property="province", type="string", default="上海市", description="省"),
     *                          @SWG\Property(property="city", type="string", default="上海市", description="市"),
     *                          @SWG\Property(property="area", type="string", default="徐汇区", description="区县"),
     *                          @SWG\Property(property="regions_id", type="array",
     *                              @SWG\Items( type="string", default="310000", description="地区码"),
     *                          ),
     *                          @SWG\Property(property="regions", type="array",
     *                              @SWG\Items( type="string", default="上海市", description="地区名称"),
     *                          ),
     *                          @SWG\Property(property="contact", type="string", default="wuqiong ", description=""),
     *                          @SWG\Property(property="child_count", type="string", default="0", description=""),
     *                          @SWG\Property(property="shop_id", type="string", default="0", description="shop_id"),
     *                          @SWG\Property(property="is_default", type="string", default="true", description="是否默认"),
     *                          @SWG\Property(property="is_ziti", type="string", default="true", description="是否自提"),
     *                          @SWG\Property(property="lng", type="string", default="121.43687", description="经度"),
     *                          @SWG\Property(property="lat", type="string", default="31.18826", description="纬度"),
     *                          @SWG\Property(property="hour", type="string", default="08:00-21:00", description=""),
     *                          @SWG\Property(property="auto_sync_goods", type="string", default="false", description="自动同步商品"),
     *                          @SWG\Property(property="logo", type="string", default="null", description="logo"),
     *                          @SWG\Property(property="banner", type="string", default="null", description="banner"),
     *                          @SWG\Property(property="is_audit_goods", type="string", default="false", description="是否审核商品"),
     *                          @SWG\Property(property="is_delivery", type="string", default="true", description=""),
     *                          @SWG\Property(property="shop_code", type="string", default="1234567", description="shop_code"),
     *                          @SWG\Property(property="review_status", type="string", default="0", description=""),
     *                          @SWG\Property(property="source_from", type="string", default="1", description=""),
     *                          @SWG\Property(property="distributor_self", type="string", default="0", description=""),
     *                          @SWG\Property(property="is_distributor", type="string", default="true", description="是否门店"),
     *                          @SWG\Property(property="contract_phone", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="is_domestic", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_direct_store", type="string", default="1", description="是否直营店"),
     *                          @SWG\Property(property="wechat_work_department_id", type="string", default="0", description=""),
     *                          @SWG\Property(property="regionauth_id", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_open", type="string", default="false", description=""),
     *                          @SWG\Property(property="rate", type="string", default="", description=""),
     *                          @SWG\Property(property="distance", type="string", default="0", description="距离"),
     *                          @SWG\Property(property="store_address", type="string", default="上海市徐汇区徐汇区", description="地址"),
     *                          @SWG\Property(property="store_name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="phone", type="string", default="17621716237", description="手机号"),
     *                          @SWG\Property(property="distance_show", type="string", default="0", description="距离显示"),
     *                          @SWG\Property(property="distance_unit", type="string", default="m", description="距离单位"),
     *                          @SWG\Property(property="tagList", type="array",description="店铺标签信息",
     *                              @SWG\Items(type="object", required={"tag_id","tag_name","tag_color","font_color","tag_icon"},
     *                                  @SWG\Property(property="tag_id", type="integer", default="1", description="店铺标签id"),
     *                                  @SWG\Property(property="tag_name", type="string", default="", description="店铺标签的名字"),
     *                                  @SWG\Property(property="tag_color", type="string", default="", description="店铺标签的颜色"),
     *                                  @SWG\Property(property="font_color", type="string", default="", description="店铺标签的字体颜色"),
     *                                  @SWG\Property(property="tag_icon", type="string", default="", description="店铺标签的图片"),
     *                              ),
     *                          ),
     *                          @SWG\Property(property="discountCardList", type="array", description="店铺优惠券",
     *                              @SWG\Items(type="object", required={"card_id","card_type","title","color","date_type","begin_date","end_date","fixed_term","quantity","discount","least_cost","most_cost","reduce_cost","get_limit","receive"},
     *                                  @SWG\Property(property="card_id", type="integer", default="1", description="优惠券id"),
     *                                  @SWG\Property(property="card_type", type="string", default="", description="优惠券类型【discount 折扣券】【cash 代金券】【gift 兑换券】【new_gift 兑换券(新)】"),
     *                                  @SWG\Property(property="title", type="string", default="", description="优惠券名称"),
     *                                  @SWG\Property(property="color", type="string", default="", description="优惠券颜色"),
     *                                  @SWG\Property(property="date_type", type="string", default="", description="有效期的类型【DATE_TYPE_FIX_TIME_RANGE 指定日期范围内】【DATE_TYPE_FIX_TERM 领取后的固定天数】"),
     *                                  @SWG\Property(property="begin_date", type="string", default="", description="开始时间（时间戳），当date_type为DATE_TYPE_FIX_TERM时，begin_date为领取后在第几天之后才生效，值为2时则为领取后的2天后才生效"),
     *                                  @SWG\Property(property="end_date", type="string", default="", description="结束时间（时间戳），当date_type为DATE_TYPE_FIX_TERM时，end_date为该优惠券的统一过期时间，如果值为0表示不存在过期时间"),
     *                                  @SWG\Property(property="fixed_term", type="string", default="", description="date_type为DATE_TYPE_FIX_TERM时，优惠券被领取后的有效天数"),
     *                                  @SWG\Property(property="quantity", type="integer", default="", description="卡券发放的数量"),
     *                                  @SWG\Property(property="discount", type="integer", default="", description="卡券的折扣额度，带百分比，值为34表示34%，但在后台营销中的优惠券管理里的折扣额度的值是 (100-34)%10"),
     *                                  @SWG\Property(property="least_cost", type="integer", default="", description="优惠券的使用条件，需要满多少才可用（金额的单位：分）"),
     *                                  @SWG\Property(property="most_cost", type="integer", default="", description="代金券最高消费限额（金额的单位：分）"),
     *                                  @SWG\Property(property="reduce_cost", type="integer", default="", description="满减券的减免金额（金额的单位：分）"),
     *                                  @SWG\Property(property="get_limit", type="integer", default="", description="领券的数量限制"),
     *                                  @SWG\Property(property="receive", type="integer", default="", description="是否前台直接领取【0 否】【1 是】"),
     *                              ),
     *                          ),
     *                          @SWG\Property(property="marketingActivityList", type="array",description="店铺满折满减满赠营销活动",
     *                              @SWG\Items(type="object", required={"marketing_id","marketing_type","marketing_name","condition_type","condition_value","promotion_tag","valid_grade","join_limit","start_time","end_time","canjoin_repeat","use_shop","in_proportion"},
     *                                  @SWG\Property(property="marketing_id", type="integer", default="1", description="活动id"),
     *                                  @SWG\Property(property="marketing_type", type="string", default="", description="活动类型【full_discount 满折】【full_minus 满减】【full_gift 满赠】【self_select 任选优惠】【plus_price_buy 加价购】【member_preference 会员优先购】"),
     *                                  @SWG\Property(property="marketing_name", type="string", default="", description="活动名称"),
     *                                  @SWG\Property(property="condition_type", type="string", default="", description="促销规则的类型【quantity 按总件数】【totalfee 按总金额】"),
     *                                  @SWG\Property(property="condition_value", type="string", default="", description="促销规则"),
     *                                  @SWG\Property(property="promotion_tag", type="string", default="", description="促销标签"),
     *                                  @SWG\Property(property="valid_grade", type="string", default="", description="会员级别集合"),
     *                                  @SWG\Property(property="join_limit", type="integer", default="", description="可参与次数"),
     *                                  @SWG\Property(property="start_time", type="integer", default="", description="促销开始的时间（时间戳）"),
     *                                  @SWG\Property(property="end_time", type="integer", default="", description="促销结束的时间（时间戳）"),
     *                                  @SWG\Property(property="canjoin_repeat", type="integer", default="", description="促销活动是否上不封顶【0 封顶】【1 不封顶】"),
     *                                  @SWG\Property(property="use_shop", type="integer", default="", description="适用店铺【0 全场可用】【1 指定店铺可用】"),
     *                                  @SWG\Property(property="in_proportion", type="integer", default="", description="满赠活动中，是否按比例赠送【0 否】【1 是】"),
     *                              ),
     *                          ),
     *                          @SWG\Property(property="scoreList", type="array", description="店铺的评分信息",
     *                              @SWG\Items(type="object", required={"avg_star", "default"},
     *                                  @SWG\Property(property="avg_star", type="string", default="5.0", description="平均分，保留1位小数"),
     *                                  @SWG\Property(property="default", type="integer", default="1", description="该店铺是否有人评分【0 否】【1 是】"),
     *                              ),
     *                          ),
     *                          @SWG\Property(property="itemList", type="array", description="店铺的商品信息",
     *                              @SWG\Items(type="object", required={"item_id","item_name","price","market_price","store","pics"},
     *                                  @SWG\Property(property="item_id", type="integer", default="1", description="商品id"),
     *                                  @SWG\Property(property="item_name", type="string", default="", description="商品名称"),
     *                                  @SWG\Property(property="price", type="integer", default="", description="销售价,单位为‘分’"),
     *                                  @SWG\Property(property="market_price", type="integer", default="", description="原价,单位为‘分’"),
     *                                  @SWG\Property(property="store", type="integer", default="", description="库存"),
     *                                  @SWG\Property(property="pics", type="string", default="", description="封面"),
     *                              ),
     *                          ),
     *                      ),
     *                  ),
     *                  @SWG\Property(property="tagList", type="array",description="店铺的所有标签",
     *                      @SWG\Items( type="object",required={"tag_id","tag_name","tag_color","font_color","tag_icon"},
     *                          @SWG\Property(property="tag_id", type="integer", default="1", description="店铺标签id"),
     *                          @SWG\Property(property="tag_name", type="string", default="", description="店铺标签的名字"),
     *                          @SWG\Property(property="tag_color", type="string", default="", description="店铺标签的颜色"),
     *                          @SWG\Property(property="font_color", type="string", default="", description="店铺标签的字体颜色"),
     *                          @SWG\Property(property="tag_icon", type="string", default="", description="店铺标签的图片"),
     *                      ),
     *                  ),
     *                  @SWG\Property(property="default_address", type="object",
     *                      @SWG\Property(property="address_id", type="string", default="415", description="地址id"),
     *                      @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                      @SWG\Property(property="user_id", type="string", default="20264", description="用户id"),
     *                      @SWG\Property(property="username", type="string", default="张三", description="名称"),
     *                      @SWG\Property(property="telephone", type="string", default="18890908989", description="手机号码"),
     *                      @SWG\Property(property="area", type="string", default="null", description="地区"),
     *                      @SWG\Property(property="province", type="string", default="广东省", description="省"),
     *                      @SWG\Property(property="city", type="string", default="广州市", description="市"),
     *                      @SWG\Property(property="county", type="string", default="海珠区", description="区"),
     *                      @SWG\Property(property="adrdetail", type="string", default="新港中路397号", description="详细地址"),
     *                      @SWG\Property(property="postalCode", type="string", default="510000", description="邮编"),
     *                      @SWG\Property(property="is_def", type="string", default="true", description="是否默认地址"),
     *                      @SWG\Property(property="created", type="string", default="1598846042", description=""),
     *                      @SWG\Property(property="updated", type="string", default="1598846042", description="修改时间"),
     *                      @SWG\Property(property="third_data", type="string", default="null", description="百胜等第三方返回的数据"),
     *                      @SWG\Property(property="lat", type="string", default="", description="经度"),
     *                      @SWG\Property(property="lng", type="string", default="", description="纬度"),
     *
     *                  ),
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorList(Request $request)
    {
        // 获取参数
        $authInfo = $request->get('auth');

        $companyId = (int)$authInfo['company_id']; // 企业id
        $userId = (int)($authInfo["user_id"] ?? 0); // 用户id
        $unionid = (string)($authInfo["unionid"] ?? ""); // 获取用户的unionid

        $page = $request->input('page', 1);// 当前页
        $pageSize = $request->input('pageSize', 10); // 每页的数量
        $lng = (string)$request->input('lng'); // 经度
        $lat = (string)$request->input('lat'); // 纬度
        $province = $request->input("province"); // 所在的省
        $city = $request->input('city'); // 所在的城市
        $area = $request->input('area'); // 所在的范围
        $address = $request->input("address"); // 所在的具体地址

        $showTag = (int)$request->input("show_tag", 1); // 是否为每个店铺展示自己的店铺标签
        $showDiscount = (int)$request->input("show_discount", 0); // 是否为每个店铺展示自己的店铺优惠券
        $showMarketingActivity = (int)$request->input("show_marketing_activity", 0); // 是否为每个店铺展示自己的店铺满折满减满赠活动
        $showSalesCount = (int)$request->input("show_sales_count", 1); // 是否为每个店铺展示自己的销量数
        $showScore = (int)$request->input("show_score", 0); // 是否为每个店铺展示自己的评分
        $showItems = (int)$request->input("show_items", 0); // 是否为每个店铺展示自己的店铺商品

        $distributorTagsService = new DistributorTagsService();
        $memberAddressService = new MemberAddressService();

        // 定义返回响应的结构体
        $result = [
            "total_count" => 0,
            "list" => [],
            "tagList" => $distributorTagsService->getFrontShowTags($companyId),
            // 获取用户的默认地址
            "defualt_address" => $userId > 0 ? $memberAddressService->getDefaultAddress($companyId, $userId) : [],
            // 判断门店的信息是否是推荐门店还是附近门店
            "is_recommend" => 0
        ];

        $filter = [];

        $type = $request->input('type', 0); // 过滤条件
        $noHaving = false; // 是否过滤离用户的经纬度比较远的店铺 【true 不过滤】【false 过滤】
        switch ($type) {
            // 基于省市区强制过滤下面的所有门店
            case 1:
                $filter['province|contains'] = $province;
                $filter['city|contains'] = $city;
                $filter['area|contains'] = str_replace("区", "", $area);
                // 获取用户经纬度
                if (!empty($lng) && !empty($lat)) {
                    $filter['lng'] = $lng;
                    $filter['lat'] = $lat;
                } elseif ($result["defualt_address"]) {
                    // 如果拒绝授权，则获取默认的收货地址
                    $mapData = $memberAddressService->getLngAndLatByAddress((int)$authInfo['company_id'], $result["defualt_address"]);
                    $filter['lng'] = $mapData->getLng();
                    $filter['lat'] = $mapData->getLat();
                }
                $noHaving = true;
                break;
            // 基于用户的收货地址获取附近的所有门店
            case 2:
                // 如果地址存在，则获取经纬度，如果地址的经纬度不存在，则根据腾讯地图获取经纬度
                $mapData = $memberAddressService->getLngAndLatByAddress((int)$authInfo['company_id'], $result["defualt_address"]);
                $filter['lng'] = $mapData->getLng();
                $filter['lat'] = $mapData->getLat();
                break;
            // 基于前端提供的具体地址来获取附近门店
            case 3:
                $mapData = $memberAddressService->getLngAndLatByAddress((int)$authInfo['company_id'], [
                    "city" => $city,
                    "adrdetail" => $address
                ]);
                $filter['province|contains'] = $province;
                $filter['city|contains'] = $city;
                $filter['area|contains'] = str_replace("区", "", $area);
                $filter['lng'] = $mapData->getLng();
                $filter['lat'] = $mapData->getLat();
                break;
            // 正常流程
            default:
                // 如果用户授权了定位，则根据经纬度去获取附近门店
                if (!empty($lng) && !empty($lat)) {
                    $filter['lng'] = $lng;
                    $filter['lat'] = $lat;
                } else {
                    // 如果拒绝授权，则获取默认的收货地址
                    if ($result["defualt_address"]) {
                        $mapData = $memberAddressService->getLngAndLatByAddress((int)$authInfo['company_id'], $result["defualt_address"]);
                        $filter['lng'] = $mapData->getLng();
                        $filter['lat'] = $mapData->getLat();
                    } else {
                        $result["is_recommend"] = 1;
                    }
                }
                break;
        }

        // 校验经纬度
        $validator = app('validator')->make(["lng" => $lng, "lat" => $lat], [
            'lng' => 'sometimes|numeric|between:-180.0,180.0',
            'lat' => 'sometimes|numeric|between:-90.0,90.0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('经纬度范围错误.', $validator->errors());
        }

        $filter['company_id'] = $companyId;

        // 基于搜索类型做不同的搜索条件
        $searchType = $request->input("search_type", 1); // 搜索类型
        $name = $request->input('name'); // 搜索内容
        if ($name) {
            $filter["or"]["name|like"] = $name;
            switch ($searchType) {
                case 1:
                    // 获取商品名称
                    $itemsList = (new ItemsService())->getLists([
                        "company_id" => $companyId,
                        "item_id|direct" => "default_item_id",
                        "item_name|like" => $name,
                        "audit_status" => "approved",
                        "approve_status" => "onsale"
                    ], "distributor_id", 1);
                    $distributorIds = (array)array_column($itemsList, "distributor_id");
                    $distributorIds = array_unique(array_filter($distributorIds));
                    if (!empty($distributorIds)) {
                        $filter["or"]["distributor_id"] = $distributorIds;
                    }
                    break;
                case 2:
                    $filter["or"]["address|like"] = $name;
                    break;
            }
        }

        // 筛选店铺id
        if ($request->get('distributor_id')) {
            $filter['distributor_id'] = $request->get('distributor_id');
        } elseif ($request->get('distributorIds')) {
            $filter['distributor_id'] = $request->get('distributorIds');
        }
        // 筛选 自提
        $isZiti = $request->input("is_ziti");
        if (!is_null($isZiti)) {
            $filter["is_ziti"] = $isZiti;
        }

        // 筛选 快递配送
        $isDelivery = $request->input("is_delivery");
        if (!is_null($isDelivery)) {
            $filter["is_delivery"] = $isDelivery;
        }

        // 筛选 达达同城配送
        $isDada = $request->input("is_dada");
        if (!is_null($isDada)) {
            $filter["is_dada"] = $isDada;
        }


        // 默认拿不是总店的店铺
        $filter['distributor_self'] = 0;

        $filter['is_valid'] = 'true';
        if ($request->input('get_shop')) {
            $filter['shop_id|neq'] = '';
        }

        // 过滤出某个卡券下的店铺列表信息
        if ($cardId = $request->get('card_id')) {
            // 查询优惠券详情
            $discountCardService = new KaquanService(new DiscountCardService());
            $ids = $discountCardService->getDistributorIds(['company_id' => $companyId, 'card_id' => $cardId]);
            if ($ids) {
                $filter['distributor_id'] = $ids;
            }
        }

        $distributorService = new DistributorService();
        if ($isNostores = $request->get('isNostores', 0)) {
            $filter['is_ziti'] = 1;
            $params = $_filter = $filter;
            $params['user_id'] = $authInfo['user_id'];
            $params['cart_type'] = $request->input('cart_type', 'cart');
            $params['order_type'] = $request->input('order_type', 'service');
            $params['seckill_id'] = $request->input('seckill_id', '');
            $params['seckill_ticket'] = $request->input('seckill_ticket', '');
            $params['iscrossborder'] = $request->input('iscrossborder', '');
            $params['bargain_id'] = $request->input('bargain_id', 0);
            if (isset($_filter['lng'])) {
                unset($_filter['lng']);
            }
            if (isset($_filter['lat'])) {
                unset($_filter['lat']);
            }
            $distributor_ids = $distributorService->getShopIdsByNostores($_filter, $params);

            if (!$distributor_ids) {
                return $this->response->array($result);
            }
            $filter['distributor_id'] = $distributor_ids;
            $noHaving = false;
            if ($lng && $lat) {
                $noHaving = true;
            }
        } else {
            $noHaving = false;
        }

        // 根据标签过滤
        $distributorTagId = (array)explode(",", $request->input("distributor_tag_id"));
        $distributorTagId = array_unique(array_filter($distributorTagId));
        if (!empty($distributorTagId)) {
            $distributorIds = (array)$distributorTagsService->getDistributorIdsByTagids(["tag_id" => $distributorTagId]);
            // 不存在店铺信息，则直接返回
            if (empty($distributorIds)) {
                return $this->response->array($result);
            }
            // 如果上文已经对店铺id做过滤，则需要做交集
            if (isset($filter['distributor_id'])) {
                $filter['distributor_id'] = array_intersect($distributorIds, is_array($filter["distributor_id"]) ? $filter["distributor_id"] : [$filter["distributor_id"]]);
            } else {
                $filter['distributor_id'] = $distributorIds;
            }
        }

        // 排序方式
        // 有经纬度，则根据经纬度定位
        // 无经纬度，则根据商家销量排序
        $sortType = $request->input("sort_type", 0);
        switch ($sortType) {
            // 根据距离排序
            case 1: // 由近到远
            case 2: // 由远到近
                if (isset($filter["lng"]) && isset($filter["lat"]) && !empty($filter["lng"]) && !empty($filter["lat"])) {
                    $orderBy = [
                        "distance" => $sortType == 1 ? "ASC" : "DESC",
                        "distributor_id" => "DESC"
                    ];
                } else {
                    $orderBy = [
                        "is_default" => "DESC",
                        "created" => "DESC",
                        "distributor_id" => "DESC"
                    ];
                }
                break;
            // 根据店铺销量排序
            case 3: // 由高到低
            case 4: // 由低到高
                $distributorSalesCountList = (new DistributorSalesCountService())->getTotalSalesCount($companyId, $sortType == 3);
                if (!empty($distributorSalesCountList)) {
                    $orderBy = [
                        sprintf("FIELD(distributor_id, %s)", implode(",", array_keys($distributorSalesCountList))) => "ASC"
                    ];
                } else {
                    $orderBy = [];
                }
                break;
            default: // 根据添加时间倒序
                $orderBy = [
//                    "is_default" => "DESC",
                    "created" => "DESC",
                    "distributor_id" => "DESC"
                ];
                break;
        }

        // 处理禁用商户下的店铺
        $merchantService = new MerchantService();
        $filter = $merchantService->__formateFilter($filter);
        // 查询店铺信息
        $setting = (new SettingService())->getOpenDistributorDivided($authInfo['company_id']);
        $outSort = [];
        if($setting['status'] === true){//开启了，就找没开启店铺
            $filter['open_divided'] = 0;
            $showType =$request->input('show_type', '');
            if(!empty($showType)){
                $service = new DistributorWhiteListService();
                if($showType === 'self'){//获取自己的名单
                    $distributorIdArr = $service->getUserWhiteListDistributor($userId,$authInfo['company_id']);
                    if(empty($distributorIdArr)){
                        $result["total_count"] =  0;
                        $result["list"] = [];
                        return $this->response->array($result);
                    }
                    $distributorId = array_keys($distributorIdArr);
                    $whiteDistributorId = $distributorId;
                    if (!empty($lng) && !empty($lat)) {
                        $outSort = [];
                    }else{
                        $outSort = $distributorIdArr;
                    }
                    $filter['open_divided'] = 1;
                    $noHaving = true;
                    $filter['distributor_id'] = $whiteDistributorId;
//                $filter['distributor_id'] = $distributorId;
                }
            }
        }
        $distributorLists = $distributorService->lists($filter, $orderBy, $pageSize, $page, $noHaving);
        if(!empty($outSort)){
            if(!empty($distributorLists['list'])){
                foreach ($distributorLists['list'] as $keyData=>$valueData){
                    $distributorIdBase  = $valueData['distributor_id'];
                    if(!empty($outSort[$distributorIdBase])){
                        $distributorLists['list'][$keyData]['sort_id'] =$outSort[$distributorIdBase]['id'];
                    }
                }
            }
        }
        $result["total_count"] = $distributorLists["total_count"] ?? 0;
        $result["list"] = $distributorLists["list"] ?? [];


//        if ($page == 1) {
//            $shopsService = new ShopsService(new WxShopsService);
//            $companySetting = $shopsService->getWxShopsSetting($authInfo['company_id']);
//            $arr[0]['store_address'] = '';
//            $arr[0]['address'] = '';
//            if ($companySetting) {
//                $arr[0]['distributor_id'] = 0;
//                $arr[0]['store_name'] = $companySetting['brand_name'];
//                $arr[0]['name'] = $companySetting['brand_name'];
//                $arr[0]['logo'] = $companySetting['logo'];
//            } else {
//                $arr[0]['distributor_id'] = 0;
//                $arr[0]['store_name'] = '商城';
//                $arr[0]['name'] = '商城';
//            }
//            //$result['list'] = array_merge($arr, $result['list']);
//        }

        // 追加店铺标签
        if ($showTag) {
            $distributorService->appendTagList($companyId, $result["list"], $result["tagList"]);
        }
        // 追加店铺优惠券
        if ($showDiscount) {
            $distributorService->appendCouponList($companyId, $result["list"]);
        }
        // 追加店铺满折满减满赠
        if ($showMarketingActivity) {
            $distributorService->appendPromotionsMarketingActivity($companyId, $result["list"]);
        }
        // 追加店铺销售数量
        if ($showSalesCount) {
            $distributorService->appendSalesCount($companyId, $result["list"]);
        }
        // 追加评分
        if ($showScore) {
            $distributorService->appendScore($companyId, $result["list"]);
        }
        // 追加店铺商品
        if($showItems){
           $item_tag_id = $request->input('item_tag_id',[]);
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $companyId, 'tag_id' => $item_tag_id];
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);

            $distributorService->appendItemsByFilter((int)$companyId, $result['list'],['item_id'=>$itemIds]);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/self",
     *     tags={"店铺"},
     *     summary="获取店铺总店",
     *     description="获取店铺总部自提点详细信息",
     *     operationId="getDistributionSelfDetail",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                @SWG\Property( property="distributor_id", type="string", example="105", description="店铺id"),
     *                @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                @SWG\Property( property="mobile", type="string", example="17621716237", description="电话"),
     *                @SWG\Property( property="address", type="string", example="徐汇区", description="地址"),
     *                @SWG\Property( property="name", type="string", example="普天信息产业园", description="名称"),
     *                @SWG\Property( property="created", type="string", example="1608012760", description="创建时间"),
     *                @SWG\Property( property="updated", type="string", example="1610533984", description="更新时间"),
     *                @SWG\Property( property="is_valid", type="string", example="true", description=""),
     *                @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                @SWG\Property( property="area", type="string", example="徐汇区", description="区县"),
     *                @SWG\Property(property="regions_id", type="array", description="地区id",
     *                    @SWG\Items( type="string", example="130000", description=""),
     *                ),
     *                @SWG\Property(property="regions", type="array", description="",
     *                    @SWG\Items( type="string", example="上海市", description="地区名称"),
     *                ),
     *                @SWG\Property( property="contact", type="string", example="wuqiong ", description=""),
     *                @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                @SWG\Property( property="shop_id", type="string", example="0", description="shop_id"),
     *                @SWG\Property( property="is_default", type="string", example="true", description="是否默认"),
     *                @SWG\Property( property="is_ziti", type="string", example="true", description="是否自提"),
     *                @SWG\Property( property="lng", type="string", example="121.43687", description="经度"),
     *                @SWG\Property( property="lat", type="string", example="31.18826", description="纬度"),
     *                @SWG\Property( property="hour", type="string", example="08:00-21:00", description=""),
     *                @SWG\Property( property="auto_sync_goods", type="string", example="false", description="自动同步商品"),
     *                @SWG\Property( property="logo", type="string", example="null", description="logo"),
     *                @SWG\Property( property="banner", type="string", example="null", description="banner"),
     *                @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核商品"),
     *                @SWG\Property( property="is_delivery", type="string", example="true", description=""),
     *                @SWG\Property( property="shop_code", type="string", example="1234567", description="shop_code"),
     *                @SWG\Property( property="review_status", type="string", example="0", description=""),
     *                @SWG\Property( property="source_from", type="string", example="1", description=""),
     *                @SWG\Property( property="distributor_self", type="string", example="0", description=""),
     *                @SWG\Property( property="is_distributor", type="string", example="true", description="是否门店"),
     *                @SWG\Property( property="contract_phone", type="string", example="17621716237", description="电话"),
     *                @SWG\Property( property="is_domestic", type="string", example="1", description=""),
     *                @SWG\Property( property="is_direct_store", type="string", example="1", description="是否直营店"),
     *                @SWG\Property( property="wechat_work_department_id", type="string", example="0", description=""),
     *                @SWG\Property( property="regionauth_id", type="string", example="1", description=""),
     *                @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                @SWG\Property( property="rate", type="string", example="", description=""),
     *                @SWG\Property( property="distance", type="string", example="0", description="距离"),
     *                @SWG\Property( property="store_address", type="string", example="上海市徐汇区徐汇区", description="地址"),
     *                @SWG\Property( property="store_name", type="string", example="普天信息产业园", description="名称"),
     *                @SWG\Property( property="phone", type="string", example="17621716237", description="手机号"),
     *                @SWG\Property( property="distance_show", type="string", example="0", description="距离显示"),
     *                @SWG\Property( property="distance_unit", type="string", example="m", description="距离单位"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getDistributionSelfDetail(Request $request)
    {
        // 获取企业id
        $authInfo = $request->get('auth');
        // 设置过滤条件
        $filter = [
            "company_id" => $authInfo['company_id'] ?? 1,
            "distributor_self" => 1
        ];
        if (!$filter["company_id"]) {
            return $this->response->array([]);
        }
        // 获取总店信息
        $result = (new DistributorService())->lists($filter, [], 1, 1);
        $list = (array)($result["list"] ?? []);
        return $this->response->array((array)array_shift($list));
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/default",
     *     tags={"店铺"},
     *     summary="获取默认店铺",
     *     description="获取默认店铺详细嘻嘻",
     *     operationId="getDistributionDefaultDetail",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                @SWG\Property( property="distributor_id", type="string", example="105", description="店铺id"),
     *                @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                @SWG\Property( property="mobile", type="string", example="17621716237", description="电话"),
     *                @SWG\Property( property="address", type="string", example="徐汇区", description="地址"),
     *                @SWG\Property( property="name", type="string", example="普天信息产业园", description="名称"),
     *                @SWG\Property( property="created", type="string", example="1608012760", description="创建时间"),
     *                @SWG\Property( property="updated", type="string", example="1610533984", description="更新时间"),
     *                @SWG\Property( property="is_valid", type="string", example="true", description=""),
     *                @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                @SWG\Property( property="area", type="string", example="徐汇区", description="区县"),
     *                @SWG\Property(property="regions_id", type="array", description="地区id",
     *                    @SWG\Items( type="string", example="130000", description=""),
     *                ),
     *                @SWG\Property(property="regions", type="array", description="",
     *                    @SWG\Items( type="string", example="上海市", description="地区名称"),
     *                ),
     *                @SWG\Property( property="contact", type="string", example="wuqiong ", description=""),
     *                @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                @SWG\Property( property="shop_id", type="string", example="0", description="shop_id"),
     *                @SWG\Property( property="is_default", type="string", example="true", description="是否默认"),
     *                @SWG\Property( property="is_ziti", type="string", example="true", description="是否自提"),
     *                @SWG\Property( property="lng", type="string", example="121.43687", description="经度"),
     *                @SWG\Property( property="lat", type="string", example="31.18826", description="纬度"),
     *                @SWG\Property( property="hour", type="string", example="08:00-21:00", description=""),
     *                @SWG\Property( property="auto_sync_goods", type="string", example="false", description="自动同步商品"),
     *                @SWG\Property( property="logo", type="string", example="null", description="logo"),
     *                @SWG\Property( property="banner", type="string", example="null", description="banner"),
     *                @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核商品"),
     *                @SWG\Property( property="is_delivery", type="string", example="true", description=""),
     *                @SWG\Property( property="shop_code", type="string", example="1234567", description="shop_code"),
     *                @SWG\Property( property="review_status", type="string", example="0", description=""),
     *                @SWG\Property( property="source_from", type="string", example="1", description=""),
     *                @SWG\Property( property="distributor_self", type="string", example="0", description=""),
     *                @SWG\Property( property="is_distributor", type="string", example="true", description="是否门店"),
     *                @SWG\Property( property="contract_phone", type="string", example="17621716237", description="电话"),
     *                @SWG\Property( property="is_domestic", type="string", example="1", description=""),
     *                @SWG\Property( property="is_direct_store", type="string", example="1", description="是否直营店"),
     *                @SWG\Property( property="wechat_work_department_id", type="string", example="0", description=""),
     *                @SWG\Property( property="regionauth_id", type="string", example="1", description=""),
     *                @SWG\Property( property="is_open", type="string", example="false", description=""),
     *                @SWG\Property( property="rate", type="string", example="", description=""),
     *                @SWG\Property( property="distance", type="string", example="0", description="距离"),
     *                @SWG\Property( property="store_address", type="string", example="上海市徐汇区徐汇区", description="地址"),
     *                @SWG\Property( property="store_name", type="string", example="普天信息产业园", description="名称"),
     *                @SWG\Property( property="phone", type="string", example="17621716237", description="手机号"),
     *                @SWG\Property( property="distance_show", type="string", example="0", description="距离显示"),
     *                @SWG\Property( property="distance_unit", type="string", example="m", description="距离单位"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getDistributionDefaultDetail(Request $request)
    {
        // 获取企业id
        $authInfo = $request->get('auth');
        // 设置过滤条件
        $filter = [
            "company_id" => $authInfo['company_id'] ?? 1,
            "is_default" => 1
        ];
        if (!$filter["company_id"]) {
            return $this->response->array([]);
        }
        // 获取总店信息
        $result = (new DistributorService())->lists($filter, [], 1, 1);
        $list = (array)($result["list"] ?? []);
        return $this->response->array((array)array_shift($list));
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/deliverytype",
     *     summary="获取店铺配送方式",
     *     tags={"店铺"},
     *     description="获取店铺配送方式",
     *     operationId="getDeliveryType",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="当前位置经度", type="number"),
     *     @SWG\Parameter( name="lat", in="query", description="当前位置纬度", type="number"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="delivery_name", type="stirng", example="配送方式名称"),
     *                     @SWG\Property(property="delivery_type", type="stirng", example="配送方式"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDeliveryType(Request $request)
    {
        $distributorId = $request->get('distributor_id', 0);
        if ($distributorId === 0 || $distributorId === 'undefined') {
            $result[] = [
                'delivery_name' => '快递配送',
                'delivery_type' => 'delivery',
            ];
            return $this->response->array($result);
        }
        $authInfo = $request->get('auth');
        $distributorService = new DistributorService();
        $filter = [
            'distributor_id' => $distributorId,
            'company_id' => $authInfo['company_id'],
        ];
        $storedata = $distributorService->getInfo($filter);
        if ($storedata['is_ziti'] && $storedata['is_ziti'] == 'true') {
            $result[] = [
                'delivery_name' => '自提',
                'delivery_type' => 'ziti',
                'address' => $storedata['address'],
            ];
        }

        if ($storedata['is_delivery'] && $storedata['is_delivery'] == 'true') {
            $result[] = [
                'delivery_name' => '快递配送',
                'delivery_type' => 'delivery',
            ];
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distributor",
     *     summary="申请店铺入驻",
     *     tags={"店铺"},
     *     description="申请店铺入驻",
     *     operationId="createDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="经销商的名称", required=true, type="string"),
     *     @SWG\Parameter( name="contact", in="query", description="联系人", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="经销商手机号", required=true, type="string"),
     *     @SWG\Parameter( name="is_delivery", in="query", description="是否支持配送", required=false, type="string"),
     *     @SWG\Parameter( name="is_ziti", in="query", description="是否支持自提", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="distributor_id", type="stirng"),
     *                     @SWG\Property(property="name", type="stirng"),
     *                     @SWG\Property(property="shop_code", type="stirng"),
     *                     @SWG\Property(property="address", type="stirng"),
     *                     @SWG\Property(property="mobile", type="stirng"),
     *                     @SWG\Property(property="shop_id", type="integer"),
     *                     @SWG\Property(property="contact", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function applyDistributor(Request $request)
    {
        $authInfo = $request->get('auth');
        $params = $request->only('name', 'address', 'mobile', 'is_valid', 'regions_id', 'regions', 'shop_id', 'contact', 'lng', 'lat', 'hour', 'logo', 'banner', 'auto_sync_goods', 'is_audit_goods', 'is_ziti', 'is_delivery', 'shop_code');
        $rules = [
            'name' => ['required|between:1,20', '请填写经销商名称'],
            'mobile' => ['required', '请填写正确的手机号'],
            'contact' => ['required', '请填写联系人'],
            'hour' => ['required', '请选择营业时间']
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if (isset($params['is_ziti'])) {
            $params['is_ziti'] = (!$params['is_ziti'] || $params['is_ziti'] === 'false') ? false : true;
        }
        if (isset($params['is_delivery'])) {
            $params['is_delivery'] = (!$params['is_delivery'] || $params['is_delivery'] === 'false') ? false : true;
        }
        if (isset($params['is_ziti'], $params['is_delivery'], $params['is_self_delivery']) && !$params['is_ziti'] && !$params['is_delivery']&& !$params['is_self_delivery']) {
            throw new Exception("自提和快递配送至少开启一项", 400500);
        }

        $params['company_id'] = $authInfo['company_id'];
        $params['source_from'] = 2;
        $params['is_valid'] = 'false';

        $distributorService = new DistributorService();
        $data = $distributorService->createDistributor($params);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/aftersaleaddress",
     *     summary="获取店铺售后地址",
     *     tags={"店铺"},
     *     description="根据店铺id获取店铺售后地址",
     *     operationId="getAftersaleAddressByDistributor",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="number" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="address", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="1", description="总条数"),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="address_id", type="string", example="42", description="地址id"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="104", description="分销商id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="province", type="string", example="北京市", description="省"),
     *                                  @SWG\Property( property="city", type="string", example="北京市", description="市"),
     *                                  @SWG\Property( property="area", type="string", example="东城区", description="区"),
     *                                  @SWG\Property( property="regions_id", type="string", example="['110000','110100','110101']", description="地区编号集合(DC2Type:json_array)"),
     *                                  @SWG\Property( property="regions", type="string", example="['北京市','北京市','东城区']", description="省市区合集(DC2Type:json_array)"),
     *                                  @SWG\Property( property="address", type="string", example="1", description="具体地址"),
     *                                  @SWG\Property( property="contact", type="string", example="1", description="联系人"),
     *                                  @SWG\Property( property="mobile", type="string", example="1", description="手机号"),
     *                                  @SWG\Property( property="post_code", type="string", example="null", description="邮政编码"),
     *                                  @SWG\Property( property="created", type="string", example="1610013243", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1610013243", description="修改时间"),
     *                                  @SWG\Property( property="is_default", type="string", example="1", description="默认地址, 1:是。2:不是"),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="distributor_info", type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="104", description="分销商id"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="17621612312", description="手机号"),
     *                          @SWG\Property( property="address", type="string", example="宜山路700号", description="具体地址"),
     *                          @SWG\Property( property="name", type="string", example="普天信息产业园", description="店铺名称"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4SsicubkrdnwoLMY38PLNULch2rPgsGb4NCVCC4EGa8EFs2MPCSbzJolznV64F0L5VetQvyE2ZrCcIb1ZALEA/0?wx_fmt=png", description="店铺logo"),
     *                          @SWG\Property( property="contract_phone", type="string", example="0", description="联系电话"),
     *                          @SWG\Property( property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkre4SsqeJKcShn3CyCQc3L52zM5jHpUo4hkicCiby1qmz5g5XpAIPg5JMFxgNcHUoCtg9vLT7QbzibP2w/0?wx_fmt=png", description="店铺banner"),
     *                          @SWG\Property( property="contact", type="string", example="张", description="联系人"),
     *                          @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                          @SWG\Property( property="lng", type="string", example="121.417537", description="地图纬度"),
     *                          @SWG\Property( property="lat", type="string", example="31.176567", description="地图经度"),
     *                          @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                          @SWG\Property( property="is_default", type="string", example="0", description="是否默认货币"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="true", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="310000", description=""),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="上海市", description=""),
     *                          ),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="徐汇区", description="区"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="created", type="string", example="1606292438", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1609999278", description="修改时间"),
     *                          @SWG\Property( property="shop_code", type="string", example="", description="店铺号"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="1", description="地区id"),
     *                          @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="rate", type="string", example="1000", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getAftersaleAddressByDistributor(Request $request)
    {
        $authInfo = $request->get('auth');
        $distributorId = $request->input('distributor_id', 0);

        if (!$distributorId) {
            return [];
        }

        $filter = [
            'distributor_id' => $distributorId,
            'company_id' => $authInfo['company_id'],
            'return_type' => 'logistics',
        ];

        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $address = $distributorAftersalesAddressService->getAftersalesAddressByDistributorId($filter);

        return $this->response->array($address);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/areainfo",
     *     summary="逆地址解析",
     *     tags={"店铺"},
     *     description="根据经纬度，逆地址解析",
     *     operationId="getAreaInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="当前位置经度", type="number" ),
     *     @SWG\Parameter( name="lat", in="query", description="当前位置纬度", type="number" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="nation", type="string", example="中国", description="国家"),
     *                  @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                  @SWG\Property( property="city", type="string", example="上海市", description="市 "),
     *                  @SWG\Property( property="district", type="string", example="徐汇区", description="区（县）"),
     *                  @SWG\Property( property="street", type="string", example="漕溪北路", description="街道"),
     *                  @SWG\Property( property="street_number", type="string", example="漕溪北路", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getAreaInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $mapData = MapService::make((int)$authInfo['company_id'])->getPosition($lat, $lng);
        if (empty($mapData->getAddressComponent())) {
            throw new ResourceException('地理位置信息获取失败');
        }
        return $this->response->array($mapData->getAddressComponent());
//        $result = get_area_by_lat_lng($lat, $lng);
//        if (empty($result)) {
//            throw new ResourceException('地理位置信息获取失败');
//        }
//        return $this->response->array($result['address_component'] ?? []);
        //if ($result['status'] ?? 1) throw new ResourceException('地理位置信息获取失败');
        //return $this->response->array($result['result']['address_component']);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/alllist",
     *     summary="获取所店铺有列表",
     *     tags={"店铺"},
     *     description="获取所有店铺列表",
     *     operationId="getAllDistributorList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", description="当前位置经度", type="number"),
     *     @SWG\Parameter( name="lat", in="query", description="当前位置纬度", type="number"),
     *     @SWG\Parameter( name="name", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="province", in="query", description="店铺所在省", type="string"),
     *     @SWG\Parameter( name="city", in="query", description="店铺所在市", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="16", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="distributor_id", type="string", example="27", description="分销商id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="13899998888", description="手机号"),
     *                          @SWG\Property( property="address", type="string", example="宜山路700号", description="地址"),
     *                          @SWG\Property( property="name", type="string", example="汇付天下总部大楼", description="店铺名称"),
     *                          @SWG\Property( property="created", type="string", example="1575379166", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1607406768", description="修改时间"),
     *                          @SWG\Property( property="is_valid", type="string", example="false", description="是否有效"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="徐汇", description="区"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="310000", description=""),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="上海市", description=""),
     *                          ),
     *                          @SWG\Property( property="contact", type="string", example="安嘉鑫", description="联系人名称"),
     *                          @SWG\Property( property="child_count", type="string", example="0", description=""),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="is_default", type="string", example="false", description="是否是默认门店"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="lng", type="string", example="121.41795", description="地图纬度"),
     *                          @SWG\Property( property="lat", type="string", example="31.17779", description="地图经度"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-23:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/a71563baed041faf9966966f654c5308utVTZDLD67LIBEGw3VOqtck8AF90tpSk", description="店铺logo"),
     *                          @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="shop_code", type="string", example="null", description="店铺号"),
     *                          @SWG\Property( property="review_status", type="string", example="0", description="入驻审核状态，0未审核，1已审核"),
     *                          @SWG\Property( property="source_from", type="string", example="1", description="店铺来源，1管理端添加，2小程序申请入驻"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="contract_phone", type="string", example="13899998888", description="联系电话"),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="0", description="地区id | 区域id"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="rate", type="string", example="", description=""),
     *                          @SWG\Property( property="distance", type="string", example="2.1435552092643815", description=""),
     *                          @SWG\Property( property="store_address", type="string", example="上海市徐汇宜山路700号", description=""),
     *                          @SWG\Property( property="store_name", type="string", example="汇付天下总部大楼", description="店铺名称"),
     *                          @SWG\Property( property="phone", type="string", example="13899998888", description=""),
     *                          @SWG\Property( property="distance_show", type="string", example="2.1435552092643815", description=""),
     *                          @SWG\Property( property="distance_unit", type="string", example="km", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getAllDistributorList(Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['distributor_self'] = 0;

        if ($lng = $request->input('lng')) {
            $filter['lng'] = $lng;
        }
        if ($lat = $request->input('lat')) {
            $filter['lat'] = $lat;
        }
        if ($name = $request->input('name')) {
            $filter['name|contains'] = $name;
        }
        if ($province = $request->input('province')) {
            $filter['province|contains'] = $province;
        }
        if ($city = $request->input('city')) {
            $filter['city|contains'] = $city;
        }
        $filter['is_valid'] = ['true', 'false'];

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 10);

        $orderBy = [];
        $distributorService = new DistributorService();
        $result = $distributorService->lists($filter, $orderBy, $pageSize, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/advertisements",
     *     summary="大屏广告接口",
     *     tags={"店铺"},
     *     description="大屏广告接口",
     *     operationId="getAdvertisements",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description="自行更改字段描述"),
     *                          @SWG\Property( property="title", type="string", example="123", description="标题"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="thumb_img", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfFPfB9nyal8uqQaYfmI3c3q7IZcTRNI1ZSkIiaiavPHKthR6pgvkdVXicH8BiaFnqdBTibv2icMZTabWfQ/0?wx_fmt=jpeg", description="缩略图"),
     *                          @SWG\Property( property="media_url", type="string", example="http://203.205.137.71/vweixinp.tc.qq.com/1007_b0fe2888851246da8b3c9b37a944b3bd.f10.mp4", description="(图片/视频)地址"),
     *                          @SWG\Property( property="release_time", type="string", example="1605778881", description="发布时间"),
     *                          @SWG\Property( property="release_status", type="string", example="true", description="状态"),
     *                          @SWG\Property( property="created", type="string", example="1605182300", description=""),
     *                          @SWG\Property( property="sort", type="string", example="null", description="排序"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="media_type", type="string", example="video", description="类型"),
     *                          @SWG\Property( property="media", type="object",
     *                                  @SWG\Property( property="url", type="string", example="http://203.205.137.71/vweixinp.tc.qq.com/1007_b0fe2888851246da8b3c9b37a944b3bd.f10.mp4", description=""),
     *                                  @SWG\Property( property="type", type="string", example="video", description=""),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="thumb_img", type="array",
     *                      @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrfFPfB9nyal8uqQaYfmI3c3q7IZcTRNI1ZSkIiaiavPHKthR6pgvkdVXicH8BiaFnqdBTibv2icMZTabWfQ/0?wx_fmt=jpeg", description=""),
     *                  ),
     *                  @SWG\Property( property="media", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="url", type="string", example="http://203.205.137.71/vweixinp.tc.qq.com/1007_b0fe2888851246da8b3c9b37a944b3bd.f10.mp4", description=""),
     *                          @SWG\Property( property="type", type="string", example="video", description=""),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */

    public function getAdvertisements(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'distributor_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('参数错误.', $validator->errors());
        }
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $filter['company_id'] = $company_id;

        $filter['distributor_id'] = $request->input('distributor_id');
        $advertisementService = new AdvertisementService();
        $result = [];
        $result = $advertisementService->getStartAds($filter); //获取开屏广告
        return $this->response->array([$result]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/slider",
     *     summary="获取首页轮播图",
     *     tags={"店铺"},
     *     description="获取首页轮播图",
     *     operationId="getSlider",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="经销商ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="slide_id", type="string", example="1", description="轮播id"),
     *                  @SWG\Property( property="title", type="string", example="123", description="标题"),
     *                  @SWG\Property( property="sub_title", type="string", example="123", description="副标题"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="style_params", type="object",
     *                          @SWG\Property( property="content", type="string", example="true", description="内容"),
     *                          @SWG\Property( property="current", type="string", example="", description=""),
     *                          @SWG\Property( property="dot", type="string", example="true", description=""),
     *                          @SWG\Property( property="dotColor", type="string", example="dark", description=""),
     *                          @SWG\Property( property="dotCover", type="string", example="false", description=""),
     *                          @SWG\Property( property="dotLocation", type="string", example="center", description=""),
     *                          @SWG\Property( property="interval", type="string", example="", description=""),
     *                          @SWG\Property( property="numNavShape", type="string", example="", description=""),
     *                          @SWG\Property( property="padded", type="string", example="false", description=""),
     *                          @SWG\Property( property="rounded", type="string", example="false", description=""),
     *                          @SWG\Property( property="shape", type="string", example="circle", description=""),
     *                          @SWG\Property( property="spacing", type="string", example="", description=""),
     *                  ),
     *                  @SWG\Property( property="image_list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrcEeaxt19CTsJiaYRR6U1G6ficMu3btW2zdrshIcVoEtLylUgtnc1Ry1EDqO4ouaoTGMpOZOnqQB3UQ/0?wx_fmt=jpeg", description=""),
     *                          @SWG\Property( property="desc", type="string", example="", description=""),
     *                          @SWG\Property( property="id", type="string", example="", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="desc_status", type="string", example="true", description="图片描述状态11"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getSlider(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $filter['company_id'] = $company_id;
        $filter['distributor_id'] = $request->input('distributor_id', 0);
        $SliderService = new SliderService();
        $result = [];
        $result = $SliderService->getSlider($filter); //获取开屏广告
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/image/code",
     *     summary="获取图片验证码",
     *     tags={"店铺"},
     *     description="获取图片验证码",
     *     operationId="getImageVcode",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="imageToken", type="string", example="3ec84c3922b23d0d6dd6bc95d38b3b5c", description="图片token"),
     *                  @SWG\Property( property="imageData", type="string", example="data:image/png;base64,", description="base64图片信息"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getImageVcode(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $type = $request->input('type', 'bind');

        if (!in_array($type, $this->code_type)) {
            throw new ResourceException("图片验证码类型错误");
        }

        $distributorSmsService = new DistributorSmsService();
        list($token, $imgData) = $distributorSmsService->generateImageVcode($companyId, $type);
        return $this->response->array([
            'imageToken' => $token,
            "imageData" => $imgData,
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/sms/code",
     *     summary="获取手机短信验证码",
     *     tags={"店铺"},
     *     description="获取手机短信验证码",
     *     operationId="getSmsCode",
     *     @SWG\Parameter( in="query", type="string", required=true, name="type", description="验证码类型 login 登录验证码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="yzm", description="图片验证码的值" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="token", description="图片验证码token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="company_id", description="企业id" ),
     *     @SWG\Parameter( in="query", type="number", required=false, name="distributor_id", description="店铺id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="error", type="object",
     *                  @SWG\Property( property="message", type="string", example="验证码错误", description="提示信息"),
     *                  @SWG\Property( property="status_code", type="string", example="422", description="错误码"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones")))
     * )
     */
    public function getSmsCode(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        $postdata = $request->all();
        $validator = app('validator')->make($postdata, [
            'distributor_id' => 'required',
            'yzm' => 'required',
            'token' => 'required',
        ], [
            'distributor_id' => '店铺id必填',
            'yzm' => '图片验证码必填',
            'token' => 'token必填'
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $type = $request->input('type', 'bind');

        if (!in_array($type, $this->code_type)) {
            throw new ResourceException("手机验证码类型错误");
        }
        // 校验手机号是否存在
        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfo(['distributor_id' => $postdata['distributor_id'], 'company_id' => $companyId]);
        if (!$distributorInfo || !$distributorInfo['mobile']) {
            throw new ResourceException("店铺或店铺联系手机不存在");
        }

        $phone = $distributorInfo['mobile'];
        $token = $postdata['token'];
        $yzmcode = $postdata['yzm'];
        $distributorSmsService = new DistributorSmsService();
        if (!$distributorSmsService->checkImageVcode($token, $companyId, $yzmcode, $type)) {
            throw new ResourceException("验证码错误");
        }

        $distributorSmsService->generateSmsVcode($phone, $companyId, $type);
        return $this->response->array(['message' => "短信发送成功"]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/distributor/sms/code",
     *     summary="验证短信验证码",
     *     tags={"店铺"},
     *     description="验证短信验证码",
     *     operationId="checkSmsVcode",
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         type="number",
     *     ),
     *     @SWG\Parameter(
     *         name="vcode",
     *         in="query",
     *         description="验证码",
     *         type="string"
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
     *                     @SWG\Property(property="cardId", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function checkSmsVcode(Request $request)
    {
        $postdata = $request->all();
        // middleware中添加的参数获取方式
        $authInfo = $request->get('auth');

        $validator = app('validator')->make($postdata, [
            'distributor_id' => 'required',
            'vcode' => 'required',
        ], [
            'distributor_id' => '店铺id必填',
            'vcode' => '验证码必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $distributorService = new DistributorService();
        $result = $distributorService->getInfo(['distributor_id' => $postdata['distributor_id'], 'company_id' => $authInfo['company_id']]);
        if (!$result || !$result['mobile']) {
            throw new ResourceException("店铺或店铺联系手机不存在");
        }

        $distributorSmsService = new DistributorSmsService();
        if (!$distributorSmsService->checkSmsVcode($result['mobile'], $authInfo['company_id'], $postdata['vcode'], 'bind')) {
            throw new ResourceException('验证码错误');
        }

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/getDistributorInfo",
     *     summary="获取店铺信息",
     *     tags={"店铺"},
     *     description="获取指定店铺信息或者默认店铺信息",
     *     operationId="getDistributorInfo",
     *     @SWG\Parameter( name="company_id", in="query", description="company_id", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", required=false, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="distributor_id", type="string", example="21", description=""),
     *               @SWG\Property(property="shop_id", type="string", example="0", description="门店id"),
     *               @SWG\Property(property="is_distributor", type="string", example="1", description="是否是主店铺"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="mobile", type="string", example="18098987759", description="店铺手机号"),
     *               @SWG\Property(property="address", type="string", example="鹿岭路6号三亚悦榕庄", description="店铺地址"),
     *               @SWG\Property(property="name", type="string", example="标准版测试用店铺，开启自提自动同步", description="店铺名称"),
     *               @SWG\Property(property="auto_sync_goods", type="string", example="1", description="自动同步总部商品"),
     *               @SWG\Property(property="logo", type="string", example="http://mmbiz.qpic.cn/mmbiz_gif/1nDJByqmW2cfI9RKuteqnL3P5AW0cNlWGP9TTnPgYakZECiafK6Tl43UxQzI598U2OZbnMagIRQCEdTbaSvbhRQ/0?wx_fmt=gif", description="店铺logo"),
     *               @SWG\Property(property="contract_phone", type="string", example="18098987759", description="其他联系方式"),
     *               @SWG\Property(property="banner", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/1nDJByqmW2fN5gAtA3mq4kJK7fUeDLuJia1XicD09yExRV5h3mm3x8s9TjpiczDLLaLY655MnyKcHdicnCSjvAiaY0A/0?wx_fmt=jpeg", description="店铺banner"),
     *               @SWG\Property(property="contact", type="string", example="松子", description="联系人名称"),
     *               @SWG\Property(property="is_valid", type="string", example="true", description="店铺是否有效"),
     *               @SWG\Property(property="lng", type="string", example="109.498", description="腾讯地图纬度"),
     *               @SWG\Property(property="lat", type="string", example="18.21967", description="腾讯地图经度"),
     *               @SWG\Property(property="child_count", type="integer", example="0", description=""),
     *               @SWG\Property(property="is_default", type="integer", example="1", description="门店id"),
     *               @SWG\Property(property="is_audit_goods", type="string", example="1", description="是否审核店铺商品"),
     *               @SWG\Property(property="is_ziti", type="string", example="1", description="是否支持自提"),
     *               @SWG\Property(property="regions_id", type="array", description="",
     *                 @SWG\Items(
     *                    type="string", example="460000", description=""
     *                 ),
     *               ),
     *               @SWG\Property(property="regions", type="array", description="",
     *                 @SWG\Items(
     *                    type="string", example="海南省", description=""
     *                 ),
     *               ),
     *               @SWG\Property(property="is_domestic", type="integer", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *               @SWG\Property(property="is_direct_store", type="integer", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *               @SWG\Property(property="province", type="string", example="海南省", description=""),
     *               @SWG\Property(property="is_delivery", type="string", example="1", description="是否支持配送"),
     *               @SWG\Property(property="city", type="string", example="三亚", description=""),
     *               @SWG\Property(property="area", type="string", example="天涯区", description=""),
     *               @SWG\Property(property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *               @SWG\Property(property="created", type="integer", example="1571302265", description=""),
     *               @SWG\Property(property="updated", type="integer", example="1595559722", description=""),
     *               @SWG\Property(property="shop_code", type="string", example="abc12QQ", description="店铺号"),
     *               @SWG\Property(property="wechat_work_department_id", type="integer", example="5", description="企业微信的部门ID"),
     *               @SWG\Property(property="distributor_self", type="integer", example="0", description="是否是总店配置"),
     *               @SWG\Property(property="regionauth_id", type="string", example="0", description="区域id"),
     *               @SWG\Property(property="is_open", type="string", example="false", description="是否开启分账"),
     *               @SWG\Property(property="rate", type="string", example="", description="平台服务费率"),
     *               @SWG\Property(property="store_address", type="string", example="海南省三亚天涯区鹿岭路6号三亚悦榕庄", description=""),
     *               @SWG\Property(property="store_name", type="string", example="标准版测试用店铺，开启自提自动同步", description=""),
     *               @SWG\Property(property="phone", type="string", example="18098987759", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getDistributorInfo(Request $request)
    {
        $filter['distributor_id'] = $request->get('distributor_id', 0);
        $filter['company_id'] = $request->get('company_id');
        $distributorService = new DistributorService();
        $result = $distributorService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/merchant/isvaild",
     *     summary="查询商家是否可用",
     *     tags={"店铺"},
     *     description="根据店铺查询关联店铺是否可用",
     *     operationId="getDistributorMerchantIsvaild",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", required=false, description="店铺id", type="number", ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", example="是否可用 true:可用 false:不可用"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getDistributorMerchantIsvaild(Request $request)
    {
        $distributorId = $request->get('distributor_id', 0);
        $authInfo = $request->get('auth');
        $distributorService = new DistributorService();
        $status = $distributorService->checkMerchantIsvaild($authInfo['company_id'], $distributorId);

        return $this->response->array(['status' => $status]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/pickuplocation",
     *     summary="查询附近的门店自提点列表",
     *     tags={"店铺"},
     *     description="查询附近的门店自提点列表",
     *     operationId="getNearPickupLocation",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", required=true, description="经度", type="number"),
     *     @SWG\Parameter( name="lat", in="query", required=true, description="纬度", type="number"),
     *     @SWG\Parameter( name="cart_type", in="query", required=false, description="购物车类型:fastbuy 立即购买,cart 购物车", type="string"),
     *     @SWG\Parameter( name="isNostores", in="query", required=false, description="是否无门店销售", type="boolean"),
     *     @SWG\Parameter( name="distributor_id", in="query", required=false, description="店铺ID", type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getNearPickupLocation(Request $request)
    {
        $authInfo = $request->get('auth');
        $postData = $request->all();

        $validator = app('validator')->make($postData, [
            'lng' => 'numeric|between:-180.0,180.0',
            'lat' => 'numeric|between:-90.0,90.0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('请授权当前所在位置或传入正确的经纬度');
        }
        $postData['lng'] = $postData['lng'] ?? null;
        $postData['lat'] = $postData['lat'] ?? null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'is_valid' => 'true',
            'is_ziti' => true,
        ];
        $distributorService = new DistributorService();
        if (isset($postData['isNostores']) && $postData['isNostores'] == 1) {
            $params['company_id'] = $authInfo['company_id'];
            $params['user_id'] = $authInfo['user_id'];
            $params['cart_type'] = $postData['cart_type'] ?? 'cart';
            $params['order_type'] = 'normal';
            $params['seckill_id'] = $postData['seckill_id'] ?? '';
            $params['seckill_ticket'] = $postData['seckill_ticket'] ?? '';
            $params['iscrossborder'] = $postData['iscrossborder'] ?? '';
            $params['bargain_id'] = $postData['bargain_id'] ?? 0;
            $params['cart_distributor_id'] = $postData['distributor_id'] ?? 0;
            // 根据条件，获取购买商品都有库存的店铺id
            $distributorIds = $distributorService->getShopIdsByNostores($filter, $params);
            if (!$distributorIds) {
                return $this->response->array(['total_count' => 0, 'list' => []]);
            }
        } else {
            if (isset($postData['distributor_id']) && $postData['distributor_id'] > 0) {
                $distributorIds = [$postData['distributor_id']];
            } else {
                $filter['distributor_self'] = 1;
                $selfDistributor = $distributorService->getInfoSimple($filter);
                if (!$selfDistributor) {
                    return $this->response->array(['total_count' => 0, 'list' => []]);
                }
                $distributorIds = [$selfDistributor['distributor_id']];
            }
        }

        $pFilter = [
            'company_id' => $authInfo['company_id'],
            'rel_distributor_id' => $distributorIds,
        ];
        $pickupLocationService = new PickupLocationService();
        $result = $pickupLocationService->getNearlists($pFilter, $postData['lng'], $postData['lat'], '*', 1, 100);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/distributor/aftersaleslocation",
     *     summary="查询附近的门店退货点列表",
     *     tags={"店铺"},
     *     description="查询附近的门店退货点列表",
     *     operationId="getNearAftersalesLocation",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="lng", in="query", required=true, description="经度", type="number"),
     *     @SWG\Parameter( name="lat", in="query", required=true, description="纬度", type="number"),
     *     @SWG\Parameter( name="distributor_id", in="query", required=false, description="店铺ID", type="number"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getNearAftersalesLocation(Request $request) {
        $authInfo = $request->get('auth');
        $postData = $request->all();

        $validator = app('validator')->make($postData, [
            'lng' => 'numeric|between:-180.0,180.0',
            'lat' => 'numeric|between:-90.0,90.0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('请授权当前所在位置或传入正确的经纬度');
        }
        $postData['lng'] = $postData['lng'] ?? null;
        $postData['lat'] = $postData['lat'] ?? null;

        $distributorService = new DistributorService();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'offline_aftersales' => 1,
        ];
        if (isset($postData['distributor_id']) && $postData['distributor_id'] > 0) {
            $filter['distributor_id'] = $postData['distributor_id'];
        } else {
            $filter['distributor_self'] = 1;
        }
        $distributorInfo = $distributorService->getInfoSimple($filter);
        if (!$distributorInfo) {
            return $this->response->array(['total_count' => 0, 'list' => []]);
        }

        $addressFilter['distributor_id'] = [];
        if ($distributorInfo['offline_aftersales_distributor_id']) {
            $distributorFilter = [
                'company_id' => $authInfo['company_id'],
                'distributor_id' => $distributorInfo['offline_aftersales_distributor_id'],
                'is_valid' => 'true',
                'offline_aftersales_other' => 1,
            ];
            $distributorList = $distributorService->getLists($distributorFilter, 'distributor_id,name');
            if ($distributorList) {
                $addressFilter['distributor_id'] = array_merge($addressFilter['distributor_id'], array_column($distributorList, 'distributor_id'));

                if (isset($postData['distributor_name']) && $postData['distributor_name']) {
                    $filteredDistributorid = [];
                    foreach ($distributorList as $row) {
                        if (strpos($row['name'], $postData['distributor_name']) !== false) {
                            $filteredDistributorid[] = $row['distributor_id'];
                        }
                    }
                }
            }
        }

        if ($distributorInfo['is_valid'] == 'true' && $distributorInfo['offline_aftersales_self']) {
            $addressFilter['distributor_id'][] = $distributorInfo['distributor_id'];
        }

        if (!$addressFilter['distributor_id']) {
            return $this->response->array(['total_count' => 0, 'list' => []]);
        }

        $addressFilter['company_id'] = $authInfo['company_id'];
        $addressFilter['return_type'] = 'offline';

        $distributorAftersalesAddressService = new DistributorAftersalesAddressService();
        $result = $distributorAftersalesAddressService->getNearlists($addressFilter, $postData['lng'], $postData['lat'], '*');

        if (isset($postData['distributor_name']) && $postData['distributor_name']) {
            foreach ($result['list'] as $key => $row) {
                if (!in_array($row['distributor_id'], $filteredDistributorid) && strpos($row['address'], $postData['distributor_name']) === false) {
                    unset($result['list'][$key]);
                    $result['total_count']--;
                }
            }
            $result['list'] = array_values($result['list']);
        }

        return $this->response->array($result);
    }

    // /wxapp/distributor/checkUserInWhite
    public function checkUserValidInDistributor(Request $request)
    {
        $params = $request->all();
        $authInfo = $request->get('auth');
        $distributorId = $params['distributor_id'];
        $userId = $authInfo['user_id'];
        $status = (new DistributorWhiteListService())->checkUserValid($distributorId,$userId,$authInfo['company_id']);
        return $this->response->array(['status'=>$status]);
    }

   /**
     * @SWG\Get(
     *     path="/wxapp/distributor/config/inRule",
     *     summary="获取店铺进店规则",
     *     tags={"店铺"},
     *     description="获取店铺进店规则",
     *     operationId="getInRule",
     *     @SWG\Parameter( in="query", type="string", required=true, name="type", description="验证码类型 login 登录验证码" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *             @SWG\Items( type="object",
     *                  @SWG\Property( property="distributor_code", type="string", example="2", description="店铺码进店"),
     *                  @SWG\Property( property="shop_assistant", type="string", example="2", description="导购物料进店"),
     *                  @SWG\Property( property="shop_assistant_pro", type="string", example="2", description="专属导购所属店"),
     *                  @SWG\Property( property="shop_lbs", type="string", example="2", description="lbs附近店"),
     *                  @SWG\Property( property="radio_type", type="string", example="2", description="兜底策略 1 默认点 2介绍面"),
     *                  @SWG\Property( property="default_shop", type="string", example="2", description="默认店"),
     *                  @SWG\Property( property="intro_page", type="string", example="2", description="介绍页面"),
     *            ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones")))
     * )
     */
    public function getInRule(Request $request)
    {
        $distributionService = new DistributionService();
        $authInfo = $request->get('auth');
        $companyId = isset($authInfo['company_id']) ? $authInfo['company_id'] : $request->get('company_id', 0);
        $result = $distributionService->getDistributionConfigInRule($companyId);
        
        return $this->response->array($result ?? []);
    }

    public function checkInRule(Request $request)
    {
        $params = $request->all();
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $workWechatRepositories = app('registry')->getManager('default')->getRepository(WorkWechatRel::class);
        $salespersonService = new SalespersonService();
        $distributorService = new DistributorService();
        try{
            if (isset($params['work_userid']) && !empty($params['work_userid'])) {
                // 如果传了work_userid,则直接使用work_userid去找对应导购的门店
                $work_userid = $params['work_userid'];
                $filter = [
                    'company_id' => $companyId,
                    'work_userid' => $work_userid,
                    'salesperson_type' => 'shopping_guide',
                    // 'is_valid' => 'true',
                ];
                $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
                if (empty($salespersonInfo)) {
                    throw new ResourceException('导购员不存在');
                }
                if (isset($salespersonInfo['is_valid']) && ($salespersonInfo['is_valid'] != 'true' || $salespersonInfo['is_valid'] != true)) {
                    throw new ResourceException('导购员失效');
                }
                // 验证店铺是否有效
                $filterDistributor = [
                    'company_id' => $companyId,
                    'distributor_id' => $salespersonInfo['distributor_id'],
                    // 'is_valid' => 'true',
                ];
                $info = $distributorService->getInfoSimple($filterDistributor);
                if (empty($info)) {
                    throw new ResourceException('店铺不存在');
                }
                if (isset($info['is_valid']) && ($info['is_valid'] != 'true' || $info['is_valid'] != true)) {
                    throw new ResourceException('店铺失效');
                }
                $result = [
                    'status' => true,
                    'msg' => '成功',
                    'data' => [
                        'distributor_id' => $salespersonInfo['distributor_id'],
                        'distributor_ids' => $salespersonInfo['distributor_ids'],
                        'salesperson_id' => $salespersonInfo['salesperson_id'],
                    ]
                ];
            }else {
                // 必须登录
                if (!$userId) {
                    throw new ResourceException('请先登录');
                }
                // 如果用户有专属导购,进行验证店铺或者导购是否有效
                $filter = [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'is_bind' => 1,
                ];
                $result = $workWechatRepositories->getInfo($filter);
                if (!empty($result)) {
                    $filter = [
                        'company_id' => $companyId,
                        'salesperson_id' => $result['salesperson_id'],
                        'salesperson_type' => 'shopping_guide',
                        // 'is_valid' => 'true',
                    ];
                    $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
                    if (empty($salespersonInfo)) {
                        throw new ResourceException('导购员不存在');
                    }
                    if (isset($salespersonInfo['is_valid']) && ($salespersonInfo['is_valid'] != 'true' || $salespersonInfo['is_valid'] != true)) {
                        throw new ResourceException('导购员失效');
                    }
                    // 验证店铺是否有效
                    $filterDistributor = [
                        'company_id' => $companyId,
                        'distributor_id' => $salespersonInfo['distributor_id'],
                        // 'is_valid' => 'true',
                    ];
                    $info = $distributorService->getInfoSimple($filterDistributor);
                    if (empty($info)) {
                        throw new ResourceException('店铺不存在');
                    }
                    if (isset($info['is_valid']) && ($info['is_valid'] != 'true' || $info['is_valid'] != true)) {
                        throw new ResourceException('店铺失效');
                    }
                    $result = [
                        'status' => true,
                        'msg' => '成功',
                        'data' => [
                            'distributor_id' => $salespersonInfo['distributor_id'],
                            'distributor_ids' => $salespersonInfo['distributor_ids'],
                            'salesperson_id' => $salespersonInfo['salesperson_id'],
                        ]
                    ];
                }else {
                    $result = ['status' => false, 'msg' => '用户没有专属导购', 'data' => []];
                }
            }   
        }catch(\Exception $e) {
            return $this->response->array(['status' => false, 'msg' => $e->getMessage(), 'data' => []]);
        }
        
        return $this->response->array($result);
    }

    // 获取用户所属白名单店铺列表
    public function getDistributorListByWhiteMember(Request $request)
    {
        $params = $request->all();
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $distributorWhiteListService = new DistributorWhiteListService();
        $data = $distributorWhiteListService->getUserWhiteListDistributorList($userId, $companyId);

        return $this->response->array($data);
    }

}
