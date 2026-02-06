<?php

namespace ThemeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use ThemeBundle\Services\PagesAdPlaceService;

class PagesAdPlace extends Controller
{
    /**
     * @SWG\Get(
     *     path="/adplace/carousel/list",
     *     tags={"模版"},
     *     summary="广告位列表",
     *     description="广告位列表",
     *     operationId="getList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=true,
     *         type="integer",
     *         minimum=1
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量",
     *         required=true,
     *         type="integer",
     *         minimum=1,
     *         maximum=50
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="query",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="query",
     *         description="广告位ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="query",
     *         description="广告位名称",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="ad_type",
     *         in="query",
     *         description="广告类型 弹窗=>popup，轮播图=>carousel",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="status",
     *         in="query",
     *         description="状态：0-未开始，1-进行中，2-已结束",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pages",
     *         in="query",
     *         description="关联页面",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="audit_status",
     *         in="query",
     *         description="审核状态：submitting待提交 processing审核中 approved成功 rejected审核拒绝",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Property(property="data", type="object",
     *             @SWG\Property(property="total_count", type="integer", example="100", description="总数"),
     *             @SWG\Property(property="list", type="array", @SWG\Items(ref="#/definitions/PagesAdPlace")),
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function getList(Request $request)
    {
        $params = $request->all();

        $validator = app('validator')->make($params, [
            'page'     => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
            'ad_type' => 'in:popup,carousel',
            'audit_status' => 'in:submitting,processing,approved,rejected',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $page  = $params['page'];
        $pageSize = $params['pageSize'];

        if (isset($params['regionauth_id']) && $params['regionauth_id']) {
            $filter['regionauth_id'] = $params['regionauth_id'];
        }

        if (isset($params['id']) && $params['id']) {
            $filter['id'] = $params['id'];
        }

        if (isset($params['name']) && $params['name']) {
            $filter['name|contains'] = $params['name'];
        }

        if (isset($params['ad_type']) && $params['ad_type']) {
            $filter['ad_type'] = $params['ad_type'];
        }

        if (isset($params['distributor_id']) && $params['distributor_id']) {
            $filter['distributor_id'] = $params['distributor_id'];
        }

        if (isset($params['pages']) && $params['pages']) {
            $filter['pages'] = $params['pages'];
        }

        if (isset($params['audit_status']) && $params['audit_status']) {
            $filter['audit_status'] = $params['audit_status'];
            //待提审, 待审核，需要没有过期
            if (in_array($filter['audit_status'], ['submitting', 'processing'])) {
                $filter['end_time|gt'] = time();
            }
        }

        $now = time();
        if (isset($params['status'])) {
            switch ($params['status']) {
                case '0':
                $filter['start_time|gt'] = $now;
                break;
                case '1':
                $filter['start_time|lte'] = $now;
                $filter['end_time|gte'] = $now;
                break;
                case '2'://已失效
                $filter['end_time|lt'] = $now;
                unset($filter['audit_status']);//显示全部已失效的数据
                break;
            }
        }

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        $orderBy = ['sort' => 'desc', 'id' => 'desc'];
        $service = new PagesAdPlaceService();
        $result = $service->getListWithRelTags($filter, $page, $pageSize, $orderBy);
        foreach ($result['list'] as $key => $val) {
            if ($now < $val['start_time']) {
                $result['list'][$key]['status'] = 0;
            } else if ($now > $val['end_time']) {
                $result['list'][$key]['status'] = 2;
            } else {
                $result['list'][$key]['status'] = 1;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/adplace/{id}",
     *     tags={"模版"},
     *     summary="广告位详情",
     *     description="根据ID获取广告位详情",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(ref="#/definitions/PagesAdPlace")
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function getInfo(Request $request, $id)
    {
        $filter['id'] = $id;
        $filter['company_id'] = app('auth')->user()->get('company_id');

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        $service = new PagesAdPlaceService();
        $result = $service->getInfoWithRelTags($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adplace",
     *     tags={"模版"},
     *     summary="创建广告位",
     *     description="创建广告位",
     *     operationId="create",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="formData",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="formData",
     *         description="店铺ID",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="ad_type",
     *         in="formData",
     *         description="广告类型：弹窗=>popup，轮播图=>carousel",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="广告位名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pages",
     *         in="formData",
     *         description="关联页面",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="start_time",
     *         in="formData",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="formData",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="setting",
     *         in="formData",
     *         description="设置信息,json格式",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="auto_play",
     *         in="formData",
     *         description="自动播放",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="play_interval",
     *         in="formData",
     *         description="播放间隔时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="auto_close",
     *         in="formData",
     *         description="自动关闭",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="close_delay",
     *         in="formData",
     *         description="关闭延迟时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(ref="#/definitions/PagesAdPlace")
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all();
        $params['company_id'] = app('auth')->user()->get('company_id');

        $validator = app('validator')->make($params, [
            'ad_type' => 'required|in:popup,carousel',
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'pages' => 'required',
            'rel_tags' => 'array',
            'rel_tags.*.tag_id' => 'required|integer',
        ], [
            'ad_type.*' => '广告类型必填',
            'name.*' => '广告名称必填',
            'start_time.*' => '开始时间必填',
            'end_time.*' => '结束时间比天',
            'pages.*' => '应用页面必填',
            'rel_tags.*' => '请选择适用人群',
            'rel_tags.*.tag_id.*' => '请选择适用人群',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        // 店铺端
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $params['distributor_id'] = [$distributorId];
            $params['source_id'] = $distributorId;
        }

        // 待提交审核
        $params['audit_status'] = 'submitting';

        $service = new PagesAdPlaceService();
        $result = $service->createWithRelTags($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/adplace/{id}",
     *     tags={"模版"},
     *     summary="更新广告位",
     *     description="更新广告位",
     *     operationId="update",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="regionauth_id",
     *         in="formData",
     *         description="区域ID",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="formData",
     *         description="店铺ID",
     *         required=false,
     *         type="array",
     *         @SWG\Items(type="integer")
     *     ),
     *     @SWG\Parameter(
     *         name="ad_type",
     *         in="formData",
     *         description="广告类型：弹窗=>popup，轮播图=>carousel",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="广告位名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="pages",
     *         in="formData",
     *         description="关联页面",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="start_time",
     *         in="formData",
     *         description="开始时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="end_time",
     *         in="formData",
     *         description="结束时间",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="setting",
     *         in="formData",
     *         description="设置信息,json格式",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="auto_play",
     *         in="formData",
     *         description="自动播放",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="play_interval",
     *         in="formData",
     *         description="播放间隔时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="auto_close",
     *         in="formData",
     *         description="自动关闭",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="close_delay",
     *         in="formData",
     *         description="关闭延迟时间",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序",
     *         required=false,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function update(Request $request, $id)
    {
        $params = $request->all();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        $validator = app('validator')->make($params, [
            'ad_type' => 'required|in:popup,carousel',
            'name' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'pages' => 'required',
            'rel_tags' => 'array',
            'rel_tags.*.tag_id' => 'required|integer',
        ], [
            'ad_type.*' => '广告类型必填',
            'name.*' => '广告名称必填',
            'start_time.*' => '开始时间必填',
            'end_time.*' => '结束时间比天',
            'pages.*' => '应用页面必填',
            'rel_tags.*' => '请选择适用人群',
            'rel_tags.*.tag_id.*' => '请选择适用人群',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $params['distributor_id'] = [$distributorId];
            $filter['source_id'] = $distributorId;
        }

        // 待提交审核
        $params['audit_status'] = 'submitting';

        $service = new PagesAdPlaceService();
        $result = $service->updateWithRelTags($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/adplace/{id}",
     *     tags={"模版"},
     *     summary="删除广告位",
     *     description="删除广告位",
     *     operationId="delete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function delete(Request $request, $id)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        $service = new PagesAdPlaceService();
        $result = $service->deleteWithRelTags($filter);
        return $this->response->array(['status' => true]);
    }
    
    /**
     * @SWG\Post(
     *     path="/adplace/submit/{id}",
     *     tags={"模版"},
     *     summary="提交审核广告位",
     *     description="提交审核广告位",
     *     operationId="submit",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function submit(Request $request, $id)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        // 待审核
        $params['audit_status'] = 'processing';

        $service = new PagesAdPlaceService();
        $result = $service->updateOneBy($filter, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/adplace/audit/{id}",
     *     tags={"模版"},
     *     summary="审核广告位",
     *     description="审核广告位",
     *     operationId="audit",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="audit_status",
     *         in="formData",
     *         description="审核状态",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="audit_remark",
     *         in="formData",
     *         description="审核备注",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function audit(Request $request, $id)
    {
        $params = $request->all('audit_status', 'audit_remark');
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        $validator = app('validator')->make($params, [
            'audit_status' => 'required|in:approved,rejected',
            'audit_remark' => 'required_if:audit_status,rejected',
        ], [
            'audit_status.*' => '审核状态必填',
            'audit_remark.*' => '请填写拒绝原因',
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }

        $service = new PagesAdPlaceService();
        $result = $service->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/adplace/withdraw/{id}",
     *     tags={"模版"},
     *     summary="撤回广告位审核申请",
     *     description="撤回广告位审核申请",
     *     operationId="withdraw",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         description="广告位ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             type="object",
     *             @SWG\Property(property="status", type="boolean")
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/ThemeErrorRespones")))
     * )
     */
    public function withdraw(Request $request, $id)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['id'] = $id;

