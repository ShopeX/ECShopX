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

namespace KaquanBundle\Http\FrontApi\V2\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use KaquanBundle\Services\VipGradeService;

use CompanysBundle\Traits\GetDefaultCur;

class VipGradeController extends BaseController
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/vipgrades/newlist",
     *     summary="获取付费会员等级卡列表",
     *     tags={"卡券"},
     *     description="获取付费会员等级卡列表",
     *     operationId="listDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                          @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型"),
     *                          @SWG\Property( property="default_grade", type="string", example="false", description="是否默认等级"),
     *                          @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     *                          @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="会员卡背景图"),
     *                          @SWG\Property( property="description", type="string", example="1、VIP 2、整场促销3、畅想优惠 4、详细说明", description="简单介绍"),
     *                          @SWG\Property( property="price_list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="name", type="string", example="monthly", description="名称"),
     *                                  @SWG\Property( property="price", type="string", example="0.01", description="价格"),
     *                                  @SWG\Property( property="day", type="string", example="30", description="生日日期"),
     *                                  @SWG\Property( property="desc", type="string", example="30天", description="描述"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="privileges", type="object",
     *                                  @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *                                  @SWG\Property( property="discount_desc", type="string", example="8", description="描述"),
     *                          ),
     *                          @SWG\Property( property="created", type="string", example="1560947408", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1560947408", description="修改时间"),
     *                          @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否是默认"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="cur", type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="currency", type="string", example="CNY", description="货币类型"),
     *                          @SWG\Property( property="title", type="string", example="中国人民币", description="货币描述"),
     *                          @SWG\Property( property="symbol", type="string", example="￥", description="货币符号"),
     *                          @SWG\Property( property="rate", type="string", example="1", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币"),
     *                          @SWG\Property( property="use_platform", type="string", example="normal", description="适用端。可选值为 service,normal"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function listDataVipGrade(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['is_disabled'] = false;
        $vipGradeService = new VipGradeService();
        $gradelist = $vipGradeService->lists($filter);
        if (!$gradelist) {
            $result['list'] = $gradelist;
            $result['cur'] = $this->getCur($filter['company_id']);
            return $this->response->array($result);
        }

        foreach ($gradelist as &$list) {
            if (isset($list['price_list']) && $list['price_list']) {
                $pricelist = [];
                foreach ($list['price_list'] as $k => $price_list) {
                    if ($price_list['price']) {
                        $pricelist[] = $price_list;
                    }
                }
                $list['price_list'] = $pricelist;
            }
        }
        $result['list'] = $gradelist;

        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/vipgrades/info",
     *     summary="获取付费会员等级卡详情(废弃)",
     *     tags={"卡券"},
     *     description="获取付费会员等级卡详情(废弃)",
     *     operationId="infoDataVipGrade",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="vip_grade_id", in="query", description="id", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function infoDataVipGrade(Request $request)
    {
        $user = $request->get('auth');
        $filter['company_id'] = $user['company_id'];
        $filter['vip_grade_id'] = $request->input('vip_grade_id');
        $vipGradeService = new VipGradeService();
        $result = $vipGradeService->getInfo($filter);
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }
}
