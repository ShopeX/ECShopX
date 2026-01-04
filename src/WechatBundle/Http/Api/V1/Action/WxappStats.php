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

namespace WechatBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use WechatBundle\Services\Wxapp\StatsService as WxappStatsService;

class WxappStats extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxa/stats/summarybydate",
     *     summary="某天概况趋势",
     *     tags={"微信"},
     *     description="查询指定天的概况信息，包括打开次数，访问次数，新访问用户，分享次数",
     *     operationId="getSummaryByDate",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="date", in="query", description="日期", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="ref_date", type="string", example="20210203"),
     *                  @SWG\Property( property="session_cnt", type="string", example="3"),
     *                  @SWG\Property( property="visit_pv", type="string", example="8"),
     *                  @SWG\Property( property="visit_uv", type="string", example="2"),
     *                  @SWG\Property( property="visit_uv_new", type="string", example="0"),
     *                  @SWG\Property( property="stay_time_uv", type="string", example="19.5"),
     *                  @SWG\Property( property="stay_time_session", type="string", example="13"),
     *                  @SWG\Property( property="visit_depth", type="string", example="2"),
     *                  @SWG\Property( property="visit_total", type="string", example="2051"),
     *                  @SWG\Property( property="share_pv", type="string", example="0"),
     *                  @SWG\Property( property="share_uv", type="string", example="0"),
     *                  @SWG\Property( property="session_cnt_dayRate", type="string", example="-57.14"),
     *                  @SWG\Property( property="session_cnt_weekRate", type="string", example="-66.67"),
     *                  @SWG\Property( property="session_cnt_monthRate", type="string", example="0"),
     *                  @SWG\Property( property="visit_pv_dayRate", type="string", example="-87.69"),
     *                  @SWG\Property( property="visit_pv_weekRate", type="string", example="-87.1"),
     *                  @SWG\Property( property="visit_pv_monthRate", type="string", example="-42.86"),
     *                  @SWG\Property( property="visit_uv_dayRate", type="string", example="-33.33"),
     *                  @SWG\Property( property="visit_uv_weekRate", type="string", example="-50"),
     *                  @SWG\Property( property="visit_uv_monthRate", type="string", example="-33.33"),
     *                  @SWG\Property( property="visit_uv_new_dayRate", type="string", example=""),
     *                  @SWG\Property( property="visit_uv_new_weekRate", type="string", example="-100"),
     *                  @SWG\Property( property="visit_uv_new_monthRate", type="string", example="-100"),
     *                  @SWG\Property( property="share_pv_dayRate", type="string", example=""),
     *                  @SWG\Property( property="share_pv_weekRate", type="string", example="-100"),
     *                  @SWG\Property( property="share_pv_monthRate", type="string", example=""),
     *                  @SWG\Property( property="share_uv_dayRate", type="string", example=""),
     *                  @SWG\Property( property="share_uv_weekRate", type="string", example="-100"),
     *                  @SWG\Property( property="share_uv_monthRate", type="string", example=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getSummaryByDate(Request $request)
    {
        $yesterday = date("Ymd", strtotime("-1 day"));
        $wxaAppId = $request->input('wxaAppId');
        $date = $request->input('date', $yesterday);
        if (!$date) {
            $date = $yesterday;
        }
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);

            $result = $weappService->getSummaryByDate($date);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/summarytrend",
     *     summary="小程序概况趋势",
     *     tags={"微信"},
     *     description="小程序概况趋势，限定查询1天数据,概况中提供累计用户数等部分指标数据",
     *     operationId="getSummaryTrend",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="ref_date", type="string", example="2021-02-03"),
     *                  @SWG\Property( property="visit_total", type="string", example="2051"),
     *                  @SWG\Property( property="share_pv", type="string", example="0"),
     *                  @SWG\Property( property="share_uv", type="string", example="0"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getSummaryTrend(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);
            $result = $weappService->getSummarytrend($type);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/visitpage",
     *     summary="小程序访问页面",
     *     tags={"微信"},
     *     description="小程序访问页面，限定查询1天数据",
     *     operationId="getVisitPage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="ref_date", type="string", example="20210203"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="page_path", type="string", example="pages/index"),
     *                          @SWG\Property( property="page_visit_pv", type="string", example="4"),
     *                          @SWG\Property( property="page_visit_uv", type="string", example="2"),
     *                          @SWG\Property( property="page_staytime_pv", type="string", example="5.5"),
     *                          @SWG\Property( property="entrypage_pv", type="string", example="3"),
     *                          @SWG\Property( property="exitpage_pv", type="string", example="1"),
     *                          @SWG\Property( property="page_share_pv", type="string", example="0"),
     *                          @SWG\Property( property="page_share_uv", type="string", example="0"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total", type="object",
     *                          @SWG\Property( property="entrypage_pv", type="string", example="6"),
     *                          @SWG\Property( property="exitpage_pv", type="string", example="2"),
     *                          @SWG\Property( property="page_visit_pv", type="string", example="8"),
     *                          @SWG\Property( property="page_visit_uv", type="string", example="5"),
     *                          @SWG\Property( property="page_staytime_pv", type="string", example="16.5"),
     *                          @SWG\Property( property="page_share_pv", type="string", example="0"),
     *                          @SWG\Property( property="page_share_uv", type="string", example="0"),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getVisitPage(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);

            $result = $weappService->getVisitpage($type);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/visittrend",
     *     summary="小程序访问趋势",
     *     tags={"微信"},
     *     description="小程序访问趋势",
     *     operationId="getVisitTrend",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="ref_date", type="string", example="2021-02-03"),
     *                  @SWG\Property( property="session_cnt", type="string", example="3"),
     *                  @SWG\Property( property="visit_pv", type="string", example="8"),
     *                  @SWG\Property( property="visit_uv", type="string", example="2"),
     *                  @SWG\Property( property="visit_uv_new", type="string", example="0"),
     *                  @SWG\Property( property="stay_time_uv", type="string", example="19.5"),
     *                  @SWG\Property( property="stay_time_session", type="string", example="13"),
     *                  @SWG\Property( property="visit_depth", type="string", example="2"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getVisitTrend(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);

            $result = $weappService->getVisitTrend($type);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/visitdistribution",
     *     summary="小程序访问分布",
     *     tags={"微信"},
     *     description="小程序访问分布，限定查询1天数据",
     *     operationId="getVisitDistribution",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getVisitDistribution(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);

            $result = $weappService->getVisitDistribution($type);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/retaininfo",
     *     summary="小程序访问留存",
     *     tags={"微信"},
     *     description="小程序访问留存",
     *     operationId="getRetaininfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getRetaininfo(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        $weappService = new WxappStatsService($wxaAppId);

        $result = $weappService->getRetainInfo($type);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxa/stats/userportrait",
     *     summary="小程序用户画像",
     *     tags={"微信"},
     *     description="获取小程序新增或活跃用户的画像分布数据。时间范围支持昨天、最近7天、最近30天",
     *     operationId="getUserPortrait",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wxaAppId", in="query", description="授权小程序appid", required=true, type="string"),
     *     @SWG\Parameter( name="queryType", in="query", description="查询日期类型，如：yesterday, weekly, monthly", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="ref_date", type="string", example="20210203"),
     *                  @SWG\Property( property="visit_uv_new", type="object",
     *                          @SWG\Property( property="province", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="city", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="genders", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="platforms", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="devices", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="ages", type="array",
     *                              @SWG\Items( type="string", example="undefined"),
     *                          ),
     *                          @SWG\Property( property="total", type="object",
     *                                  @SWG\Property( property="genders", type="string", example="0"),
     *                                  @SWG\Property( property="province", type="string", example="0"),
     *                                  @SWG\Property( property="city", type="string", example="0"),
     *                                  @SWG\Property( property="platforms", type="string", example="0"),
     *                                  @SWG\Property( property="devices", type="string", example="0"),
     *                                  @SWG\Property( property="ages", type="string", example="0"),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="visit_uv", type="object",
     *                          @SWG\Property( property="province", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="11"),
     *                                  @SWG\Property( property="name", type="string", example="上海"),
     *                                  @SWG\Property( property="value", type="string", example="1"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="city", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="11"),
     *                                  @SWG\Property( property="name", type="string", example="上海"),
     *                                  @SWG\Property( property="value", type="string", example="1"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="genders", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="1"),
     *                                  @SWG\Property( property="name", type="string", example="男"),
     *                                  @SWG\Property( property="value", type="string", example="1"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="platforms", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="1"),
     *                                  @SWG\Property( property="name", type="string", example="iPhone"),
     *                                  @SWG\Property( property="value", type="string", example="2"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="devices", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="name", type="string", example="苹果 IPHONE XS MAX"),
     *                                  @SWG\Property( property="value", type="string", example="1"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="ages", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="3"),
     *                                  @SWG\Property( property="name", type="string", example="25-29岁"),
     *                                  @SWG\Property( property="value", type="string", example="2"),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="total", type="object",
     *                                  @SWG\Property( property="genders", type="string", example="2"),
     *                                  @SWG\Property( property="province", type="string", example="2"),
     *                                  @SWG\Property( property="city", type="string", example="2"),
     *                                  @SWG\Property( property="platforms", type="string", example="2"),
     *                                  @SWG\Property( property="devices", type="string", example="1"),
     *                                  @SWG\Property( property="ages", type="string", example="2"),
     *                          ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getUserPortrait(Request $request)
    {
        $wxaAppId = $request->input('wxaAppId');
        if (!$wxaAppId) {
            return $this->response->error('wxappid 必填');
        }
        $type = $request->input('queryType', 'yesterday');
        if (!$type) {
            $type = 'yesterday';
        }
        try {
            $weappService = new WxappStatsService($wxaAppId);

            $result = $weappService->getUserPortrait($type);
            return $this->response->array($result);
        } catch (\Exception $e) {
            $errcode = $e->getCode();
            if ($errcode == -1) {
                return $this->response->error('微信系统繁忙, 请稍后重试', 500);
            } else {
                return $this->response->error($e->getMessage(), $errcode);
            }
        }
    }
}
