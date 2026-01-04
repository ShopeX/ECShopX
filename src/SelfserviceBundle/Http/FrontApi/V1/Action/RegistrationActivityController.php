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

namespace SelfserviceBundle\Http\FrontApi\V1\Action;

use EspierBundle\Services\AddressService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use PromotionsBundle\Services\MarketingActivityService;
use SelfserviceBundle\Services\RegistrationActivityService;
use SelfserviceBundle\Services\RegistrationRecordService;
use SelfserviceBundle\Services\FormTemplateService;

class RegistrationActivityController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationActivity",
     *     summary="获取指定报名活动",
     *     tags={"报名"},
     *     description="获取指定报名活动",
     *     operationId="getRegistrationActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="activeing", description="活动状态"),
     *                  @SWG\Property( property="activity_info", type="object",
     *                          @SWG\Property( property="activity_id", type="string", example="28", description="活动ID"),
     *                          @SWG\Property( property="temp_id", type="string", example="15", description="表单模板id"),
     *                          @SWG\Property( property="activity_name", type="string", example="苹果新品预售报名", description="活动名称"),
     *                          @SWG\Property( property="start_time", type="string", example="1586361600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1586620799", description="活动结束时间"),
     *                          @SWG\Property( property="join_limit", type="string", example="9", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="false", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="false", description="是否小程序模板通知"),
     *                          @SWG\Property( property="created", type="string", example="1586495521", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1586495527", description="修改时间"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="formdata", type="object",
     *                                  @SWG\Property( property="id", type="string", example="15", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="tem_name", type="string", example="超全的模板", description="表单模板名称"),
     *                                  @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                  @SWG\Property( property="form_style", type="string", example="single", description="表单关键指数, single:单页问卷, multiple:多页问卷"),
     *                                  @SWG\Property( property="header_link_title", type="string", example="XX新品预售报名", description="头部文字"),
     *                                  @SWG\Property( property="header_title", type="string", example="帮助公众号获取用户信息，进行用户管理", description="头部文字内容"),
     *                                  @SWG\Property( property="bottom_title", type="string", example="苹果", description="表单关键指数"),
     *                                  @SWG\Property( property="key_index", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="key_index"),
     *                                  ),
     *                                  @SWG\Property( property="tem_type", type="string", example="ask_answer_paper", description="表单模板类型；ask_answer_paper：问答考卷，basic_entry：基础录入"),
     *                                  @SWG\Property( property="content", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="title", type="string", example="", description="标题"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="formdata", type="array",
     *                                              @SWG\Items( type="object",
     *                                                  @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                                  @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                                                  @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                                  @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                                  @SWG\Property( property="status", type="string", example="1", description="自行更改字段描述"),
     *                                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                                  @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                                  @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                                                  @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                               ),
     *                                          ),
     *                                       ),
     *                                  ),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationActivity(Request $request)
    {
        $result = [
            'total_join_num' => 0,//当前已经参与的人数
            'activity_info' => [],
        ];
        $authInfo = $request->get('auth');
        // if ($authInfo['user_id'] ?? 0) {
        //     $filter = [
        //         'user_id' => $authInfo['user_id'],
        //         'company_id' => $authInfo['company_id'],
        //         'activity_id' => $request->get('activity_id'),
        //     ];
        //     $registrationRecordService = new RegistrationRecordService();
        //     $recordList = $registrationRecordService->getRocordList($filter);
        //     $result['status'] = 'already';
        // }
        // $result['activity_info'] = null;

        $filter = [
            'company_id' => $authInfo['company_id'],
            'activity_id' => $request->get('activity_id'),
        ];
        $registrationActivityService = new RegistrationActivityService();
        $activity_info = $registrationActivityService->entityRepository->getInfo($filter);
        if (!$activity_info) {
            throw new ResourceException(trans('SelfserviceBundle.activity_not_exist'));
        }
        //获取活动状态
        $registrationActivityService->getStatusName($activity_info);

        $activity_info['start_date'] = $activity_info['start_time'] ? date('Y-m-d H:i:s', $activity_info['start_time']) : '';
        $activity_info['end_date'] = $activity_info['end_time'] ? date('Y-m-d H:i:s', $activity_info['end_time']) : '';

        $registrationRecordService = new RegistrationRecordService();
        $user_id = $authInfo['user_id'] ?? 0;
        if ($user_id) {
            $_filter = [
                'activity_id' => $activity_info['activity_id'], 
                'user_id' => $user_id, 
                // 'status' => ['pending', 'passed', 'verified']
            ];
            $activity_info['record_info'] = $registrationRecordService->entityRepository->getLists($_filter, 'record_id, status, reason, created');
        }
        
        //获取已经参加活动的总人数
        if ($activity_info['join_limit']) {
            $filter = [
                'company_id' => $authInfo['company_id'],
                'activity_id' => $request->get('activity_id'),
            ];            
            $result['total_join_num'] = $registrationRecordService->entityRepository->count($filter);
        }

        //活动绑定的表单
        if ($activity_info['temp_id']) {
            $formTemplateService = new FormTemplateService();
            $temp = $formTemplateService->entityRepository->getInfo(['company_id' => $authInfo['company_id'], 'id' => $activity_info['temp_id']]);
            $activity_info['formdata'] = $temp;
        }
        
        $result['activity_info'] = $activity_info;
        return $this->response->array($result);
    }

    /**
     *     path="/h5app/wxapp/registrationActivityList",
     *     summary="获取报名活动列表",
     */
    public function getRegistrationActivityList(Request $request)
    {
        $page = intval($request->get('page', 1));
        $pageSize = intval($request->get('pageSize', 1));
        $status = intval($request->get('status', 0));// 1 当前活动；2 精彩回顾
        $activity_name = trim($request->get('activity_name', ''));
        
        $authInfo = $request->get('auth');
        $filter = [
            'company_id' => $authInfo['company_id'],
        ];
        if ($status == 1) $filter['end_time|gte'] = time();
        if ($status == 2) $filter['end_time|lte'] = time();
        if ($activity_name) $filter['activity_name|contains'] = $activity_name;
        $orderBy = ['activity_id' => 'DESC'];
        $registrationActivityService = new RegistrationActivityService();
        $result = $registrationActivityService->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);

        if ($result['list']) {
            $user_id = $authInfo['user_id'] ?? 0;
            $addressService = new AddressService();
            $registrationRecordService = new RegistrationRecordService();
            foreach ($result['list'] as $k => $v) {
                $v['area_name'] = $addressService->getAreaName($v['area']);
                $registrationActivityService->getStatusName($v);

                //活动已参与的人数
                if ($v['join_limit']) {
                    $_filter = ['activity_id' => $v['activity_id']];
                    $v['total_join_num'] = $registrationRecordService->entityRepository->count($_filter);
                }

                //仅当用户已报名该活动时返回
                //状态: pending 待审核，passed 已通过，rejected 已拒绝, canceled 已取消, verified 已核销
                if ($user_id) {
                    $_filter = [
                        'activity_id' => $v['activity_id'], 
                        'user_id' => $user_id, 
                        // 'status' => ['pending', 'passed', 'verified']
                    ];
                    $v['record_info'] = $registrationRecordService->entityRepository->getLists($_filter, 'record_id, status, reason, created');
                }
                
                $result['list'][$k] = $v;
            }
        }
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationRecordList",
     *     summary="获取指定报名日志",
     *     tags={"报名"},
     *     description="获取指定报名日志",
     *     operationId="getRegistrationRecordList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="11", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/RegistrationRecordInfo"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationRecordList(Request $request)
    {
        $status = $request->get('status', '');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 30);
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        if ($request->get('activity_id')) {
            $filter['activity_id'] = intval($request->get('activity_id'));
        }
        $filter['user_id'] = $authInfo['user_id'];
        $registrationRecordService = new RegistrationRecordService();
        
        if ($status) {
            if ($status == $registrationRecordService->getStatusName($status)) {
                throw new ResourceException(trans('SelfserviceBundle.parameter_error', ['status' => $status]));
            }
            $filter['status'] = $status;
        }
        
        $result = $registrationRecordService->getRocordList($filter, $page, $pageSize);
        if ($result['list']) {
            foreach ($result['list'] as $k => $v) {
                //获取当前报名记录可以执行的操作按钮
                $v['action'] = $registrationRecordService->getRecordAction($v);
                $result['list'][$k] = $v;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/registrationSubmit",
     *     summary="报名提交",
     *     tags={"报名"},
     *     description="报名提交",
     *     operationId="registrationSubmit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动id", required=true, type="integer"),
     *     @SWG\Parameter( name="formdata[content]", in="query", description="报名内容(json)", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="record_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                  @SWG\Property( property="user_id", type="string", example="20337", description="用户id "),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="手机号"),
     *                  @SWG\Property( property="wxapp_appid", type="string", example="", description="会员小程序appid"),
     *                  @SWG\Property( property="open_id", type="string", example="", description="用户open_id"),
     *                  @SWG\Property( property="status", type="string", example="pending", description="状态"),
     *                  @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="区块一标题", description="名称"),
     *                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                  @SWG\Property( property="formdata", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="36", description="ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                          @SWG\Property( property="field_title", type="string", example="团长姓名", description="表单项标题(中文描述)"),
     *                                          @SWG\Property( property="field_name", type="string", example="username", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                          @SWG\Property( property="form_element", type="string", example="text", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                          @SWG\Property( property="answer", type="string", example="吴琼", description="回答内容"),
     *                                       ),
     *                                  ),
     *                               ),
     *                          ),
     *                  @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
     *                  @SWG\Property( property="created", type="string", example="1612441632", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612441632", description=" 修改时间"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function registrationSubmit(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!($authInfo['user_id'] ?? 0)) {
            throw new ResourceException(trans('SelfserviceBundle.only_members_can_register'));
        }
        $params['user_id'] = $authInfo['user_id'];
        $params['wxapp_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['open_id'] = $authInfo['open_id'] ?? '';
        $params['mobile'] = $authInfo['mobile'];
        $params['form_mobile'] = $authInfo['mobile'];
        $params['company_id'] = $authInfo['company_id'];
        $params['true_name'] = trim($request->get('true_name', ''));
        $params['distributor_id'] = intval($request->get('distributor_id', 0));
        $params['activity_id'] = intval($request->get('activity_id', 0));
        $params['record_id'] = intval($request->get('record_id', 0));
        if (!$params['activity_id']) {
            throw new ResourceException(trans('SelfserviceBundle.please_specify_registration_activity'));
        }
            
        $redis_key = 'registrationSubmit:' . $params['user_id'];
        $redis = app('redis');
        if ($redis->setnx($redis_key, 1)) {
            $redis->expire($redis_key, 3);
        } else {
            $redis->expire($redis_key, 3);
            throw new ResourceException(trans('SelfserviceBundle.activity_too_popular_try_later'));
        }

        $activityService = new RegistrationActivityService();
        $activityInfo = $activityService->checkActivityValid($params, $err_msg);
        if ($err_msg) {
            throw new ResourceException($err_msg);
        }

        $formdata = $request->get('formdata', null);
        $params['content'] = $formdata['content'];
        if (!$params['content']) {
            throw new ResourceException(trans('SelfserviceBundle.registration_data_cannot_empty'));
        }
        $params['content'] = is_array($params['content']) ? $params['content'] : json_decode($params['content'], true);

        foreach ($params['content'] as $key => $card) {
            foreach ($card['formdata'] as $k => $value) {
                if (($value['is_required'] ?? false) == 'true' && !($value['answer'] ?? null)) {
                    if ($card['title'] ?? 0) {
                        throw new ResourceException(trans('SelfserviceBundle.field_required_with_card', ['card_title' => $card['title'], 'field_title' => $value['field_title']]));
                    } else {
                        throw new ResourceException(trans('SelfserviceBundle.field_required', ['field_title' => $value['field_title']]));
                    }
                }
                if ($value['answer'] ?? null) {
                    $params['content'][$key]['formdata'][$k]['answer'] = is_array($value['answer']) ? implode(',', $value['answer']) : $value['answer'];
                }
                //识别是否存在姓名字段
                if (!$params['true_name'] && $value['field_name'] == 'username') {
                    $params['true_name'] = $value['answer'] ?? '';
                }
                //验证手机号是否正确
                if ($value['field_name'] == 'mobile' && $value['answer']) {
                    if (!preg_match('/^1[3456789]{1}[0-9]{9}$/', $value['answer'])) {
                        throw new ResourceException(trans('SelfserviceBundle.please_enter_correct_mobile'));
                    }
                    $params['form_mobile'] = $value['answer'];
                }
            }
        }
        $params['content'] = json_encode($params['content'], 256);
        $registrationRecordService = new RegistrationRecordService();
        $result['record_data'] = $registrationRecordService->saveRecord($params, $activityInfo);
        $result['activity_info'] = $activityInfo;
        // $result = $registrationRecordService->entityRepository->create($params);
        return $this->response->array($result);
    }

    /**
     * path="/h5app/wxapp/joinActivity",
     * summary="参与活动(不填写表单)",
     */
    public function joinActivity(Request $request)
    {
        $activity_id = intval($request->input('activity_id', 0));
        if (!$activity_id) {
            throw new ResourceException(trans('SelfserviceBundle.please_specify_registration_activity'));
        }

        $authInfo = $request->get('auth');
        if (!($authInfo['user_id'] ?? 0)) {
            throw new ResourceException(trans('SelfserviceBundle.only_members_can_register'));
        }
        $params['user_id'] = $authInfo['user_id'];
        $params['wxapp_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['open_id'] = $authInfo['open_id'] ?? '';
        $params['mobile'] = $authInfo['mobile'];
        $params['form_mobile'] = $authInfo['mobile'];
        $params['company_id'] = $authInfo['company_id'];        
        $params['activity_id'] = $activity_id;
        $params['content'] = '';
        $params['distributor_id'] = intval($request->get('distributor_id', 0));
        $params['record_id'] = intval($request->get('record_id', 0));
        if ($params['record_id']) {
            throw new ResourceException(trans('SelfserviceBundle.activity_registration_not_allow_modify'));
        }

        $redis_key = 'joinActivity:' . $params['user_id'];
        $redis = app('redis');
        if ($redis->setnx($redis_key, 1)) {
            $redis->expire($redis_key, 3);
        } else {
            throw new ResourceException(trans('SelfserviceBundle.activity_too_popular_try_later_short'));
        }
        
        $err_msg = '';
        $activityService = new RegistrationActivityService();
        $activityInfo = $activityService->checkActivityValid($params, $err_msg);
        if ($err_msg) {
            throw new ResourceException($err_msg);
        }
        if ($activityInfo['temp_id']) {
            throw new ResourceException(trans('SelfserviceBundle.please_fill_registration_form'));
        }

        $registrationRecordService = new RegistrationRecordService();
        $result['record_data'] = $registrationRecordService->saveRecord($params, $activityInfo);
        $result['activity_info'] = $activityInfo;
        return $this->response->array($result);
    }

    /**
     * path="/h5app/wxapp/cancelRecord",
     *     summary="取消活动报名",
     */
    public function cancelRecord(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!($authInfo['user_id'] ?? 0)) {
            throw new ResourceException(trans('SelfserviceBundle.please_login_before_cancel'));
        }
        $params['user_id'] = $authInfo['user_id'];
        $params['wxapp_appid'] = $authInfo['wxapp_appid'] ?? '';
        $params['open_id'] = $authInfo['open_id'] ?? '';
        $params['mobile'] = $authInfo['mobile'];
        $params['company_id'] = $authInfo['company_id'];
        $params['record_id'] = intval($request->get('record_id', 0));
        if (!$params['record_id']) {
            throw new ResourceException(trans('SelfserviceBundle.please_specify_registration_record_id'));
        }
        
        $_filter = [
            'record_id' => $params['record_id'],
            'user_id' => $authInfo['user_id'],
        ];
        $recordService = new RegistrationRecordService();
        $rs = $recordService->entityRepository->getInfo($_filter);
        if (!$rs) {
            throw new ResourceException(trans('SelfserviceBundle.registration_record_not_exist'));
        }

        if (!in_array($rs['status'], ['pending', 'passed'])) {
            throw new ResourceException(trans('SelfserviceBundle.registration_status_cannot_cancel'));
        }

        $activityService = new RegistrationActivityService();
        $rsActivity = $activityService->entityRepository->getInfoById($rs['activity_id']);
        if (!$rsActivity['is_allow_cancel']) {
            throw new ResourceException(trans('SelfserviceBundle.activity_not_allow_cancel'));
        }

        $saveData = [
            'status' => 'canceled',
        ];
        $result = $recordService->entityRepository->updateOneBy($_filter, $saveData);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/registrationRecordInfo",
     *     summary="获取指定报名日志",
     *     tags={"报名"},
     *     description="获取指定报名日志",
     *     operationId="getRegistrationRecordInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="record_id", in="query", description="记录ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  ref="#/definitions/RegistrationRecordInfo"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRegistrationRecordInfo(Request $request)
    {
        $authInfo = $request->get('auth');
        $id = $request->get('record_id');
        $registrationRecordService = new RegistrationRecordService();
        $result = $registrationRecordService->getRocordInfo($id);
        if ($result['user_id'] != $authInfo['user_id']) {
            throw new ResourceException(trans('SelfserviceBundle.information_error'));
        }
        //获取当前报名记录可以执行的操作按钮
        $result['action'] = $registrationRecordService->getRecordAction($result);        
        return $this->response->array($result);
    }

    /**
     * @SWG\Definition(
     *     definition="RegistrationRecordInfo",
     *     description="报名活动信息",
     *     type="object",
     *     @SWG\Property( property="record_id", type="string", example="48", description="记录id"),
     *                          @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *                          @SWG\Property( property="user_id", type="string", example="20342", description="用户id"),
     *                          @SWG\Property( property="mobile", type="string", example="17621716237", description="用户手机号"),
     *                          @SWG\Property( property="status", type="string", example="pending", description="状态"),
     *                          @SWG\Property( property="content", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="title", type="string", example="区块一标题", description="活动名称"),
     *                                  @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                  @SWG\Property( property="formdata", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="id", type="string", example="36", description="ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                          @SWG\Property( property="field_title", type="string", example="团长姓名", description="表单项标题(中文描述)"),
     *                                          @SWG\Property( property="field_name", type="string", example="username", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                                          @SWG\Property( property="form_element", type="string", example="text", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                                          @SWG\Property( property="is_required", type="string", example="false", description="是否必填"),
     *                                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                                          @SWG\Property( property="answer", type="string", example="吴琼", description="回答内容"),
     *                                       ),
     *                                  ),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="reason", type="string", example="null", description="审核不通过原因"),
     *                          @SWG\Property( property="created", type="string", example="1608272078", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1608272078", description="修改时间"),
     *                          @SWG\Property( property="wxapp_appid", type="string", example="wx912913df9fef6ddd", description="会员小程序appid"),
     *                          @SWG\Property( property="open_id", type="string", example="oHxgH0eB5RArTLq6ZCsh8DnQc4KY", description="用户open_id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="created_date", type="string", example="2020-12-18 14:14:38", description="创建时间"),
     *                          @SWG\Property( property="activity_name", type="string", example="qqqq", description="活动名称 "),
     *                          @SWG\Property( property="start_time", type="string", example="1607961600", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1609430399", description="活动结束时间"),
     *                          @SWG\Property( property="start_date", type="string", example="2020-12-15 00:00:00", description="开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2020-12-31 23:59:59", description="有效期结束时间 "),
     *                          @SWG\Property( property="join_limit", type="string", example="111", description="可参与次数"),
     *                          @SWG\Property( property="is_sms_notice", type="string", example="1", description="是否短信通知"),
     *                          @SWG\Property( property="is_wxapp_notice", type="string", example="1", description="是否小程序模板通知"),
     *                          @SWG\Property( property="create_date", type="string", example="2020-12-18 14:14:38", description="创建时间"),
     * )
     */
}
