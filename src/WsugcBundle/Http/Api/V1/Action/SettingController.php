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

namespace WsugcBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use WsugcBundle\Services\SettingService;
use Swagger\Annotations as SWG;

class SettingController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ugc/setting/point/saveSetting",
     *     summary="积分/视频/内容检查 设置",
     *     tags={"UGC设置"},
     *     description="积分/视频/内容检查/官方账号 设置",
     *     operationId="savePointSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="formData", description="类型，比如point。默认传point。类型有point 积分设置,video 是否允许前台上传视频,contentCheck 内容检查 official 官方账号", required=true, type="string"),
     *     @SWG\Parameter( name="setting", in="formData", description="设置json字符串。{”point_enable”:1,//全局开启1开启，0关闭,”point_max_day”:100,//每位会员每日最多,”point_post_like_get_once”:5,//点赞每次赠送积分”point_post_like_get_max_times_day”:5,//点赞每日最多次数”point_post_comment_get_once”:5,//评论每次赠送积分”point_post_comment_get_max_times_day”:5,//评论每日最多次数”point_post_share_get_once”:5,//分享每次赠送积分”point_post_share_get_max_times_day”:5//分享每日最多次数”point_post_favorite_get_once”:5,//收藏笔记每次赠送积分”point_post_favorite_get_max_times_day”:5,//收藏笔记每日最多次数”point_post_create_get_once”:5,//发布笔记每次赠送积分”point_post_create_get_max_times_day”:5,//发布笔记每日最多次数}  前台是否允许上传视频设置 ｛”video_enable”:1｝ 内容检查：｛“contentCheck_enable“:1,//是否启用，后台加一个开关吧。“contentCheck_url“:““,“contentCheck_appid“:““,“contentCheck_appsecret“:““,｝ 官方账号设置 “official.nickname“:“md”,“official.headerimgurl“:“头像“｝", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="comment_id", type="string", example="49", description="记录id"),
     *                  @SWG\Property( property="activity_id", type="string", example="36", description="活动ID"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function savePointSetting(Request $request)
    {
        $id          = $request->input('id', '');
        $company_id  = app('auth')->user()->get('company_id');
        $setting     = $request->input('setting', '');
        $type        = $request->input('type', '');
        $service = new SettingService();
        $setting=json_decode($setting,true);
        foreach($setting as $k=>$v){
            $params=[];
            $params = [
                //'id'                 => $id,
                'company_id'=> $company_id,
                'type'=>  $type??'point',
                'keyname'=>$k,
                'value'=>$v,
            ];
            $exist=$service->getInfo(['company_id' => $company_id, 'type'=>$params['type'],'keyname'=>$k]);
            if($exist['id']??null){
                //已存在，更新
                $result = $service->saveData($params,['id'=>$exist['id']]);
            }
            else{
                //新增
                $result = $service->saveData($params);
            }
            //保存到redis
            if($result){
                $service->saveSettingToRedis($company_id, $k,$v);
             
            }
        }
        $msg='';

        if($type=='official'){
            $msg='官方账号设置成功';
        }
        else if($type=='point'){
            //
            if($setting['point_enable']==1){
                $msg='积分行为已开启';
            }
            else{
                $msg='积分行为已关闭';
            }
        }
        else if($type=='video'){
            //
            if($setting['video_enable']==1){
                $msg='会员视频上传已开启';
            }
            else{
                $msg='会员视频上传已关闭';
            }
        }
        else if($type=='contentCheck'){
            //
            if($setting['contentCheck_enable']==1){
                $msg='第三方审核对接已开启';
            }
            else{
                $msg='第三方审核对接已关闭';
            }
        }
        $result['message'] = ($msg!='' ? $msg:'保存设置成功');
        return $this->response->array($result);
    }
    
    /**
     * @SWG\Get(
     *     path="/ugc/setting/point/getSetting",
     *     summary="获取 积分/视频/内容检查 设置",
     *     tags={"UGC设置"},
     *     description="获取 积分/视频/内容检查 设置",
     *     operationId="getPointSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="type", in="query", description="类型，比如point。默认传point", required=true, type="string"),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespone") ) )
     * )
     */
    public function getPointSetting(Request $request)
    {
        // U2hvcEV4 framework
        $company_id  = app('auth')->user()->get('company_id');
        $filter['company_id'] = $company_id;
        $type = $request->input('type', '');
        $filter['type']=$type;
        $service = new SettingService();
        $result  = $service->getSettingList($filter,'*');
        $setting=[];
        if (empty($result)) {
            return $this->response->array($setting);
        } else {
            if($result['list']??null){
                foreach($result['list'] as $k=>$v){
                    $setting[$v['keyname']]=$v['value'];
                }
            }
            return $this->response->array($setting);
        }
    }
}