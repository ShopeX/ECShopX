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

namespace SelfserviceBundle\Http\Api\V1\Action;

use DistributionBundle\Services\DistributorService;
use EmployeePurchaseBundle\Services\EnterprisesService;
use EspierBundle\Services\AddressService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;
use KaquanBundle\Services\MemberCardService;
use KaquanBundle\Services\VipGradeService;
use OpenapiBundle\Services\Member\MemberCardGradeService;
use SelfserviceBundle\Services\FormTemplateService;
use SelfserviceBundle\Services\RegistrationActivityRelShopService;
use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;

class RegistrationActivityController extends Controller
{
    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new RegistrationActivityService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/create",
     *     summary="添加报名活动",
     *     tags={"报名"},
     *     description="添加报名活动",
     *     operationId="createData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="temp_id", in="query", description="问卷调查模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="join_limit", in="query", description="每个会员每个活动可参与次数，默认1", required=false, type="integer"),
     *     @SWG\Parameter( name="is_sms_notice", in="query", description="是否发送短信通知", required=false, type="boolean"),
     *     @SWG\Parameter( name="is_wxapp_notice", in="query", description="是否发送小程序模板通知", required=false, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */

    public function createData(Request $request)
    {
        $params = $request->all('temp_id', 'activity_name', 'start_time', 'end_time', 'join_limit', 'is_sms_notice', 'is_wxapp_notice', 'area', 'place', 'address', 'intro', 'show_fields', 'pics', 'gift_points', 'is_allow_duplicate', 'is_allow_cancel', 'is_offline_verify', 'is_need_check', 'is_white_list', 'enterprise_ids', 'group_no', 'member_level', 'distributor_ids', 'join_tips', 'submit_form_tips', 'content');
        $rules = [
            // 'temp_id' => ['required', trans('SelfserviceBundle.template_required')],
            'activity_name' => ['required', trans('SelfserviceBundle.activity_name_required')],
            'start_time' => ['required', trans('SelfserviceBundle.start_time_required')],
            'end_time' => ['required', trans('SelfserviceBundle.end_time_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['distributor_id'] = $request->input('distributor_id', 0);
        $params['temp_id'] = $request->input('temp_id', 0);
        $result = $this->service->saveData($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/selfhelp/registrationActivity/update",
     *     summary="编辑报名活动",
     *     tags={"报名"},
     *     description="编辑报名活动",
     *     operationId="updateData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Parameter( name="temp_id", in="query", description="问卷调查模板id", required=true, type="integer"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="join_limit", in="query", description="每个会员每个活动可参与次数，默认1", required=false, type="integer"),
     *     @SWG\Parameter( name="is_sms_notice", in="query", description="是否发送短信通知", required=false, type="boolean"),
     *     @SWG\Parameter( name="is_wxapp_notice", in="query", description="是否发送小程序模板通知", required=false, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function updateData(Request $request)
    {
        $params = $request->all('activity_id', 'temp_id', 'activity_name', 'start_time', 'end_time', 'join_limit', 'is_sms_notice', 'is_wxapp_notice', 'area', 'place', 'address', 'intro', 'show_fields', 'pics', 'gift_points', 'is_allow_duplicate', 'is_allow_cancel', 'is_offline_verify', 'is_need_check', 'is_white_list', 'enterprise_ids', 'group_no', 'member_level', 'distributor_ids', 'join_tips', 'submit_form_tips', 'content');
        $rules = [
            'activity_id' => ['required', trans('SelfserviceBundle.activity_id_required')],
            // 'temp_id' => ['required', trans('SelfserviceBundle.template_required')],
            'activity_name' => ['required', trans('SelfserviceBundle.activity_name_required')],
            'start_time' => ['required', trans('SelfserviceBundle.start_time_required')],
            'end_time' => ['required', trans('SelfserviceBundle.end_time_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        //筛选参数
        $filter['activity_id'] = $params['activity_id'];
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = $request->input('distributor_id', 0);
        //更新参数
        $params['company_id'] = $companyId;
        $params['distributor_id'] = $request->input('distributor_id', 0);        
        $params['temp_id'] = $request->input('temp_id', 0);        
        $result = $this->service->saveData($params, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/list",
     *     summary="报名活动列表",
     *     tags={"报名"},
     *     description="报名活动列表",
     *     operationId="getDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态", required=true, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否在有效期内", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="34", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="38", description="活动ID "),
     *                          @SWG\Property( property="temp_id", type="string", example="23", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="cesss", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1609430400", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1610726399", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="3", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="0", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="0", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1610443180", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1610443180", description="更新时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="start_date", type="string", example="2021-01-01 00:00:00", description="开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2021-01-15 23:59:59", description="结束时间"),
     *                          @SWG\Property( property="total_join_num", type="string", example="0", description="总参与人数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $result = [
            'total_count' => 0,
            'list' => [],
        ];
        $params = $request->all('page', 'pageSize', 'start_time', 'end_time', 'status', 'is_valid', 'distributor_id');
        $page = $request->get('page', 1);
        $size = $request->get('pageSize', $this->limit);
        $orderBy = ['activity_id' => 'DESC'];
        $filter = $this->_getFilter($request);
        if (!$filter) {
            return $this->response->array($result);
        }

        $fieldTitle = trim($request->get('field_title', ''));
        if ($fieldTitle) {
            $filter['activity_name|contains'] = $fieldTitle;
        }
        
        if ($params['distributor_id']) {
            $_filter = ['distributor_id' => $params['distributor_id']];
            $registrationActivityRelShopService = new RegistrationActivityRelShopService();
            $rs = $registrationActivityRelShopService->entityRepository->getLists($_filter, 'activity_id', 1, 100);
            if (!$rs) {
                return $this->response->array($result);
            }
            $filter['activity_id'] = array_column($rs, 'activity_id');
        }

        $result = $this->service->entityRepository->lists($filter, '*', $page, $size, $orderBy);
        if ($result['list'] ?? null) {

            $activity_ids = array_column($result['list'], 'activity_id');

            //获取绑定的店铺名称
            $activity_rel_shops = [];
            $distributor_names = [];
            $distributor_ids = [];
            $registrationActivityRelShopService = new RegistrationActivityRelShopService();
            $rsRelShops = $registrationActivityRelShopService->entityRepository->getLists(['activity_id' => $activity_ids]);
            if ($rsRelShops) {
                foreach ($rsRelShops as $v) {
                    $activity_rel_shops[$v['activity_id']][] = $v['distributor_id'];
                }
                $distributor_ids = array_column($rsRelShops, 'distributor_id');
                $distributor_ids = array_unique($distributor_ids);
                $distributor_ids = array_filter($distributor_ids);
            }
            if ($distributor_ids) {
                $distributorService = new DistributorService();
                $rsDistributor = $distributorService->entityRepository->getLists(['distributor_id' => $distributor_ids]);
                if ($rsDistributor) {
                    $distributor_names = array_column($rsDistributor, 'name', 'distributor_id');
                }
            }
            
            foreach ($result['list'] as $k => $v) {
                //获取活动状态
                if ($v['start_time'] > time()) {
                    $v['status'] = 'waiting';
                    $v['status_name'] = trans('SelfserviceBundle.status_waiting');
                } elseif ($v['end_time'] < time()) {
                    $v['status'] = 'end';
                    $v['status_name'] = trans('SelfserviceBundle.status_ended');
                }  else {
                    $v['status'] = 'ongoing';
                    $v['status_name'] = trans('SelfserviceBundle.status_ongoing');
                }
                //获取绑定的店铺名称
                $v['distributor_name'] = [];
                if (isset($activity_rel_shops[$v['activity_id']])) {
                    foreach ($activity_rel_shops[$v['activity_id']] as $distributor_id) {
                        if (isset($distributor_names[$distributor_id])) {
                            $v['distributor_name'][] = $distributor_names[$distributor_id];
                        }
                    }
                }
                $result['list'][$k] = $v;
            }
            
            $registrationRecordService = new RegistrationRecordService();
            $activityIds = array_column($result['list'], 'activity_id');
            $datalist = $registrationRecordService->entityRepository->getJoinActivityUserNum($filter['company_id'], $activityIds);
            if ($datalist) {
                foreach ($result['list'] as &$v) {
                    $v['total_join_num'] = $datalist[$v['activity_id']] ?? 0;
                }
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/get",
     *     summary="获取指定详情",
     *     tags={"报名"},
     *     description="获取指定详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDataInfo(Request $request)
    {
        $result = [
            'enterprise_list' => [],
            'distributor_list' => [],
        ];
        $companyId = app('auth')->user()->get('company_id');
        $id = intval($request->get('activity_id'));
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->entityRepository->getInfoById($id);
        if (!$result) {
            return $this->response->array($result);
        }
        
        //转换会员等级名称 member_level
        if ($result['member_level']) {
            $member_level = explode(',', $result['member_level']);
            
            //普通会员等级
            $memberCardService = new MemberCardService();
            $rsLevel = $memberCardService->memberCardGradeRepository->getList('*', ['grade_id' => $member_level]);
            foreach ($rsLevel as $v) {
                $result['member_level_list'][] = [
                    'grade_id' => $v['grade_id'],
                    'grade_name' => $v['grade_name']
                ];
            }
            
            //付费会员等级
            $vipGradeService = new VipGradeService();
            $rsVipLevel = $vipGradeService->entityRepository->lists(['lv_type' => $member_level]);
            foreach ($rsVipLevel as $v) {
                $result['member_level_list'][] = [
                    'lv_type' => $v['lv_type'],
                    'grade_name' => $v['grade_name']
                ];
            }
        }
        
        //转换区域名称
        $addressService = new AddressService();
        $result['area_name'] = $addressService->getAreaName($result['area']);
        
        //获取关联的内购企业
        $result['enterprise_list'] = [];
        if ($result['enterprise_ids']) {
            $filter = [
                'id' => explode(',', $result['enterprise_ids']),
            ];
            $enterprisesService = new EnterprisesService();
            $result['enterprise_list'] = $enterprisesService->enterprisesRepository->getLists($filter);
            
            if ($result['enterprise_list']) {
                //获取店铺名称
                $distributorService = new DistributorService();
                $storeIds = array_filter(array_unique(array_column($result['enterprise_list'], 'distributor_id')), function ($distributorId) {
                    return is_numeric($distributorId) && $distributorId >= 0;
                });
                $storeData = [];
                if ($storeIds) {
                    $storeList = $distributorService->getDistributorOriginalList(['distributor_id' => $storeIds]);
                    $storeData = array_column($storeList['list'], null, 'distributor_id');
                    // 附加总店信息
                    $storeData[0] = $distributorService->getDistributorSelfSimpleInfo($companyId);
                }
                if ($storeData) {
                    foreach ($result['enterprise_list'] as $k => $v) {
                        $v['distributor_name'] = isset($v['distributor_id']) ? ($storeData[$v['distributor_id']]['name'] ?? '') : '';
                        $result['enterprise_list'][$k] = $v;
                    }
                } 
            }
        }

        //获取关联的店铺数据
        $activityRelShopService = new RegistrationActivityRelShopService();
        $result['distributor_list'] = $activityRelShopService->getRelShops($id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/del",
     *     summary="删除活动",
     *     tags={"报名"},
     *     description="删除活动",
     *     operationId="deleteData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="操作结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function deleteData(Request $request)
    {
        $result = [];
        $id = $request->get('activity_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->entityRepository->deleteById($id);
        return $this->response->array(['status' => $result]);
    }

    /**
     * path="/selfhelp/registrationActivity/cancel",
     *     summary="终止活动",
     */
    public function cancelActivity(Request $request)
    {
        $result = [];
        $id = $request->get('activity_id');
        if (!$id) {
            return $this->response->array($result);
        }
        $result = $this->service->entityRepository->updateOneBy(['activity_id' => $id], ['end_time' => time()]);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/registrationActivity/invalid",
     *     summary="废弃指定活动",
     *     tags={"报名"},
     *     description="废弃指定活动",
     *     operationId="deleteData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="1", description="操作结果(0,1)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function restoreData(Request $request)
    {
        $result = [];
        $id = intval($request->get('activity_id', 0));
        if (!$id) {
            return $this->response->array($result);
        }
        $filter['activity_id'] = $id;
        $params['end_time'] = time() - 3600;
        $result = $this->service->entityRepository->updateBy($filter, $params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/registrationActivity/easylist",
     *     summary="报名活动列表",
     *     tags={"报名"},
     *     description="报名活动列表",
     *     operationId="getEasyDatalist",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="状态(ongoing)", required=true, type="string"),
     *     @SWG\Parameter( name="is_valid", in="query", description="是否有效(0, 1)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="34", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="38", description="活动ID "),
     *                          @SWG\Property( property="activity_name", type="string", example="cesss", description="活动名称"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getEasyDatalist(Request $request)
    {
        $result = [
            'total_count' => 0,
            'list' => [],
        ];
        $distributor_id = intval($request->input('distributor_id', 0));
        $page = $request->get('page', 1);
        $size = $request->get('pageSize', $this->limit);
        $orderBy = ['activity_id' => 'DESC'];
        $filter = $this->_getFilter($request);
        if (!$filter) {
            return $this->response->array($result);
        }
        $result = $this->service->entityRepository->lists($filter, 'activity_id,activity_name,temp_id', $page, $size, $orderBy);
        if ($result['list']) {
            $activity_ids = array_column($result['list'], 'activity_id');
            $temp_ids = array_column($result['list'], 'temp_id');
            
            //获取绑定的店铺名称
            $activity_rel_shops = [];
            $distributor_names = [];
            $distributor_ids = [];
            if ($distributor_id) {
                //只显示当前店铺
                $distributor_ids = [$distributor_id];
            } else {
                //显示所有店铺
                $registrationActivityRelShopService = new RegistrationActivityRelShopService();
                $rsRelShops = $registrationActivityRelShopService->entityRepository->getLists(['activity_id' => $activity_ids]);
                if ($rsRelShops) {
                    foreach ($rsRelShops as $v) {
                        $activity_rel_shops[$v['activity_id']][] = $v['distributor_id'];
                    }
                    $distributor_ids = array_column($rsRelShops, 'distributor_id');
                    $distributor_ids = array_unique($distributor_ids);
                    $distributor_ids = array_filter($distributor_ids);
                }
            }
            if ($distributor_ids) {
                $distributorService = new DistributorService();
                $rsDistributor = $distributorService->entityRepository->getLists(['distributor_id' => $distributor_ids]);
                if ($rsDistributor) {
                    $distributor_names = array_column($rsDistributor, 'name', 'distributor_id');
                }
            }

            //获取绑定的表单名称
            $form_names = [];
            $form_ids = array_column($result['list'], 'temp_id');
            $formTemplateService = new FormTemplateService();
            $rsFormTemplate = $formTemplateService->entityRepository->lists(['id' => $form_ids]);
            if ($rsFormTemplate["list"]) {
                $form_names = array_column($rsFormTemplate["list"], 'tem_name', 'id');
            }

            foreach ($result['list'] as $k => $v) {
                $v['tem_name'] = $form_names[$v['temp_id']] ?? $v['temp_id'];
                //获取绑定的店铺名称
                $v['distributor_name'] = [];
                if (isset($activity_rel_shops[$v['activity_id']])) {
                    foreach ($activity_rel_shops[$v['activity_id']] as $distributor_id) {
                        if (isset($distributor_names[$distributor_id])) {
                            $v['distributor_name'][] = $distributor_names[$distributor_id];
                        }
                    }
                }
                $result['list'][$k] = $v;
            }
        }
        
        return $this->response->array($result);
    }

    private function _getFilter($request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $params = $request->all('temp_id', 'activity_name', 'start_time', 'end_time', 'status', 'is_valid');
        if ($params['status']) {
            switch ($params['status']) {
                case "waiting":
                    $filter['start_time|gte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "ongoing":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "end":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|lte'] = time();
                    break;
            }
        }
        if ($request->get('is_valid')) {
            $filter['start_time|lte'] = time();
            $filter['end_time|gte'] = time();
        }

        if (isset($params['start_time'],$params['end_time']) && $params['start_time'] && $params['end_time']) {
            $filter['created|gte'] = $params['start_time'];
            $filter['created|lte'] = $params['end_time'];
        }
        
        if ($request->input('distributor_id', 0)) {
            $distributor_id = intval($request->input('distributor_id'));
            $registrationActivityRelShopService = new RegistrationActivityRelShopService();
            $relShops = $registrationActivityRelShopService->entityRepository->getLists(['distributor_id' => $distributor_id], 'activity_id', 1, 100);
            if (!$relShops) {
                return false;
            }
            $filter['activity_id'] = array_column($relShops, 'activity_id');
        }
        return $filter;
    }

    /**
     * @SWG\Definition(
     *     definition="RegistrationActivity",
     *     description="报名活动信息",
     *     type="object",
     *     @SWG\Property( property="activity_id", type="string", example="39", description="活动ID  "),
     *                  @SWG\Property( property="temp_id", type="string", example="23", description="表单模板id "),
     *                  @SWG\Property( property="activity_name", type="string", example="免费美家设计", description="活动名称 "),
     *                  @SWG\Property( property="start_time", type="string", example="2021-01-01", description="活动开始时间"),
     *                  @SWG\Property( property="end_time", type="string", example="2021-11-01", description="活动结束时间"),
     *                  @SWG\Property( property="join_limit", type="string", example="1", description="可参与次数"),
     *                  @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                  @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                  @SWG\Property( property="created", type="string", example="1612410464", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612410464", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     * )
     */
}
