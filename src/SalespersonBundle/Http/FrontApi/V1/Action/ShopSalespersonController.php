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

namespace SalespersonBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorService;
use CompanysBundle\Services\OperatorsService;
use PopularizeBundle\Services\BrokerageService;

use PopularizeBundle\Services\PromoterService;
use MembersBundle\Services\MemberService;

class ShopSalespersonController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/distributorlist",
     *     summary="获取导购店铺列表",
     *     tags={"导购"},
     *     description="获取导购店铺列表",
     *     operationId="getDistributorDataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=true, type="string", default="distributor"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="门店id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型"),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【店铺】视力康眼镜(中兴路店)", description="店铺名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getDistributorDataList(Request $request)
    {
        $authInfo = $this->auth->user();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 500);
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter['company_id'] = $authInfo['company_id'];
        $dIds = [];
        if ($filter['company_id'] == $authInfo['company_id']) {
            $filter['salesperson_id'] = $authInfo['salesperson_id'];
            $filter['store_type'] = $request->get('store_type', 'distributor');
            // 根据店铺名称筛选
            if ($request->get('store_name', '') && trim($request->get('store_name'))) {
                $filter['store_name'] = trim($request->get('store_name'));
            }
            $listdata = $salespersonService->getSalespersonRelShopdata($filter, $page, $pageSize);
            // $dIds = array_column($listdata['list'],'distributor_id');
        }
        // $distributorService = new DistributorService();
        // $distributor = $distributorService->getDefaultDistributor($filter['company_id']);
        // if ($distributor && (!$dIds || !in_array($distributor['distributor_id'],$dIds))) {
        //     $listdata['list'][] = [
        //         'address' => $distributor['address'],
        //         'store_name' => $distributor['name'],
        //         'distributor_id' => $distributor['distributor_id'],
        //         'shop_logo' => $distributor['logo'],
        //         'hour' => $distributor['hour'],
        //     ];
        //     $listdata['total_count'] = 1;
        // }
        return $this->response->array($listdata);
    }
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salesperson/SalemanShopList",
     *     summary="获取导购店铺列表",
     *     tags={"导购"},
     *     description="获取导购店铺列表",
     *     operationId="getDistributorDataList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=true, type="string", default="distributor"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="33", description="门店id"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="45", description="导购员ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="store_type", type="string", example="distributor", description="店铺类型"),
     *                          @SWG\Property( property="address", type="string", example="中兴路实验小学楼下", description="店铺地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【店铺】视力康眼镜(中兴路店)", description="店铺名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/1/2019/12/03/...", description="店铺Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="08:00-21:00", description="营业时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function SalemanShopList(Request $request)
    {
        $authInfo = $request->get('auth');
        $mobile   = $request->input('mobile',false);
        $name     = $request->input('name',false);

        // $authInfo = $this->auth->user();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 500);
        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id']    = $authInfo['user_id'];

        $listdata = $salespersonService->getSalespersonList($filter, $page, $pageSize);
        $dIds = array_column($listdata['list'],'shop_id');
        $salesperson_arr = array_column($listdata['list'],null,'shop_id');
        // $salesperson = $listdata['list'] ?? [];
        foreach($salesperson_arr as $kSales => &$vSales){
            if($vSales['is_valid'] === 'true'){
                $vSales['is_valid'] = true;
            }
            if($vSales['is_valid'] === 'false'){
                $vSales['is_valid'] = false;
            }
        }   

        if(!$dIds){
            return $this->response->array(array());
        }
        $distributorService = new DistributorService();
        $filter_store =  array(['distributor_id' => $dIds]);
        if($name)   $filter_store['name|like']   = $name;
        if($mobile) $filter_store['mobile|like'] = $mobile;
        $listShop = $distributorService->lists($filter_store, [], -1, 0);
        foreach($listShop['list'] as $kShop => &$vShop){
            $vShop['user_id'] = $authInfo['user_id'];
            $vShop['salesperson'] = $salesperson_arr[$vShop['distributor_id']] ?? [];
        }


        // $listShop['salesperson'] = $salesperson ?? [];
        return $this->response->array($listShop);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/salesperson/distributor/is_valid",
     *     summary="验证导购员的店铺id是否有效",
     *     tags={"导购"},
     *     description="验证导购员的店铺id是否有效",
     *     operationId="checkDistributorIsValid",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token，workwechatlogin返回的session3rd值", required=true, type="string", default="vaUpvrHrgsEWG54xqmY+IA=="),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="salesperson_id", in="query", required=true, description="导购id", type="number", ),
     *     @SWG\Parameter( name="distributor_id", in="query", required=true, description="店铺id", type="number", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true, description="校验结果"),
     *
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function checkDistributorIsValid(Request $request)
    {
        $postdata = $request->all('salesperson_id', 'distributor_id');
        $rules = [
            'salesperson_id' => ['required', '导购员id不能为空'],
            'distributor_id' => ['required', '店铺id不能为空'],
        ];
        $error = validator_params($postdata, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $authInfo = $this->auth->user();
        $salespersonService = new SalespersonService();
        $status = $salespersonService->checkDistributorIsValid($authInfo['company_id'], $postdata['salesperson_id'], $postdata['distributor_id']);
        return $this->response->array(['status' => $status]);
    }


    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/bindusersalesperson",
     *     summary="业务员关系绑定24小时有效",
     *     tags={"导购"},
     *     description="业务员关系绑定24小时有效",
     *     operationId="bindusersalesperson",
     *     @SWG\Parameter( in="header", type="string", required=true, name="authorization", description="jwt验证token" ),
     *     @SWG\Parameter( in="query", type="string", name="promoter_user_id", description="业务员导购ID" ),
     *     @SWG\Parameter( in="query", type="string", name="promoter_shop_id", description="业务员店铺ID" ),
     *     @SWG\Parameter( in="query", type="string", name="promoter_item_id", description="业务员商品ID" ),
     *     @SWG\Response( response=200, description="成功返回结构",
     *          @SWG\Property(
     *              property="data",
     *              ref="#/definitions/SalesPersonComplaint"
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function bindusersalesperson(Request $request)
    {
        $authInfo = $request->get('auth');

        $request_data = $request->all('promoter_user_id', 'promoter_shop_id', 'promoter_item_id');
        
        if (!$request_data['promoter_user_id'] ) {
            throw new ResourceException('导购更新业务员信息错误');
        }
        if ( !$authInfo['user_id']) {
            throw new ResourceException('导购更新用户信息错误');
        }

        $data = json_encode($request_data);
        $key = "promoter_user_info_dayset:" . $authInfo['user_id'] ;
        $extime = env('PROMOTER_INFO_EXTIME_SETTING',86400);
        $res = app('redis')->setex($key, $extime, $data);//一天
        $promoterinfo = app('redis')->get($key);
        $result = array('status'=>1,'code' => 0,'data' => $request_data,
        'key'=>$key,
        'extime'=>$extime,
        'promoterinfo'=>$promoterinfo );
        return $this->response->array($result);
    } 
    
    
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salespersonadmin/storemanagerinfo",
     *     summary="店铺业务员管理-查询管理信息",
     */
     public function storemanagerinfo(Request $request){
        $authInfo = $request->get('auth');
        $inputData = $request->all();


        $operatorsService = new OperatorsService();
        $filter = array( 
            'company_id'    => $authInfo['company_id'],
            'mobile'        => $authInfo['mobile'],
            'operator_type' => 'distributor',
        );
        $operatorInfo = $operatorsService->getInfo($filter, $request->input('is_app'));

        if($operatorInfo && isset($operatorInfo['distributor_ids']) && $operatorInfo['distributor_ids']){
            $dids = array_column($operatorInfo['distributor_ids'],'distributor_id');
        }

        $manage_status = 0;
        if(isset($inputData['distributor_id']) && $inputData['distributor_id'] 
        && isset($dids) && in_array( $inputData['distributor_id'] , $dids )
        ){
            $manage_status = 1;
        }

        $result = array(
            'manage_status'=>$manage_status,
            'operatorInfo' => $operatorInfo,
            'inputData'=>$inputData,
            'authInfo'=>$authInfo,
            'manage_store_ids'=>$dids ?? [],
             );

        $data= $result;
        $result['data'] = $data;
        return $this->response->array($result);
     }

    /**
     * @SWG\post(
     *     path="/h5app/wxapp/salespersonadmin/addsalesperson",
     *     summary="店铺业务员管理-增加业务员",
     */
     public function addsalesperson(Request $request){
        $authInfo = $request->get('auth');
        $params = $request->all();

        $rules = [
            'mobile' => ['required', '请填写手机号'],
            'name' => ['required', '请填写导购员姓名'],
            'is_valid' => ['in:true,false', '请选择是否开启'],
            'distributor_id' => ['required', '请选择导购员所属店铺'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        // $distributorIds = $request->get('distributor_id');
        // if (!is_array($distributorIds)) {
        //     $distributorIds = json_decode($distributorIds, true);
        // }
        $salespersonService = new SalespersonService();
        $companyId = $authInfo['company_id'];


        if ($params['mobile']) {
            $memberService = new MemberService();
            $userId = $memberService->getUserIdByMobile($params['mobile'], $companyId);
            if (!$userId) {
                throw new ResourceException('当前手机号还不是会员');
            }else{
                $params['user_id'] = $userId;
            }
        }
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":params:". json_encode($params));

        $distributorService = new DistributorService();
        $distributorInfo = $distributorService->getInfoSimple(['distributor_id' => $params['distributor_id'] ]);
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":distributorInfo:". json_encode($distributorInfo));
        
        if(!$distributorInfo['is_open_salesman']){
            throw new ResourceException('店铺未开启业务员配置！');
        }

        $data = [
            'mobile' => trim($params['mobile']),
            'name' => trim($params['name']),
            'role' => '',
            //'distributor_id' => (array)$distributorIds,
            'distributor_id' => 0,// (array)$distributorIds,
            'company_id' => $companyId,
            'salesperson_type' => 'shopping_guide',
            'is_valid' => $params['is_valid'] ?? 'false',
            'number' => '',
            'employee_status' => 1,
            'user_id' => $userId ?? 0,
            'shop_id' => $params['distributor_id'] ?? 0,
        ];

        $mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'user_id' => $userId ,  'shop_id'=>$params['distributor_id'] ?? 0,  'salesperson_type' => $data['salesperson_type']]);
        if ($mobileFindData && $data['salesperson_type'] == 'shopping_guide') {
            throw new ResourceException('当前会员已添加！');
        }

        $mobile = fixedencrypt($data['mobile']);
        //验证手机号
        //$mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'mobile' => $data['mobile'], 'salesperson_type' => $data['salesperson_type']]);
        $mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'mobile' => $mobile ,  'shop_id'=>$params['distributor_id'] ?? 0,  'salesperson_type' => $data['salesperson_type']]);
        if ($mobileFindData && $data['salesperson_type'] == 'shopping_guide') {
            throw new ResourceException('当前手机号已绑定');
        }

        $result = $salespersonService->createSalesperson($data);

        $promoterService = new PromoterService();
        $promoterService->updateShopStatusSalesperson($companyId, $userId);

        $result = array(
            'status'=>1,
            'code' => 0,
            'data' => $data,
            'inoutData'=>$params,
             );
        return $this->response->array($result);

     }  
     
    /**
     * @SWG\post(
     *     path="/h5app/wxapp/salespersonadmin/updatesalesperson",
     *     summary="店铺业务员管理-更新业务员",
     */
    public function updatesalesperson(Request $request){
        $authInfo = $request->get('auth');
        $inputData = $request->all();

        $salespersonService = new SalespersonService();
        $companyId = $authInfo['company_id'];
        if(!isset($inputData['salesperson_id']) || !$inputData['salesperson_id'] ){
            throw new ResourceException('参数错误');
        }
        if($inputData['is_valid'] == false || $inputData['is_valid'] == 'false'){
            $is_valid = 'false';
        }else{
            $is_valid = 'true';
        }

        $data = array(
            'name'    => $inputData['name'],
            'is_valid'=> $is_valid,
            'salesperson_type' => 'shopping_guide',
        );
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-inputData:". json_encode($inputData));
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($data));

        $result = $salespersonService->updateSalesperson($companyId,$inputData['salesperson_id'],$data);

        $result = array(
            'status'=>1,
            'code' => 0,
            'data' => $result,
            'inoutData'=>$inputData,
             );
        return $this->response->array($result);
    }   

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salespersonadmin/salespersonlist",
     *     summary="店铺业务员管理-查询业务员列表",
     */
    public function salespersonlist(Request $request){
        $authInfo = $request->get('auth');
        $inputData = $request->all();

        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter = array();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['shop_id']    = $inputData['distributor_id'];
        if(isset($inputData['mobile']) 
        && $inputData['mobile'] ){
            $filter['mobile'] = $inputData['mobile'];
        }

        if(isset($inputData['username']) 
        && $inputData['username'] ){
            $filter['name'] = $inputData['username'];
        }
        app('log')->info(':salesperson:'.__FUNCTION__.__LINE__.':filter:'.json_encode($filter));

        $listdata = $salespersonService->getSalespersonList($filter, $inputData['page'] ?? 1, $inputData['pageSize'] ?? 100);
        
        $dIds = array_column($listdata['list'],'shop_id');
        $salesperson_arr = array_column($listdata['list'],null,'shop_id');
        // $salesperson = $listdata['list'] ?? [];
        foreach($listdata['list'] as $kSales => &$vSales){
            app('log')->info(':salesperson:'.__FUNCTION__.__LINE__.':is_valid:'.$vSales['user_id'].':'.json_encode($vSales['is_valid']));

            if($vSales['is_valid'] === 'true'){
                $vSales['is_valid'] = true;
            }
            if($vSales['is_valid'] === 'false'){
                $vSales['is_valid'] = false;
            }
        }   

        if(!$dIds){
            return $this->response->array(array());
        }
        $distributorService = new DistributorService();
        $filter_store =  array(['distributor_id' => $dIds]);
        // if($name)   $filter_store['name|like']   = $name;
        // if($mobile) $filter_store['mobile|like'] = $mobile;
        $listShop = $distributorService->lists($filter_store, [], -1, 0);
        foreach($listShop['list'] as $kShop => &$vShop){
            $vShop['user_id'] = $authInfo['user_id'];
            $vShop['salesperson'] = $salesperson_arr[$vShop['distributor_id']] ?? [];
        }



        $result = array(
            'status'=>1,
            'code' => 0,
            'data' => $listdata,
            'inputData'=>$inputData,
             );
        return $this->response->array($result);

    }   
    
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salespersonadmin/salespersoninfo",
     *     summary="店铺业务员管理-查询业务员列表",
     */
    public function salespersoninfo(Request $request){
        $authInfo = $request->get('auth');
        $inputData = $request->all();

        $listdata = ['list' => []];
        $salespersonService = new SalespersonService();
        $filter = array();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['shop_id']    = $inputData['distributor_id'];
        if(isset($inputData['id']) && $inputData['id']){
            $filter['id']    = $inputData['id'];
        }
        if(isset($inputData['user_id']) && $inputData['user_id']){
            $filter['user_id']    = $inputData['user_id'];
        }

        $data = $salespersonService->getInfo($filter);
        if($data['is_valid'] === 'true'){
            $data['is_valid'] = true;
        }
        if($data['is_valid'] === 'false'){
            $data['is_valid'] = false;
        }
        // getSalespersonList($filter, $inputData['page'] ?? 1, $inputData['pageSize'] ?? 100);
        
        // $dIds = array_column($listdata['list'],'shop_id');
        // $salesperson_arr = array_column($listdata['list'],null,'shop_id');
        // // $salesperson = $listdata['list'] ?? [];
        // foreach($salesperson_arr as $kSales => &$vSales){
        //     if($vSales['is_valid'] === 'true'){
        //         $vSales['is_valid'] = true;
        //     }
        //     if($vSales['is_valid'] === 'false'){
        //         $vSales['is_valid'] = false;
        //     }
        // }   

        // if(!$dIds){
        //     return $this->response->array(array());
        // }
        // $distributorService = new DistributorService();
        // $filter_store =  array(['distributor_id' => $dIds]);
        // // if($name)   $filter_store['name|like']   = $name;
        // // if($mobile) $filter_store['mobile|like'] = $mobile;
        // $listShop = $distributorService->lists($filter_store, [], -1, 0);
        // foreach($listShop['list'] as $kShop => &$vShop){
        //     $vShop['user_id'] = $authInfo['user_id'];
        //     $vShop['salesperson'] = $salesperson_arr[$vShop['distributor_id']] ?? [];
        // }



        $result = array(
            'status'=>1,
            'code' => 0,
            'data' => $data,
            'inputData'=>$inputData,
             );
        return $this->response->array($result);

    }   
    
    

    
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/salespersonadmin/brokagestaticlist",
     *     summary="店铺业务员管理-查询业绩统计",
     */
    public function brokagestaticlist(Request $request){
        $authInfo = $request->get('auth');
        $inputData = $request->all();
        // $filter = array();
        $inputData['company_id'] = $authInfo['company_id'];
        // $filter['distributor_id']    = $inputData['distributor_id'];

        //

        $filter_salesperson = null;
        if(isset($inputData['username']) && $inputData['username']){
            $filter_salesperson = array(
                'company_id' => $authInfo['company_id'],
                'shop_id' => $inputData['distributor_id'],
                'name' => $inputData['username'],
            );
        }
        if(isset($inputData['mobile']) && $inputData['mobile']){
            $filter_salesperson = array(
                'company_id' => $authInfo['company_id'],
                'shop_id' => $inputData['distributor_id'],
                'mobile' => $inputData['mobile'],
            ); 
            unset($inputData['mobile']);
        }

        if($filter_salesperson){
            app('log')->info(':salespersonSearch:'.__FUNCTION__.__LINE__.':filter_salesperson:'.json_encode($filter_salesperson));
            $salespersonService = new SalespersonService();
            $salespersonSearch = $salespersonService->salesperson->getInfo($filter_salesperson);
            app('log')->info(':salespersonSearch:'.__FUNCTION__.__LINE__.':salespersonSearch:'.json_encode($salespersonSearch));
            if(!isset($salespersonSearch['user_id']) ){
                throw new ResourceException('查询数据不存在');
            }
        }

        if(isset($salespersonSearch['user_id']) 
        && $salespersonSearch['user_id']){
            $inputData['user_id'] =  $salespersonSearch['user_id'];
        } 
        app('log')->info(':salespersonSearch:'.__FUNCTION__.__LINE__.':inputData:'.json_encode($inputData));

        $brokerageService = new BrokerageService();
        $countDataShopList = $brokerageService->getSalesmanBrokerageCountList($inputData, $inputData['pageSize'] ?? 1000, $inputData['page'] ?? 1 );

        $result = array(
            'status'=>1,
            'code' => 0,
            'data' => $countDataShopList,
            'inputData'=>$inputData,
            'authInfo'=>$authInfo,
             );
        return $this->response->array($result);

    }   
    
    

}
