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

namespace SalespersonBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;

use SalespersonBundle\Services\SalespersonService;

use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Illuminate\Http\Response;

use MembersBundle\Services\MemberService;
use PopularizeBundle\Services\PromoterService;
use DistributionBundle\Services\DistributorService;

class SalespersonController extends BaseController
{
    /**
      * @SWG\Post(
      *     path="/distributor/salesman",
      *     summary="新增店铺导购员",
      *     tags={"导购"},
      *     description="新增店铺导购员",
      *     operationId="addSalesman",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
      *     @SWG\Parameter( name="salesman_name", in="query", description="姓名", required=true, type="string"),
      *     @SWG\Parameter( name="role", in="query", description="角色ID", required=true, type="string"),
      *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              ref="#/definitions/SalesManInfo"
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function addSalesman(request $request)
    {
        // Powered by ShopEx EcShopX
        $params = $request->all('mobile', 'salesman_name', 'distributor_id');

        $rules = [
            'mobile' => ['required', '请填写手机号'],
            'salesman_name' => ['required', '请填写导购员姓名'],
            'distributor_id' => ['required', '请选择导购员所属店铺'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $distributorIds = $request->get('distributor_id');
        if (!is_array($distributorIds)) {
            $distributorIds = json_decode($distributorIds, true);
        }
        $salespersonService = new SalespersonService();
        $companyId = app('auth')->user()->get('company_id');


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
            'mobile' => trim($request->input('mobile')),
            'name' => trim($request->input('salesman_name')),
            'role' => intval($request->input('role')),
            //'distributor_id' => (array)$distributorIds,
            'distributor_id' => 0,// (array)$distributorIds,
            'company_id' => $companyId,
            'salesperson_type' => 'shopping_guide',
            'number' => '',
            'employee_status' => $request->input('employee_status', 1),
            'user_id' => $userId ?? 0,
            'shop_id' => $params['distributor_id'] ?? 0,
        ];

        $mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'user_id' => $userId ,  'shop_id'=>$params['distributor_id'] ?? 0,  'salesperson_type' => $data['salesperson_type']]);
        if ($mobileFindData && $data['salesperson_type'] == 'shopping_guide') {
            throw new StoreResourceFailedException('当前会员已添加！');
        }

        $mobile = fixedencrypt($data['mobile']);
        //验证手机号
        //$mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'mobile' => $data['mobile'], 'salesperson_type' => $data['salesperson_type']]);
        $mobileFindData = $salespersonService->findOneBy(['company_id' => $data['company_id'], 'mobile' => $mobile ,  'shop_id'=>$params['distributor_id'] ?? 0,  'salesperson_type' => $data['salesperson_type']]);
        if ($mobileFindData && $data['salesperson_type'] == 'shopping_guide') {
            throw new StoreResourceFailedException('当前手机号已绑定');
        }

        $result = $salespersonService->createSalesperson($data);

        $promoterService = new PromoterService();
        $promoterService->updateShopStatusSalesperson($companyId, $userId);
        return $this->response->array($result);
    }

    /**
      * @SWG\Put(
      *     path="/distributor/salesman/{salesmanId}",
      *     summary="更新店铺导购员",
      *     tags={"导购"},
      *     description="更新店铺导购员",
      *     operationId="updateSalesman",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="salesmanId", in="path", description="导购员ID", required=true, type="string"),
      *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
      *     @SWG\Parameter( name="salesman_name", in="query", description="姓名", required=false, type="string"),
      *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="string"),
      *     @SWG\Parameter( name="is_valid", in="query", description="是否有效", required=false, type="string"),
      *     @SWG\Parameter( name="role", in="query", description="角色ID", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="更新结果"),
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function updateSalesman($salesmanId, request $request)
    {
        $params = $request->all('mobile', 'salesman_name', 'distributor_id', 'is_valid', 'role');
        $salespersonService = new SalespersonService();
        $data = [];
        if (trim($request->input('mobile'))) {
            $data['mobile'] = trim($request->input('mobile'));
        }
        if (trim($request->input('salesman_name'))) {
            $data['name'] = trim($request->input('salesman_name'));
        }
        $distributorIds = $request->get('distributor_id', 0);
        if (!is_array($distributorIds)) {
            $distributorIds = json_decode($distributorIds, true);
        }
        if ($distributorIds) {
            $data['distributor_id'] = (array)$distributorIds;
        }
        if (trim($request->input('is_valid'))) {
            $data['is_valid'] = trim($request->input('is_valid'));
        }
        if (intval($request->input('role'))) {
            $data['role'] = intval($request->input('role'));
        }
        $data['salesperson_type'] = 'shopping_guide';
        if ($data) {
            $companyId = app('auth')->user()->get('company_id');
            $mobileFindData = $salespersonService->findOneBy(['company_id' => $companyId, 'salesperson_id' => $salesmanId, 'salesperson_type' => $data['salesperson_type']]);
            if (!$mobileFindData) {
                throw new UpdateResourceFailedException('更新的人员不存在');
            }
            if ($data['mobile'] ?? 0) {
                $mobileFindData = $salespersonService->findOneBy(['company_id' => $companyId,'shop_id'=> $mobileFindData->getShopId(), 'mobile' => $data['mobile'], 'salesperson_type' => $data['salesperson_type']]);
            }
            //如果通过更新的手机号查询到人员，并且不是当前账号更新
            if ($mobileFindData && $mobileFindData->getSalespersonId() != $salesmanId) {
                //如果通过更新的手机号查询到人员
                if ($data['salesperson_type'] == 'shopping_guide') {
                    throw new StoreResourceFailedException('当前手机号已经已绑定'); 
                }
            }
            $result = $salespersonService->updateSalesperson($companyId, $salesmanId, $data);
        }
        return $this->response->array(['status' => true, 'mobileFindData'=> $mobileFindData]);
    }

    /**
      * @SWG\Put(
      *     path="/distributor/salesman/role/{salesmanId}",
      *     summary="更新店铺导购员权限",
      *     tags={"导购"},
      *     description="更新店铺导购员权限",
      *     operationId="updateSalesmanRole",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="salesmanId", in="path", description="导购员ID", required=true, type="string"),
      *     @SWG\Parameter( name="role", in="formData", description="角色id", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="更新结果"),
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function updateSalesmanRole($salesmanId, request $request)
    {
        $salespersonService = new SalespersonService();
        $role = $request->get('role', 0);
        $companyId = app('auth')->user()->get('company_id');
        $result = $salespersonService->updateSalespersonRole($companyId, $salesmanId, $role);
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\get(
      *     path="/distributor/salesman/role",
      *     summary="获取店铺导购员角色权限",
      *     tags={"导购"},
      *     description="获取店铺导购员角色权限",
      *     operationId="getSalesmanRoleList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="1", type="object",
     *                          @SWG\Property( property="key", type="string", example="1", description="权限ID"),
     *                          @SWG\Property( property="name", type="string", example="发货管理", description="权限名称"),
     *                  ),
     *                  @SWG\Property( property="2", type="object",
     *                          @SWG\Property( property="key", type="string", example="2", description="权限ID"),
     *                          @SWG\Property( property="name", type="string", example="导购数据", description="权限名称"),
     *                  ),
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function getSalesmanRoleList(request $request)
    {
        $salespersonService = new SalespersonService();
        $result = $salespersonService->salespersonRole();
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/distributor/salesmans",
      *     summary="获取店铺导购员列表",
      *     tags={"导购"},
      *     description="获取店铺导购员列表",
      *     operationId="getSalesmanList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
      *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
      *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=false, type="string"),
      *     @SWG\Parameter( name="salesman_name", in="query", description="姓名", required=false, type="string"),
      *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="string"),
      *     @SWG\Parameter( name="is_valid", in="query", description="是否有效", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="72", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object", ref="#/definitions/SalesPersonInfo" ),
     *                  ),
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function getSalesmanList(request $request)
    {
        $salespersonService = new SalespersonService();
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->all('distributor_id', 'salesman_name', 'mobile', 'is_valid', 'page', 'pageSize');

        $merchantId = app('auth')->user()->get('merchant_id',0);
        $operatorType = app('auth')->user()->get('operator_type','');

        $filter = [];
        $filter['company_id'] = $companyId;

        if ($operatorType == 'merchant') {
            $filter_distributor = array();
            $filter_distributor['company_id'] = $companyId; // 商户端只能获取商户的店铺
            $filter_distributor['merchant_id'] = $merchantId; // 商户端只能获取商户的店铺
            $distributorService = new DistributorService();
            $distributorList =  $distributorService->lists($filter_distributor,['created' => 'desc'],10000,1,false,"distributor_id");
            $shopIds = array_column($distributorList['list'],'distributor_id');
            $filter['shop_id'] = $shopIds;

        }

        if (trim($request->get('distributor_id'))) {
            //$filter['distributor_id'] = trim($request->get('distributor_id'));
            $filter['shop_id'] = trim($request->get('distributor_id'));
        }
        if (trim($request->input('salesman_name'))) {
            $filter['name|contains'] = trim($request->input('salesman_name'));
        }
        if (trim($request->input('mobile'))) {
            $filter['mobile|contains'] = trim($request->input('mobile'));
        }

        if (trim($request->input('is_valid'))) {
            $filter['is_valid'] = trim($request->input('is_valid'));
        } else {
            $filter['is_valid|neq'] = 'delete';
        }
        $filter['salesperson_type'] = 'shopping_guide';

        $list = $salespersonService->getSalespersonList($filter, ['created_time' => 'DESC'], trim($request->input('pageSize')), trim($request->input('page')));
        $list['filter'] = $filter;
        $list['shopIds'] = $shopIds ?? [];
        return $this->response->array($list);
    }

    /**
      * @SWG\Get(
      *     path="/distributor/salemanCustomerComplaints",
      *     summary="获取店铺导购员客诉列表",
      *     tags={"导购"},
      *     description="获取店铺导购员客诉列表",
      *     operationId="getSalemanCustomerComplaintsList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
      *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
      *     @SWG\Parameter( name="user_name", in="query", description="用户名", required=false, type="string"),
      *     @SWG\Parameter( name="user_mobile", in="query", description="手机号", required=false, type="string"),
      *     @SWG\Parameter( name="saleman_name", in="query", description="导购员姓名", required=false, type="string"),
      *     @SWG\Parameter( name="saleman_mobile", in="query", description="导购员手机号", required=false, type="string"),
      *     @SWG\Parameter( name="reply_status", in="query", description="回复状态", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
      *          @SWG\Property( property="data", type="object",
      *                  @SWG\Property( property="total_count", type="string", example="8", description="总数"),
      *                  @SWG\Property( property="list", type="array",
      *                      @SWG\Items( type="object", ref="#/definitions/SalesPersonComplaint" ),
      *                  ),
      *          ),
      *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
      * )
      */
    public function getSalemanCustomerComplaintsList(Request $request)
    {
        $salespersonService = new SalespersonService();
        $companyId = app('auth')->user()->get('company_id');
        $inputData = $request->input();

        $params = $request->all('page', 'pageSize', 'user_name', 'user_mobile', 'saleman_name', 'saleman_mobile', 'reply_status');
        $filter = [];

        if (isset($inputData['user_name']) && !empty(trim($inputData['user_name']))) {
            $filter['user_name|contains'] = $inputData['user_name'];
        }
        if (isset($inputData['user_mobile']) && !empty(trim($inputData['user_mobile']))) {
            $filter['user_mobile|contains'] = $inputData['user_mobile'];
        }
        if (isset($inputData['saleman_name']) && !empty(trim($inputData['saleman_name']))) {
            $filter['saleman_name|contains'] = $inputData['saleman_name'];
        }
        if (isset($inputData['saleman_mobile']) && !empty(trim($inputData['saleman_mobile']))) {
            $filter['saleman_mobile|contains'] = $inputData['saleman_mobile'];
        }
        if (isset($inputData['reply_status']) && $inputData['reply_status'] !== "") {
            $filter['reply_status'] = $inputData['reply_status'];
        }

        $filter['company_id'] = $companyId;
        $list = $salespersonService->getSalemanCustomerComplaintsList($filter, [], trim($request->input('page')), trim($request->input('pageSize')));
        return $this->response->array($list);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/salemanCustomerComplaints",
     *     summary="回复店铺导购员客诉",
     *     tags={"导购"},
     *     description="回复店铺导购员客诉",
     *     operationId="replySalemanCustomerComplaints",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="reply_id", in="query", description="回复投诉的id", required=true, type="string"),
     *     @SWG\Parameter( name="reply_content", in="query", description="回复内容", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object", ref="#/definitions/SalesPersonComplaint" ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function replySalemanCustomerComplaints(Request $request)
    {
        $salespersonService = new SalespersonService();
        $params = $request -> all('reply_id', 'reply_content');
        $params = array_map("trim", $params);

        if (mb_strlen($params['reply_content']) > 255) {
            throw new ResourceException('回复内容不能超过255个字符');
        }

        $distributorIds = $request->get('distributorIds');
        $rules = [
            'reply_id' => ['required', '未知的回复对象'],
            'reply_content' => ['required', '请输入回复内容'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $now = time();
        $data = [
            'reply_content' => $params['reply_content'],
            'reply_status' => 1,
            'reply_time' => $now,
            'updated' => $now,
            'reply_operator_id' => app('auth')->user()->get('operator_id'),
            'reply_operator_name' => app('auth')->user()->get('username'),
            'reply_operator_mobile' => app('auth')->user()->get('mobile'),
        ];
        $filter = [];
        $filter['id'] = $params['reply_id'];
        $salespersonService = new SalespersonService();
        //获取已回复的内容
        $replyed = $salespersonService->salemanCustomerComplaints->getInfoById($filter['id']);
        if (!empty($replyed['reply_content'])) {
            $replyed_content = json_decode($replyed['reply_content'], true);
        } else {
            $replyed_content = [];
        }
        $tmp_data = [
            'reply_operator_id' => $data['reply_operator_id'],
            'reply_operator_name' => $data['reply_operator_name'],
            'reply_operator_mobile' => $data['reply_operator_mobile'],
            'reply_time' => $data['reply_time'],
            'reply_content' => $data['reply_content']
        ];
        array_push($replyed_content, $tmp_data);
        $data['reply_content'] = json_encode($replyed_content);

        $reply_result = $salespersonService->replySalemanCustomerComplaints($filter, $data);

        return $reply_result;
    }

    /**
     * @SWG\Definition(
     *     definition="SalesManInfo",
     *     type="object",
     *     @SWG\Property( property="salesperson_id", type="string", example="119", description="门店人员ID"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *     @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *     @SWG\Property( property="shop_name", type="string", example="null", description="门店名称"),
     *     @SWG\Property( property="name", type="string", example="test", description="姓名"),
     *     @SWG\Property( property="mobile", type="string", example="13812345679", description="手机号"),
     *     @SWG\Property( property="salesperson_type", type="string", example="admin", description="人员类型 admin: 管理员; verification_clerk:核销员; shopping_guide:导购员"),
     *     @SWG\Property( property="created_time", type="string", example="1611651291", description="创建时间"),
     *     @SWG\Property( property="user_id", type="string", example="0", description="关联会员id"),
     *     @SWG\Property( property="child_count", type="string", example="0", description="导购员引入的会员数"),
     *     @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *     @SWG\Property( property="number", type="string", example="null", description="导购员编号"),
     *     @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *     @SWG\Property( property="work_userid", type="string", example="null", description="企业微信userid"),
     *     @SWG\Property( property="avatar", type="string", example="null", description="企业微信头像"),
     *     @SWG\Property( property="work_configid", type="string", example="null", description="企业微信userid"),
     *     @SWG\Property( property="work_qrcode_configid", type="string", example="null", description="企业微信userid"),
     *     @SWG\Property( property="role", type="string", example="6", description="角色ID"),
     *     @SWG\Property( property="created", type="string", example="1611651291", description="created"),
     *     @SWG\Property( property="updated", type="string", example="1611651291", description="updated"),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="SalesPersonComplaint",
     *     type="object",
     *     @SWG\Property( property="id", type="string", example="6", description="投诉ID"),
     *     @SWG\Property( property="user_id", type="string", example="20337", description="会员ID"),
     *     @SWG\Property( property="company_id", type="string", example="1", description="商家ID"),
     *     @SWG\Property( property="user_name", type="string", example="会员", description="会员姓名"),
     *     @SWG\Property( property="user_mobile", type="string", example="13800000000", description="会员手机号"),
     *     @SWG\Property( property="saleman_id", type="string", example="94", description="导购员ID"),
     *     @SWG\Property( property="saleman_name", type="string", example="导购员", description="导购员姓名"),
     *     @SWG\Property( property="saleman_avatar", type="string", example="http://wework.qpic.cn/bizmail/...", description="导购员头像URL"),
     *     @SWG\Property( property="saleman_mobile", type="string", example="13800000000", description="导购员手机号"),
     *     @SWG\Property( property="distributor_id", type="string", example="33", description="店铺ID"),
     *     @SWG\Property( property="saleman_distribution_name", type="string", example="视力康眼镜(中兴路店)", description="店铺名称"),
     *     @SWG\Property( property="complaints_content", type="string", example="投诉内容", description="投诉内容"),
     *     @SWG\Property( property="complaints_images", type="string", example="https://bbctest.aixue7.com/image/...", description="投诉图片地址"),
     *     @SWG\Property( property="reply_status", type="string", example="0", description="回复状态(0,1)"),
     *     @SWG\Property( property="reply_content", type="string", example="回复内容(json编码)", description="回复内容(json编码)"),
     *     @SWG\Property( property="reply_time", type="string", example="1611627758", description="回复时间"),
     *     @SWG\Property( property="reply_operator_id", type="string", example="1", description="回复人ID"),
     *     @SWG\Property( property="reply_operator_name", type="string", example="店员", description="回复人姓名"),
     *     @SWG\Property( property="reply_operator_mobile", type="string", example="13800000000", description="回复人手机号"),
     *     @SWG\Property( property="created", type="string", example="1611572005", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1611572005", description="更新时间"),
     * )
     */
}
