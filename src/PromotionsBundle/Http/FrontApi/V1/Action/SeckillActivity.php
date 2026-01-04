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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Traits\GetDefaultCur;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use OrdersBundle\Traits\SeckillStoreTicket;
use PromotionsBundle\Services\PromotionSeckillActivityService;

class SeckillActivity extends Controller
{
    use GetDefaultCur;
    use SeckillStoreTicket;

    public function __construct()
    {
        // Built with ShopEx Framework
        $this->service = new PromotionSeckillActivityService();
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/seckillactivity/getlist",
     *     summary="获取秒杀活动列表",
     *     tags={"营销"},
     *     description="获取秒杀活动列表",
     *     operationId="getSeckillList",
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="活动状态", default="valid", type="string"),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型 normal or services",default="normal", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="seckill_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="activity_name", type="integer", example="1"),
     *                     @SWG\Property(property="activity_start_time", type="integer", example="1"),
     *                     @SWG\Property(property="activity_end_time", type="string", example="1"),
     *                     @SWG\Property(property="activity_release_time", type="string", example="1"),
     *                     @SWG\Property(
     *                          property="items",
     *                          type="array",
     *                          @SWG\Items(
     *                               type="object",
     *                               @SWG\Property(property="seckill_id", type="integer"),
     *                               @SWG\Property(property="company_id", type="integer", example="1"),
     *                               @SWG\Property(property="item_title", type="string", example="1"),
     *                               @SWG\Property(property="item_id", type="integer", example="1"),
     *                               @SWG\Property(property="activity_price", type="integer", example="1"),
     *                               @SWG\Property(property="activity_store", type="integer", example="1"),
     *                               @SWG\Property(property="limit", type="integer", example="1"),
     *                          )
     *                     ),
     *                     @SWG\Property(property="is_activity_rebate", type="string", example="1"),
     *                     @SWG\Property(property="ad_pic", type="string", example="1"),
     *                     @SWG\Property(property="is_free_shipping", type="string", example="1"),
     *                     @SWG\Property(property="validity_period", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getSeckillList(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $authUser = $request->get('auth');
        $filter['company_id'] = $authUser['company_id'];
        $filter['item_type'] = $request->input('item_type', 'normal');
        $filter['seckill_type'] = $request->input('seckill_type', 'normal');
        if ($request->input('status') == 'notice') {
            //预告中的秒杀活动
            $filter['activity_release_time|lte'] = time();
            $filter['activity_start_time|gt'] = time();
            $filter['disabled'] = 0;
        } elseif ($request->input('status') == 'valid') {
            //已开始的秒杀活动
            $filter['activity_start_time|lte'] = time();
            $filter['activity_end_time|gt'] = time();
            $filter['disabled'] = 0;
        } else {
            //有效的秒杀活动
            $filter['activity_release_time|lte'] = time();
            $filter['activity_end_time|gt'] = time();
            $filter['disabled'] = 0;
        }

        $orderBy = ['activity_start_time' => 'asc'];
        $result = $this->service->getLists($filter, $page, $pageSize, $orderBy);
        if ($result['list']) {
            $itemIds = [];
            foreach ($result['list'] as $list) {
                $itemIds = array_merge($itemIds, array_column($list['items'], 'item_id'));
            }
            $itemsService = new ItemsService();
            $params['company_id'] = $authUser['company_id'];
            $params['item_id'] = array_unique($itemIds);
            $itemList = $itemsService->getItemListData($params);
            $itemList = array_column($itemList['list'], null, 'item_id');
            foreach ($result['list'] as $key => $list) {
                foreach ($list['items'] as &$item) {
                    if (isset($itemList[$item['item_id']])) {
                        $item = array_merge($item, $itemList[$item['item_id']]);
                    }
                }
                $result['list'][$key] = $list;
            }
        }
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/seckillactivity/getinfo",
     *     summary="获取秒杀活动详情",
     *     tags={"营销"},
     *     description="获取秒杀活动详情",
     *     operationId="getSeckillInfo",
     *     @SWG\Parameter( name="authorizer-appid", in="header", description="小程序appid(小程序访问此参必填)", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="query", description="公司company_id(h5app端必填)", type="integer"),
     *     @SWG\Parameter( name="seckill_id", in="query", description="活动名称", type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="seckill_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="activity_name", type="integer", example="1"),
     *                     @SWG\Property(property="activity_start_time", type="integer", example="1"),
     *                     @SWG\Property(property="activity_end_time", type="string", example="1"),
     *                     @SWG\Property(property="activity_release_time", type="string", example="1"),
     *                     @SWG\Property(
     *                          property="items",
     *                          type="array",
     *                          @SWG\Items(
     *                               type="object",
     *                               @SWG\Property(property="seckill_id", type="integer"),
     *                               @SWG\Property(property="company_id", type="integer", example="1"),
     *                               @SWG\Property(property="item_title", type="string", example="1"),
     *                               @SWG\Property(property="item_id", type="integer", example="1"),
     *                               @SWG\Property(property="activity_price", type="integer", example="1"),
     *                               @SWG\Property(property="activity_store", type="integer", example="1"),
     *                               @SWG\Property(property="limit", type="integer", example="1"),
     *                          )
     *                     ),
     *                     @SWG\Property(property="is_activity_rebate", type="string", example="1"),
     *                     @SWG\Property(property="ad_pic", type="string", example="1"),
     *                     @SWG\Property(property="is_free_shipping", type="string", example="1"),
     *                     @SWG\Property(property="validity_period", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getSeckillInfo(Request $request)
    {
        // Built with ShopEx Framework
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $authUser = $request->get('auth');
        $filter['company_id'] = $authUser['company_id'];
        $filter['seckill_id'] = $request->input('seckill_id');
        $filter['is_show'] = true;
        $result = $this->service->getSeckillInfo($filter, true, null, $page, $pageSize);
        if (isset($result['items'])) {
            $itemIds = array_column($result['items'], 'item_id');
            $itemsService = new ItemsService();
            $params['company_id'] = $authUser['company_id'];
            $params['item_id'] = $itemIds;
            $itemList = $itemsService->getItemListData($params);
            $itemList = array_column($itemList['list'], null, 'item_id');
            foreach ($result['items'] as &$item) {
                if (isset($itemList[$item['item_id']])) {
                    $item = array_merge($item, $itemList[$item['item_id']]);
                }
            }
        }
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/seckillactivity/geticket",
     *     summary="获取到秒杀商品的资格",
     *     tags={"营销"},
     *     description="获取到秒杀商品的资格",
     *     operationId="getSeckillItemTicket",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="seckill_id", in="query", description="活动id", type="integer"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", type="integer"),
     *     @SWG\Parameter( name="num", in="query", description="购买数量", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="ticket", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function getSeckillItemTicket(Request $request)
    {
        $authUser = $request->get('auth');
        $userId = $authUser['user_id'];

        $filter['company_id'] = $authUser['company_id'];
        $filter['seckill_id'] = $request->input('seckill_id');
        $itemId = $request->input('item_id');
        $num = intval($request->input('num', 1));
        $seckillInfo = $this->service->getSeckillInfo($filter, true, $itemId);
        if ($seckillInfo) {
            if ($seckillInfo['limit_num'] > 0 && $seckillInfo['limit_num'] < $num) {
                throw new ResourceException(trans('PromotionsBundle.purchase_quantity_exceeds_limit'));
            }
            switch ($seckillInfo['status']) {
                case "close":
                    throw new ResourceException(trans('PromotionsBundle.seckill_activity_closed'));
                    break;
                case "waiting":
                case "in_the_notice":
                    throw new ResourceException(trans('PromotionsBundle.seckill_activity_not_started'));
                    break;
                case "it_has_ended":
                    throw new ResourceException(trans('PromotionsBundle.seckill_activity_ended'));
                    break;
            }
            $ticket = $this->getTicket($userId, $seckillInfo, $num);
            if ($ticket) {
                return $this->response->array(['ticket' => $ticket]);
            }
            throw new ResourceException(trans('PromotionsBundle.sold_out'));
        }
        throw new ResourceException(trans('PromotionsBundle.seckill_activity_removed'));
    }


    /**
     * @SWG\Delete(
     *     path="/wxapp/promotion/seckillactivity/cancelTicket",
     *     summary="取消会员秒杀资格",
     *     tags={"营销"},
     *     description="取消会员秒杀资格",
     *     operationId="cancelSeckillTicket",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="seckill_ticket", in="query", description="会员秒杀ticket", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="ticket", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function cancelSeckillTicket(Request $request)
    {
        $ticket = $request->input('seckill_ticket');
        $authUser = $request->get('auth');
        $result = $this->cancelTicket($ticket, $authUser['user_id'], $authUser['company_id']);
        return $this->response->array(['status' => $result]);
    }
}
