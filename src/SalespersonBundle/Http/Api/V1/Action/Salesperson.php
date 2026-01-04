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
use SalespersonBundle\Services\SignService;


use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;

class Salesperson extends BaseController
{
    private $salespersonService;

    /**
     * Shops constructor.
     * @param SalespersonService  $salespersonService
     */
    public function __construct(SalespersonService $salespersonService)
    {
        $this->salespersonService = new $salespersonService();
    }

    /**
     * @SWG\Post(
     *     path="/shops/salesperson",
     *     summary="添加门店人员",
     *     tags={"导购"},
     *     description="添加门店人员",
     *     operationId="createSalesperson",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID(门店ID和店铺ID二选一)", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID(门店ID和店铺ID二选一)", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="人员姓名", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_type", in="query", description="人员类型(verification_clerk核销员)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="添加结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function createSalesperson(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        $data = [
            'company_id' => $authInfo['company_id'],
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'salesperson_type' => $request->input('salesperson_type', 'admin'),
        ];
        $shop_id = $request->input('shop_id');
        $distributor_id = $request->input('distributor_id');

        $shopIds = (isset($authInfo['shop_ids']) && $authInfo['shop_ids']) ? $authInfo['shop_ids'] : [] ;
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
            foreach ((array)$shop_id as $id) {
                if (!in_array($id, $shopIds)) {
                    throw new ResourceException("门店权限不足");
                }
            }
        }

        $distributorIds = (isset($authInfo['distributor_ids']) && $authInfo['distributor_ids']) ? $authInfo['distributor_ids'] : [] ;
        if ($distributorIds) {
            $distributorIds = array_column($distributorIds, 'shop_id');
            foreach ((array)$distributor_id as $id) {
                if (!in_array($id, $distributorIds)) {
                    throw new ResourceException("店铺权限不足");
                }
            }
        }

        $data['shop_id'] = (array)$shop_id;
        $data['distributor_id'] = (array)$distributor_id;

        if ($data['shop_id'] && $data['distributor_id']) {
            throw new ResourceException("不可同时选择门店和店铺");
        }

        if (!$data['mobile'] || !$data['name'] || (!$data['shop_id'] && !$data['distributor_id'])) {
            throw new ResourceException("请填写必填信息");
        }

        $this->salespersonService->createSalesperson($data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/shops/salesperson",
     *     summary="获取所有门店人员列表",
     *     tags={"导购"},
     *     description="获取所有门店人员列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_type", in="query", description="人员类型(verification_clerk核销员)", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号码", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的参数是0，那么按默认值20处理", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object", ref="#/definitions/SalesPersonInfo" ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function lists(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];

        if ($request->input('shop_id')) {
            $filter['shop_id'] = $request->input('shop_id');
        } else {
            $shopIds = (isset($authInfo['shop_ids']) && $authInfo['shop_ids']) ? $authInfo['shop_ids'] : [] ;
            if ($shopIds) {
                $filter['shop_id'] = array_column($shopIds, 'shop_id');
            }
        }

        if ($request->input('distributor_id')) {
            $filter['distributor_id'] = $request->input('distributor_id');
        } else {
            $distributorIds = (isset($authInfo['distributor_ids']) && $authInfo['distributor_ids']) ? $authInfo['distributor_ids'] : [] ;
            if ($distributorIds) {
                $filter['distributor_id'] = array_column($shopIds, 'distributor_id');
            }
        }

        if ($request->input('mobile')) {
            $filter['mobile'] = $request->input('mobile');
        }

        if ($request->input('salesperson_type')) {
            $filter['salesperson_type'] = $request->input('salesperson_type');
        }

        $orderBy = ['created_time' => 'DESC'];
        $pageSize = $request->input('pageSize', 20);
        $page = $request->input('page', 1);
        $list = $this->salespersonService->getSalespersonList($filter, $orderBy, $pageSize, $page);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $list['datapass_block'] = $datapassBlock;
        if ($datapassBlock) {
            foreach ($list['list'] as $key => $value) {
                $list['list'][$key]['name'] = data_masking('truename', (string) $value['name']);
                $list['list'][$key]['mobile'] = data_masking('mobile', (string) $value['mobile']);
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Delete(
     *     path="/shops/salesperson/{salesperson_id}",
     *     summary="删除门店人员",
     *     tags={"导购"},
     *     description="删除门店人员",
     *     operationId="deleteSalesperson",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="path", description="人员ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="删除结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function deleteSalesperson($salespersonId)
    {
        $authInfo = app('auth')->user()->get();
        $this->salespersonService->deleteSalesperson($authInfo['company_id'], $salespersonId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/shops/salesperson/{salesperson_id}",
     *     summary="更新门店人员",
     *     tags={"导购"},
     *     description="更新门店人员",
     *     operationId="updateSalesperson",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="path", description="人员ID", required=true, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店ID(门店和店铺二选一)", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID(门店和店铺二选一)", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="人员姓名", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_type", in="query", description="人员类型(verification_clerk核销员)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="更新结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function updateSalesperson(Request $request, $salespersonId)
    {
        $authInfo = app('auth')->user()->get();

        $data = [
            'company_id' => $authInfo['company_id'],
            'name' => $request->input('name'),
            'mobile' => $request->input('mobile'),
            'salesperson_type' => $request->input('salesperson_type', 'admin'),
        ];

        $shop_id = $request->input('shop_id');
        $distributor_id = $request->input('distributor_id');

        $shopIds = (isset($authInfo['shop_ids']) && $authInfo['shop_ids']) ? $authInfo['shop_ids'] : [] ;
        if ($shopIds) {
            $shopIds = array_column($shopIds, 'shop_id');
            foreach ((array)$shop_id as $id) {
                if (!in_array($id, $shopIds)) {
                    throw new ResourceException("门店权限不足");
                }
            }
        }

        $distributorIds = (isset($authInfo['distributor_ids']) && $authInfo['distributor_ids']) ? $authInfo['distributor_ids'] : [] ;
        if ($distributorIds) {
            $distributorIds = array_column($distributorIds, 'shop_id');
            foreach ((array)$distributor_id as $id) {
                if (!in_array($id, $distributorIds)) {
                    throw new ResourceException("店铺权限不足");
                }
            }
        }

        $data['shop_id'] = (array)$shop_id;
        $data['distributor_id'] = (array)$distributor_id;

        if ($data['shop_id'] && $data['distributor_id']) {
            throw new ResourceException("不可同时选择门店和店铺");
        }

        if (!$salespersonId || !$data['mobile'] || !$data['name'] || (!$data['shop_id'] && !$data['distributor_id'])) {
            throw new ResourceException("请填写必填信息");
        }

        $this->salespersonService->updateSalesperson($authInfo['company_id'], $salespersonId, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/shops/saleperson/shoplist",
     *     summary="获取管理员管理的门店数据",
     *     tags={"导购"},
     *     description="获取管理员管理的门店数据",
     *     operationId="getRelShopList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="query", description="管理员ID", required=true, type="string"),
     *     @SWG\Parameter( name="store_type", in="query", description="店铺类型", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="shop_id", type="string", example="1", description="门店ID"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="2", description="管理员ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="store_type", type="string", example="shop", description="门店类型"),
     *                          @SWG\Property( property="address", type="string", example="上海市徐汇区宜山路700号(近桂林路)", description="门店地址"),
     *                          @SWG\Property( property="store_name", type="string", example="【门店】普天信息产业园", description="门店名称"),
     *                          @SWG\Property( property="distributor_id", type="string", example="未知", description="店铺ID"),
     *                          @SWG\Property( property="shop_logo", type="string", example="http://bbctest.aixue7.com/image/...", description="门店Logo图片地址"),
     *                          @SWG\Property( property="hour", type="string", example="未知", description="营业时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getRelShopList(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['salesperson_id'] = $request->input('salesperson_id');
        $filter['store_type'] = $request->input('store_type', 'shop');
        $filter['company_id'] = $authInfo['company_id'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 500);
        $result = $this->salespersonService->getSalespersonRelShopdata($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/shops/saleperson/getinfo",
     *     summary="获取门店人员详细信息",
     *     tags={"导购"},
     *     description="获取门店人员详细信息",
     *     operationId="getSalespersonInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="query", description="人员ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesperson_id", type="string", example="2", description="人员ID"),
     *                  @SWG\Property( property="name", type="string", example="candy", description="姓名"),
     *                  @SWG\Property( property="mobile", type="string", example="15300532463", description="手机号"),
     *                  @SWG\Property( property="created_time", type="string", example="1563778936", description="创建时间"),
     *                  @SWG\Property( property="salesperson_type", type="string", example="admin", description="人员类型"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                  @SWG\Property( property="user_id", type="string", example="0", description="用户ID"),
     *                  @SWG\Property( property="child_count", type="string", example="0", description="导购员引入的会员数"),
     *                  @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     *                  @SWG\Property( property="shop_id", type="string", example="null", description="门店id"),
     *                  @SWG\Property( property="shop_name", type="string", example="null", description="门店名称"),
     *                  @SWG\Property( property="number", type="string", example="null", description="导购员编号"),
     *                  @SWG\Property( property="friend_count", type="string", example="0", description="导购员会员好友数"),
     *                  @SWG\Property( property="avatar", type="string", example="null", description="企业微信头像"),
     *                  @SWG\Property( property="work_userid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="work_configid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="work_qrcode_configid", type="string", example="null", description="企业微信userid"),
     *                  @SWG\Property( property="role", type="string", example="null", description="导购权限集合"),
     *                  @SWG\Property( property="created", type="string", example="0", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="0", description="更新时间"),
     *                  @SWG\Property( property="shop_ids", type="array",
     *                      @SWG\Items( type="string", example="1", description="门店id"),
     *                  ),
     *                  @SWG\Property( property="distributor_ids", type="array",
     *                      @SWG\Items( type="string", example="undefined", description="店铺ID"),
     *                  ),
     *                  @SWG\Property( property="store_type", type="string", example="shop", description="门店类型"),
     *                  @SWG\Property( property="shopList", type="array",
     *                      @SWG\Items( type="object", description="门店列表", ref="#/definitions/ShopInfo" ),
     *                  ),
     *                  @SWG\Property( property="distributor_id", type="string", example="false", description="店铺ID"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSalespersonInfo(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['salesperson_id'] = $request->input('salesperson_id');
        $infodata = $this->salespersonService->getSalespersonDetail($filter, true);
        if (isset($authInfo['shop_ids']) && $authInfo['shop_ids']) {
            $shopIds = array_column($authInfo['shop_ids'], 'shop_id');
        }
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $infodata['datapass_block'] = $datapassBlock;
        if ($datapassBlock) {
            // $infodata['mobile'] = data_masking('mobile', (string) $infodata['mobile']);
            // $infodata['name'] = data_masking('truename', (string) $infodata['name']);
        }
        return $this->response->array($infodata);
    }

    /**
     * @SWG\Get(
     *     path="/shops/saleperson/signlogs",
     *     summary="获取导购签到记录",
     *     tags={"导购"},
     *     description="获取导购签到记录",
     *     operationId="getSignlogs",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="导购姓名", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="导购手机号", required=true, type="string"),
     *     @SWG\Parameter( name="time_start_begin", in="query", description="开始时间(时间戳)", required=true, type="string"),
     *     @SWG\Parameter( name="time_start_end", in="query", description="结束时间(时间戳)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="21", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="2", description="导购ID"),
     *                          @SWG\Property( property="distributor_id", type="string", example="7", description="店铺ID"),
     *                          @SWG\Property( property="sign_type", type="string", example="签到", description="签到类型"),
     *                          @SWG\Property( property="created", type="string", example="2020-06-19 18:59:35", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2020-06-19 18:59:35", description="更新时间"),
     *                          @SWG\Property( property="name", type="string", example="candy", description="导购姓名"),
     *                          @SWG\Property( property="mobile", type="string", example="15300532463", description="导购手机号"),
     *                          @SWG\Property( property="shop_name", type="string", example="测试店铺", description="门店名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function getSignlogs(Request $request)
    {
        $signServicec = new SignService();
        $salespersonService = new SalespersonService();
        $companyId = app('auth')->user()->get('company_id');

        $filter = [];
        $filter['company_id'] = $companyId;
        if (trim($request->get('distributor_id'))) {
            $filter['distributor_id'] = trim($request->get('distributor_id'));
        }
        if (trim($request->input('name'))) {
            $filter['name|contains'] = trim($request->input('name'));
        }
        if (trim($request->input('mobile'))) {
            $filter['mobile'] = trim($request->input('mobile'));
        }
        if (trim($request->input('time_start_begin')) && trim($request->input('time_start_end'))) {
            $filter['created|gte'] = $request->input('time_start_begin');
            $filter['created|lte'] = $request->input('time_start_end');
        }
        $list = $signServicec->getSignlogs($filter, ['created' => 'DESC'], trim($request->input('pageSize')), trim($request->input('page')));
        return $this->response->array($list);
    }

    /**
     * @SWG\Definition(
     *     definition="SalesPersonInfo",
     *     type="object",
     *     description="门店人员信息",
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
     *     @SWG\Property( property="created", type="string", example="1611651291", description="created"),
     *     @SWG\Property( property="updated", type="string", example="1611651291", description="updated"),
     *     @SWG\Property( property="role", type="string", example="null", description="导购权限集合"),
     *     @SWG\Property( property="salesman_name", type="string", example="test", description="姓名(=name)"),
     *     @SWG\Property( property="salespersonId", type="string", example="119", description="门店人员ID"),
     *     @SWG\Property( property="companyId", type="string", example="1", description="公司ID"),
     *     @SWG\Property( property="createdTime", type="string", example="1611651291", description="创建时间"),
     *     @SWG\Property( property="role_name", type="string", example="无角色", description="角色权限"),
     *     @SWG\Property( property="salespersonType", type="string", example="admin", description="人员类型(=salesperson_type)"),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="ShopInfo",
     *     type="object",
     *     description="门店信息",
     *     @SWG\Property( property="wxShopId", type="string", example="2", description="WX自增id"),
     *     @SWG\Property( property="mapPoiId", type="string", example="16093503253274986652", description="从腾讯地图换取的位置点id，即search_map_poi接口返回的sosomap_poi_uid字段"),
     *     @SWG\Property( property="picList", type="array",
     *         @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/...", description="图片地址"),
     *     ),
     *     @SWG\Property( property="contractPhone", type="string", example="13572400496", description="联系电话"),
     *     @SWG\Property( property="hour", type="string", example="08:00 - 22:00", description="营业时间，格式11:11-12:12"),
     *     @SWG\Property( property="credential", type="string", example="null", description="经营资质证件号"),
     *     @SWG\Property( property="companyName", type="string", example="null", description="主体名字"),
     *     @SWG\Property( property="qualificationList", type="string", example="null", description="相关证明材料，临时素材mediaid，不复用公众号主体时，才需要填"),
     *     @SWG\Property( property="cardId", type="string", example="null", description="卡券id，如果不需要添加卡券，该参数可为空，目前仅开放支持会员卡、买单和刷卡支付券，不支持自定义code，需要先去公众平台卡券后台创建cardid"),
     *     @SWG\Property( property="status", type="string", example="5", description="审核状态，1：审核成功，2：审核中，3：审核失败，4：管理员拒绝, 5: 无需审核"),
     *     @SWG\Property( property="companyId", type="string", example="1", description="公司id"),
     *     @SWG\Property( property="created", type="string", example="1563519767", description="创建时间"),
     *     @SWG\Property( property="updated", type="string", example="1591254116", description="更新时间"),
     *     @SWG\Property( property="lng", type="string", example="108.873344", description="腾讯地图纬度"),
     *     @SWG\Property( property="lat", type="string", example="34.269329", description="腾讯地图经度"),
     *     @SWG\Property( property="address", type="string", example="陕西省西安市枣园街道24号", description="腾讯地图门店地址"),
     *     @SWG\Property( property="category", type="string", example="房产小区:产业园区", description="腾讯地图门店类目"),
     *     @SWG\Property( property="poiId", type="string", example="null", description="门店id"),
     *     @SWG\Property( property="errmsg", type="string", example="null", description="审核失败原因"),
     *     @SWG\Property( property="auditId", type="string", example="null", description="微信返回的审核id"),
     *     @SWG\Property( property="resourceId", type="string", example="1", description="资源包id"),
     *     @SWG\Property( property="expiredAt", type="string", example="1878362772", description="过期时间"),
     *     @SWG\Property( property="isDefault", type="string", example="false", description="是否是默认门店"),
     *     @SWG\Property( property="storeName", type="string", example="怡康医药智慧药店1", description="腾讯地图的门店名称"),
     *     @SWG\Property( property="addType", type="string", example="1", description="1,公众号主体；2,相关主体; 3,无主体"),
     *     @SWG\Property( property="country", type="string", example="null", description="非中国国家名称"),
     *     @SWG\Property( property="city", type="string", example="null", description="非中国门店所在城市"),
     *     @SWG\Property( property="isDomestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *     @SWG\Property( property="isDirectStore", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *     @SWG\Property( property="isOpen", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *     @SWG\Property( property="distributorId", type="string", example="0", description="门店所属店铺ID"),
     *     @SWG\Property( property="is_valid", type="string", example="true", description="是否有效"),
     * )
     */
}
