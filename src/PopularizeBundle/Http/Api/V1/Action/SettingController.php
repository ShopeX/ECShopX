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

namespace PopularizeBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PointBundle\Services\PointMemberRuleService;
use PopularizeBundle\Services\SettingService;
use PopularizeBundle\Services\PromoterGradeService;

class SettingController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/popularize/config",
     *     summary="获取分销配置信息",
     *     tags={"分销推广"},
     *     description="获取分销配置信息",
     *     operationId="config",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="limit_rebate", type="string", example="1", description=""),
     *               @SWG\Property(property="limit_time", type="string", example="0", description=""),
     *               @SWG\Property(property="isOpenGuide", type="string", example="false", description=""),
     *               @SWG\Property(property="isOpenShop", type="string", example="true", description=""),
     *               @SWG\Property(property="isOpenRecharge", type="string", example="false", description=""),
     *               @SWG\Property(property="goods", type="string", example="all", description=""),
     *               @SWG\Property(property="banner_img", type="string", example="", description=""),
     *               @SWG\Property(property="custompage_template_id", type="string", example="0", description=""),
     *               @SWG\Property(property="commission_type", type="string", example="money", description=""),
     *               @SWG\Property(property="change_promoter", type="object", description="",
     *                   @SWG\Property(property="type", type="string", example="no_threshold", description=""),
     *                   @SWG\Property(property="filter", type="object", description="",
     *                           @SWG\Property(property="no_threshold", type="string", example="0", description=""),
     *                           @SWG\Property(property="vip_grade", type="string", example="vip", description=""),
     *                           @SWG\Property(property="consume_money", type="string", example="0", description=""),
     *                           @SWG\Property(property="order_num", type="string", example="0", description=""),
     *                  ),
     *              ),
     *              @SWG\Property(property="popularize_ratio", type="object", description="",
     *                   @SWG\Property(property="type", type="string", example="order_money", description=""),
     *                   @SWG\Property(property="profit", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                              @SWG\Property(property="ratio", type="string", example="10", description=""),
     *                              @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                              @SWG\Property(property="ratio", type="string", example="10", description=""),
     *                              @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *                  @SWG\Property(property="order_money", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="20", description=""),
     *                               @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="20", description=""),
     *                               @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *              ),
     *              @SWG\Property(property="recharge", type="object", description="",
     *                  @SWG\Property(property="profit", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="0", description=""),
     *                               @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="0", description=""),
     *                               @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *              ),
     *               @SWG\Property(property="isOpenPopularize", type="string", example="true", description=""),
     *               @SWG\Property(property="is_open_wechat", type="string", example="false", description=""),
     *               @SWG\Property(property="is_open_point", type="string", example="false", description="是否已打开积分设置 true:已打开；false:未打开；"),
     *               @SWG\Property(property="qrcode_bg_img", type="string", example="false", description="二维码分享背景图"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getConfig(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $companyId = app('auth')->user()->get('company_id');
        $user = app('auth')->user();
        $operator_type = $user->get('operator_type');
         //authInfo
        $settingService = new SettingService();
        $config = $settingService->getConfig($companyId);
        $pathSource = $request->get('pathSource',false);
        if($operator_type == 'distributor' 
        or $operator_type == 'merchant'
        or $pathSource == '/sellers/popularize/popularizegoods'
        ){
            $config['popularize_ratio']['profit']['first_level']['name'] = '业绩';
            unset($config['popularize_ratio']['profit']['second_level']);
            $config['popularize_ratio']['order_money']['first_level']['name'] = '业绩';
            unset($config['popularize_ratio']['order_money']['second_level']);
        }

        

        $config['isOpenPopularize'] = $settingService->getOpenPopularize($companyId);

        $config['banner_img'] = $config['banner_img'] ?? config('common.distribution_default_banner');
        $config['banner_img'] = $config['banner_img'] ? $config['banner_img'] : config('common.distribution_default_banner');

        $config['applets_share_img'] = $config['applets_share_img'] ?? config('common.distribution_default_weapp');
        $config['applets_share_img'] = $config['applets_share_img'] ? $config['applets_share_img'] : config('common.distribution_default_weapp');

        $config['h5_share_img'] = $config['h5_share_img'] ?? config('common.distribution_default_poster');
        $config['qrcode_bg_img'] = $config['qrcode_bg_img'] ?? config('common.qrcode_bg_img');
        $config['promoter_qrcode_bg_img'] = $config['promoter_qrcode_bg_img'] ?? config('common.promoter_qrcode_bg_img');//分销员推广二维码背景图（仅内部可推广时使用）

        $config['is_open_point'] = (new PointMemberRuleService($companyId))->getIsOpenPoint();
        // 开启身份
        $config['internalOpenIdentity'] = ($config['internalOpenIdentity'] ?? 'false' ) == 'true' ? true : false;

        return $this->response->array($config);
    }

    /**
     * @SWG\Post(
     *     path="/popularize/config",
     *     summary="设置分销配置信息",
     *     tags={"分销推广"},
     *     description="设置分销配置信息",
     *     operationId="config",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="limit_rebate", in="formData", description="限制回扣", type="string"),
     *     @SWG\Parameter( name="limit_time", in="formData", description="限时", type="string"),
     *     @SWG\Parameter( name="isOpenGuide", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="isOpenShop", in="formData", description="是否开启推广员小店", type="string"),
     *     @SWG\Parameter( name="isOpenRecharge", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="goods", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="banner_img", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="qrcode_bg_img", in="formData", description="分享二维码背景图片", type="string"),
     *     @SWG\Parameter( name="custompage_template_id", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="commission_type", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="change_promoter[type]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="change_promoter[filter][no_threshold]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="change_promoter[filter][vip_grade]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="change_promoter[filter][consume_money]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="change_promoter[filter][order_num]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[type]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[profit][first_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[profit][first_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[profit][second_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[profit][second_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[order_money][first_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[order_money][first_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[order_money][second_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="popularize_ratio[order_money][second_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="recharge[profit][first_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="recharge[profit][first_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="recharge[profit][second_level][ratio]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="recharge[profit][second_level][name]", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="isOpenPopularize", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="is_open_wechat", in="formData", description="", type="string"),
     *     @SWG\Parameter( name="isOpenPromoterInformation", in="formData", description="是否开启推广员信息", type="string"),
     *     @SWG\Parameter( name="shop_img", in="formData", description="小店图片", type="string"),
     *     @SWG\Parameter( name="share_title", in="formData", description="分享标题", type="string"),
     *     @SWG\Parameter( name="share_des", in="formData", description="分享描述", type="string"),
     *     @SWG\Parameter( name="applets_share_img", in="formData", description="小程序分享图片", type="string"),
     *     @SWG\Parameter( name="h5_share_img", in="formData", description="h5/app/海报 分享图片", type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="limit_rebate", type="string", example="1", description=""),
     *               @SWG\Property(property="limit_time", type="string", example="0", description=""),
     *               @SWG\Property(property="isOpenGuide", type="string", example="false", description=""),
     *               @SWG\Property(property="isOpenShop", type="string", example="true", description=""),
     *               @SWG\Property(property="isOpenRecharge", type="string", example="false", description=""),
     *               @SWG\Property(property="goods", type="string", example="all", description=""),
     *               @SWG\Property(property="banner_img", type="string", example="", description=""),
     *               @SWG\Property(property="custompage_template_id", type="string", example="0", description=""),
     *               @SWG\Property(property="commission_type", type="string", example="money", description=""),
     *               @SWG\Property(property="change_promoter", type="object", description="",
     *                   @SWG\Property(property="type", type="string", example="no_threshold", description=""),
     *                   @SWG\Property(property="filter", type="object", description="",
     *                           @SWG\Property(property="no_threshold", type="string", example="0", description=""),
     *                           @SWG\Property(property="vip_grade", type="string", example="vip", description=""),
     *                           @SWG\Property(property="consume_money", type="string", example="0", description=""),
     *                           @SWG\Property(property="order_num", type="string", example="0", description=""),
     *                  ),
     *              ),
     *              @SWG\Property(property="popularize_ratio", type="object", description="",
     *                   @SWG\Property(property="type", type="string", example="order_money", description=""),
     *                   @SWG\Property(property="profit", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                              @SWG\Property(property="ratio", type="string", example="10", description=""),
     *                              @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                              @SWG\Property(property="ratio", type="string", example="10", description=""),
     *                              @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *                  @SWG\Property(property="order_money", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="20", description=""),
     *                               @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="20", description=""),
     *                               @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *              ),
     *              @SWG\Property(property="recharge", type="object", description="",
     *                  @SWG\Property(property="profit", type="object", description="",
     *                          @SWG\Property(property="first_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="0", description=""),
     *                               @SWG\Property(property="name", type="string", example="上级", description=""),
     *                          ),
     *                          @SWG\Property(property="second_level", type="object", description="",
     *                               @SWG\Property(property="ratio", type="string", example="0", description=""),
     *                               @SWG\Property(property="name", type="string", example="上上级", description=""),
     *                          ),
     *                  ),
     *              ),
     *               @SWG\Property(property="isOpenPopularize", type="string", example="true", description=""),
     *               @SWG\Property(property="is_open_wechat", type="string", example="false", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function setConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $settingService = new SettingService();
        $isOpenPopularize = $request->input('isOpenPopularize', false);
        $settingService->openPopularize($companyId, $isOpenPopularize);

        $data = $request->input();
        $settingService->setConfig($companyId, $data);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/popularize/promoter/config",
     *     summary="获取推广员等级",
     *     tags={"分销推广"},
     *     description="获取推广员等级",
     *     operationId="getPromoterGradeConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(
     *               property="data",
     *               type="object",
     *                 @SWG\Property(property="isOpenPromoterGrade", type="string", example="false", description=""),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function getPromoterGradeConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new PromoterGradeService();
        $config = $service->getPromoterGradeConfig($companyId);

        $config['isOpenPromoterGrade'] = $service->getOpenPromoterGrade($companyId);
        return $this->response->array($config);
    }

    /**
     * @SWG\Post(
     *     path="/popularize/promoter/config",
     *     summary="设置推广员等级",
     *     tags={"分销推广"},
     *     description="设置推广员等级",
     *     operationId="setPromoterGradeConfig",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromoterErrorRespones") ) )
     * )
     */
    public function setPromoterGradeConfig(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $service = new PromoterGradeService();
        $isOpenPromoterGrade = $request->input('isOpenPromoterGrade', false);
        $service->openPromoterGrade($companyId, $isOpenPromoterGrade);

        $data = $request->input();
        $service->setPromoterGradeConfig($companyId, $data);

        return $this->response->array(['status' => true]);
    }
}