        // 店铺端
        $filter['source_id'] = 0;
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $distributorId = $request->get('distributor_id');
            $filter['source_id'] = $distributorId;
        }

        // 待提交
        $params['audit_status'] = 'submitting';

        $service = new PagesAdPlaceService();
        $result = $service->updateOneBy($filter, $params);
        return $this->response->array($result);
    }
}

/**
 * @SWG\Definition(
 *     definition="PagesAdPlace",
 *     type="object",
 *     @SWG\Property(property="id", type="integer", description="ID"),
 *     @SWG\Property(property="company_id", type="integer", description="公司ID"),
 *     @SWG\Property(property="regionauth_id", type="integer", description="区域ID"),
 *     @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
 *     @SWG\Property(property="ad_type", type="string", description="广告类型：弹窗=>popup，轮播图=>carousel"),
 *     @SWG\Property(property="name", type="string", description="广告位名称"),
 *     @SWG\Property(property="pages", type="array", @SWG\Items(type="string"), description="关联页面"),
 *     @SWG\Property(property="start_time", type="integer", description="开始时间"),
 *     @SWG\Property(property="end_time", type="integer", description="结束时间"),
 *     @SWG\Property(property="setting", type="string", description="设置信息,json格式"),
 *     @SWG\Property(property="auto_play", type="integer", description="自动播放"),
 *     @SWG\Property(property="play_interval", type="integer", description="播放间隔时间"),
 *     @SWG\Property(property="auto_close", type="integer", description="自动关闭"),
 *     @SWG\Property(property="close_delay", type="integer", description="关闭延迟时间"),
 *     @SWG\Property(property="status", type="integer", description="状态：0-未开始，1-进行中，2-已结束"),
 *     @SWG\Property(property="created", type="integer", description="创建时间"),
 *     @SWG\Property(property="updated", type="integer", description="更新时间"),
 *     @SWG\Property(property="audit_status", type="string", description="审核状态：submitting,processing,approved,rejected"),
 *     @SWG\Property(property="audit_remark", type="string", description="审核备注"),
 *     @SWG\Property(property="rel_tags", type="array", description="关联人群标签", @SWG\Items(ref="#/definitions/PagesAdPlaceTag")),
 *     @SWG\Property(property="sort", type="integer", description="排序"),
 *     @SWG\Property(property="rel_distributors", type="array", description="关联店铺", @SWG\Items(ref="#/definitions/PagesAdPlaceRelDistributor")),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PagesAdPlaceTag",
 *     type="object",
 *     @SWG\Property(property="tag_id", type="integer", description="标签ID"),
 *     @SWG\Property(property="tag_name", type="string", description="标签名称"),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="PagesAdPlaceRelDistributor",
 *     type="object",
 *     @SWG\Property(property="distributor_id", type="integer", description="店铺ID"),
 *     @SWG\Property(property="distributor_name", type="string", description="店铺名称"),
 * )
 */