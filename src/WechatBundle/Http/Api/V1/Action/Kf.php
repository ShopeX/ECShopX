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
use WechatBundle\Services\Kf as KfServices;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;
use Dingo\Api\Exception\StoreResourceFailedException;

class Kf extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wechat/kfs",
     *     summary="添加微信客服",
     *     tags={"微信"},
     *     description="添加微信客服，需要微信公众号开通客服功能",
     *     operationId="createWechatKf",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="wx_name", in="query", description="微信号", required=true, type="string"),
     *     @SWG\Parameter( name="nick", in="query", description="客服昵称", required=true, type="string"),
     *     @SWG\Parameter( name="avatar", in="formData", description="客服头像", type="file"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function createWechatKf(Request $request)
    {
        $service = new KfServices();

        $path = '';
        if ($avatar = $request->file('avatar')) {
            if ($request->file('avatar')->getSize() <= 0) {
                throw new StoreResourceFailedException('上传头像失败');
            }
            $oldpath = $avatar->getPathname();
            $path = $oldpath.'.'.$avatar->getClientOriginalExtension();
            copy($oldpath, $path);
        }

        $service->create($request->input('wx_name'), $request->input('nick'), $path);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/kfs",
     *     summary="获取所有客服账号列表",
     *     tags={"微信"},
     *     description="获取所有客服账号列表",
     *     operationId="lists",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="kf_account", type="string", example="kf2001@yykdemo"),
     *                          @SWG\Property( property="kf_headimgurl", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkreIDa0jkEfxACA10akAibgiaMUbQcFmFlAhabPia0DeQyiaOsCpwoia3moZYwnj1jCRNVIM8ggiaXXuhKiaw/300?wx_fmt=jpeg"),
     *                          @SWG\Property( property="kf_id", type="string", example="2001"),
     *                          @SWG\Property( property="kf_nick", type="string", example="danny"),
     *                          @SWG\Property( property="kf_wx", type="string", example="yunrui83"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        $service = new KfServices();
        $list = $service->lists();
        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/update/kfs",
     *     summary="修改指定微信客服昵称或头像",
     *     tags={"微信"},
     *     description="修改指定微信客服昵称或头像",
     *     operationId="updateWechatKf",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="account", in="query", description="客服账号", required=true, type="string"),
     *     @SWG\Parameter( name="nick", in="query", description="修改的客服昵称", type="string"),
     *     @SWG\Parameter( name="avatar", in="formData", description="修改的客服头像", type="file"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="stirng")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function updateWechatKf(Request $request)
    {
        $service = new KfServices();
        $account = $request->input('account');
        if (!$account) {
            throw new UpdateResourceFailedException('更改的账号必填');
        }
        $data = [];
        if ($request->input('nick')) {
            $data['nick'] = $request->input('nick');
        }
        if ($avatar = $request->file('avatar')) {
            if ($request->file('avatar')->getSize() <= 0) {
                throw new StoreResourceFailedException('更新头像失败');
            }
            $oldpath = $avatar->getPathname();
            $path = $oldpath.'.'.$avatar->getClientOriginalExtension();
            copy($oldpath, $path);
            $data['avatarPath'] = $path;
        }

        if (!$data) {
            throw new UpdateResourceFailedException('请填写需要修改的昵称或头像');
        }

        $service->update($account, $data);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/wechat/kfs",
     *     summary="删除指定微信客服",
     *     tags={"微信"},
     *     description="删除指定微信客服",
     *     operationId="updateWechatKf",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="account",
     *         in="query",
     *         description="客服账号",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function deleteWechatKf(Request $request)
    {
        $service = new KfServices();
        $account = $request->input('account');
        if (!$account) {
            throw new DeleteResourceFailedException('更改的账号必填');
        }
        $service->delete($account);
        return $this->response->array(['status' => true]);
    }
}
