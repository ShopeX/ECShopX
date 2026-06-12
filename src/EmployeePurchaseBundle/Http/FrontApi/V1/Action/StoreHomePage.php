<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 */

namespace EmployeePurchaseBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\StoreHomePageService;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class StoreHomePage extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/employeepurchase/store-home-page/{id}",
     *     summary="内购模版详情（含完整模板装修数据）",
     *     tags={"内购"},
     *     description="返回内购模版表字段、pages_template 列表完整行（pages_template_record）、以及与 pagestemplate/detail 同构的 page_template_detail（list/config/tab_bar 等）。当存在 weapp_customize_page_id 时，page_template_detail 从 wechat_weapp_setting 的 page_name=custom_{weapp_customize_page_id} 读取，version 依次尝试 shop_{distributor_id} 与 v1.0.1。resolved_pages_template_id 为装修 pages_template 主键，与自定义页 page_name 语义不同。可选 distributor_id、e_activity_id。",
     *     operationId="employeepurchaseStoreHomePageDetailFront",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="id", in="path", description="内购模版主键 employee_purchase_store_home_page.id", required=true, type="integer"),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺 ID；与 Cart 等接口一致，用于经销商数据隔离", required=false, type="integer"),
     *     @SWG\Parameter(name="e_activity_id", in="query", description="内购活动 ID；传入时写入模板 content 的 e_activity_id（组件活动价等）", required=false, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功",
     *         @SWG\Schema(
     *             @SWG\Property(property="data", type="object",
     *                 @SWG\Property(property="store_home_page_id", type="integer"),
     *                 @SWG\Property(property="resolved_pages_template_id", type="integer", description="装修 pages_template 主键，可能为空"),
     *                 @SWG\Property(property="weapp_customize_page_id", type="integer"),
     *                 @SWG\Property(property="template_name", type="string"),
     *                 @SWG\Property(property="template_meta", type="object"),
     *                 @SWG\Property(property="pages_template_record", type="object", description="pages_template 列表接口同源完整行"),
     *                 @SWG\Property(property="page_template_detail", type="object", description="与 GET pagestemplate/detail 一致的聚合结构；有 weapp_customize_page_id 时优先对应 custom_{id} 装修数据")
     *             )
     *         )
     *     ),
     *     @SWG\Response(response="default", description="错误", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones")))
     * )
     */
    public function getDetail(Request $request, $id)
    {
        $auth = $request->get('auth');
        $companyId = (int) ($auth['company_id'] ?? 0);
        $authDistributorId = (int) $request->get('distributor_id', 0);
        $sid = (int) $id;
        if ($sid <= 0) {
            throw new ResourceException('id 无效');
        }

        $userId = (int) ($auth['user_id'] ?? 0);
        $eActivityId = (int) $request->input('e_activity_id', 0);

        $service = new StoreHomePageService();
        $data = $service->getDetailForFront($companyId, $authDistributorId, $sid, $userId, $eActivityId);

        return $this->response->array($data);
    }
}
