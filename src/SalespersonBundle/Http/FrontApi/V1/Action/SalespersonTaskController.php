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

use SalespersonBundle\Jobs\SalespersonRelationshipContinuity;
use SalespersonBundle\Services\SalespersonTaskRecordService;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use SalespersonBundle\Jobs\SalespersonTask;
use DistributionBundle\Services\DistributorService;

class SalespersonTaskController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/task/share",
     *     summary="完成导购任务分享",
     *     tags={"导购"},
     *     description="完成导购任务分享",
     *     operationId="share",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesperson_id", in="query", description="导购id", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="items商品", required=true, type="string"),
     *     @SWG\Parameter( name="id", in="query", description="id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="分享结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function share(Request $request)
    {
        $authInfo = $request->get('auth');
        $requestData = $request->all('salesperson_id', 'type', 'id');
        $rules = [
            'salesperson_id' => ['required', '导购id必填'],
        ];
        $error = validator_params($requestData, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $salespersonTaskRecordService = new SalespersonTaskRecordService();
        $params = [
            'company_id' => $authInfo['company_id'],
            'salesperson_id' => $requestData['salesperson_id'],
            'user_id' => $authInfo['user_id'],
            'type' => $requestData['type'] ?? 'index',
            'id' => $requestData['id'] ?? '0',
            'username' => $authInfo['nickname'] ?? ($authInfo['username'] ?? '微信用户'),
        ];

        $result = $salespersonTaskRecordService->completeShare($params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/subtask/post",
     *     summary="提交子任务参数",
     *     tags={"导购"},
     *     description="导购端分享到商城后，提交子任务参数",
     *     operationId="postSubtask",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="subtask_id", in="query", description="子任务ID", required=true, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", type="integer"),
     *     @SWG\Parameter( name="shop_code", in="query", description="门店编号，门店ID不存在必填", type="string"),
     *     @SWG\Parameter( name="employee_number", in="query", description="员工工号", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="分享结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function postSubtask(Request $request)
    {
        $authInfo = $request->get('auth');
        $input = $request->all();
        app('log')->info('guide-api-callbacksubtask start '.print_r($input, true));
        $subtaskId = $input['subtask_id'] ?? 0;
        $distributorId = $input['distributor_id'] ?? 0;// 门店id
        $shopCode = $input['shop_code'] ?? '';// 门店编号
        $employeeNumber = $input['employee_number'] ?? '';
        $itemId = $input['item_id'] ?? 0;
        if (!$subtaskId || !$employeeNumber || (!$distributorId && !$shopCode)) {
            app('log')->info('guide-api-callbacksubtask error 参数不正确 '.json_encode($input));
            return $this->response->array(['status' => true,'input'=>$input,'error'=>'参数不正确']);
            // throw new ResourceException('参数不正确');
        }

        if (!$shopCode) {
            $filter = [
                'distributor_id' => $distributorId,
                'is_valid' => 'true',
            ];
            $distributionService = new DistributorService();
            $distributor = $distributionService->entityRepository->getInfo($filter);
            if ($distributor) {
                $shopCode = $distributor['shop_code'];
            }
        }
        if (!$shopCode) {
            app('log')->info('guide-api-callbacksubtask error 门店信息获取失败 '.json_encode($input));
            return $this->response->array(['status' => true,'input'=>$input,'error'=>'门店信息获取失败']);            
            // throw new ResourceException('门店信息获取失败');
        }

        $queue = (new SalespersonTask($authInfo['company_id'], $subtaskId, $shopCode, $employeeNumber, $itemId, $authInfo['unionid']))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/h5app/wxapp/salesperson/relationshipcontinuity",
     *     summary="关系延续埋点",
     *     tags={"导购"},
     *     description="关系延续埋点",
     *     operationId="relationshipContinuity",
     *     @SWG\Parameter( name="event_type", in="query", description="事件类型 [activeItemDetail 分享商品详情页],[activeSeedingDetail 分享种草详情页],[activeDiscountCoupon 分享优惠券],[activeCustomPage 分享自定义页面],[orderPaymentSuccess 订单支付成功]", required=true, type="string"),
     *     @SWG\Parameter( name="event_id", in="query", description="事件id, 是个多态，如果是导购模块则是导购id, 如果是订单模块则是订单id", required=true, type="string"),
     *     @SWG\Parameter( name="user_type", in="query", description="用户类型 [wechat: 微信用户],[enterpriseWeChat: 企业微信用户]", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="基于用户类型来确定的user唯一标识，微信用户为unionid", required=true, type="string"),
     *     @SWG\Parameter( name="user_channel_type", in="query", description="用户的渠道类型", required=false, type="string"),
     *     @SWG\Parameter( name="country", in="query", description="用户所在的国家", required=false, type="string"),
     *     @SWG\Parameter( name="city", in="query", description="用户所在的市", required=false, type="string"),
     *     @SWG\Parameter( name="province", in="query", description="用户所在的省", required=false, type="string"),
     *     @SWG\Parameter( name="device_manufacturer", in="query", description="设备制造商, 例: Apple", required=false, type="string"),
     *     @SWG\Parameter( name="device_os", in="query", description="设备操作系统, 例: iOs、Android", required=false, type="string"),
     *     @SWG\Parameter( name="device_os_version", in="query", description="设备操作系统版本号, 例: 14.0", required=false, type="string"),
     *     @SWG\Parameter( name="device_id", in="query", description="设备的唯一id (UDID、IEMI)", required=false, type="string"),
     *     @SWG\Parameter( name="device_name", in="query", description="设备名称, 例: iPhone11", required=false, type="string"),
     *     @SWG\Parameter( name="path", in="query", description="事件来源路径, 例: /pages/index", required=false, type="string"),
     *     @SWG\Parameter( name="network_type", in="query", description="网络类型, 例: 4G", required=false, type="string"),
     *     @SWG\Parameter( name="params", in="query", description="json内容，必要的记录参数", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example="true", description="分享结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function relationshipContinuity(Request $request)
    {
        $input = $request->all();
        $authInfo = $request->get('auth');
        $evenType = $input['event_type'] ?? '';
        $evenId = $input['event_id'] ?? '';
        $userType = $input['user_type'] ?? '';
        $userId = $input['user_id'] ?? '';

        if (!$evenType || !$evenId || !$userType || !$userId) {
            throw new ResourceException('参数不正确');
        }

        $input['company_id'] = $authInfo['company_id'];

        $queue = (new SalespersonRelationshipContinuity($input))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($queue);

        return $this->response->array(['status' => true]);
    }
}
