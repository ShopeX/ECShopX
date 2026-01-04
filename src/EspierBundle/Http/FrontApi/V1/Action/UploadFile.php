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

namespace EspierBundle\Http\FrontApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\UploadFileService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as BaseController;
use EspierBundle\Services\UploadTokenFactoryService;

class UploadFile extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/espier/image_upload_token",
     *     summary="获取上传图片token",
     *     tags={"系统"},
     *     description="获取上传图片token",
     *     operationId="getPicUploadToken",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="filesystem",
     *          in="query",
     *          description="文件系统名称",
     *          required=true,
     *          type="string"
     *      ),
     *     @SWG\Parameter(
     *          name="filename",
     *          in="query",
     *          description="上传文件名称",
     *          required=true,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="group",
     *          in="query",
     *          description="上传文件类型,item 商品，aftersales 售后",
     *          type="string"
     *      ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="token", type="string"),
     *                     @SWG\Property(property="domain", type="string"),
     *                     @SWG\Property(property="region", type="string"),
     *                     @SWG\Property(property="key", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getPicUploadToken(Request $request)
    {
        // fe10e2f6 module
        $user = $request->get('auth');
        $companyId = $user['company_id'];
        $user_id = $user['user_id'];

        $key = $this->getRedisKey($companyId,$user_id);
        $num = env('USER_UPLOAD_IMAGE_NUM',10);
        $redis = app('redis')->connection('members');
        $count =  $redis->get($key);
        if($count >= $num) {
            throw new ResourceException('今日图片上传超过限制');
        }
        $redis->incr($key);
        $redis->expire($key, 3600 * 24);
        $filename = $request->input('filename');
        $group = $request->input('group');
        $filetype = $request->input('filetype');
        $result = UploadTokenFactoryService::create($filetype)->getToken($companyId, $group, $filename);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/espier/image_upload",
     *     summary="上传图片",
     *     tags={"系统"},
     *     description="上传图片",
     *     operationId="uploadOssImage",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="filesystem",
     *          in="query",
     *          description="文件系统名称",
     *          required=true,
     *          type="string"
     *      ),
     *     @SWG\Parameter(
     *          name="filename",
     *          in="query",
     *          description="上传文件名称",
     *          required=true,
     *          type="string"
     *     ),
     *     @SWG\Parameter(
     *          name="group",
     *          in="query",
     *          description="上传文件类型,item 商品，aftersales 售后",
     *          type="string"
     *      ),
     *     @SWG\Parameter(
     *          name="file",
     *          in="query",
     *          description="上传文件File二进制数据",
     *          type="string"
     *      ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="token", type="string"),
     *                     @SWG\Property(property="domain", type="string"),
     *                     @SWG\Property(property="region", type="string"),
     *                     @SWG\Property(property="key", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function uploadOssImage(Request $request)
    {
        $user = $request->get('auth');
        $companyId = $user['company_id'];
        $filename = $request->input('filename');
        $group = $request->input('group');
        $fileObject = $request->file('file');
        if (!$fileObject) {
            throw new ResourceException('请上传文件数据');
        }
        $result = (new UploadFileService())->uploadOss($companyId, 'image', $fileObject, $group, $filename);
        return $this->response->array($result);
    }

    /**
     * 前台上传图片
     * @param Request $request
     * @return mixed
     */
    public function uploadImage(Request $request)
    {
        $authInfo = $request->get('auth');

        $company_id = $authInfo['company_id'];
        $user_id = $authInfo['user_id'];

//        $timestamp = $request->input('timestamp','');
//        $upload_key = $request->input('upload_key','');
//        if(!$timestamp || !$upload_key) {
//            throw new ResourceException('参数异常');
//        }
//        $key = env('USER_UPLOAD_IMAGE_KEY','cd49f7a3ac286ca9acb');
//        $sign = md5($company_id.$user_id.substr(time(),0,8).$key);
//        if($upload_key != $sign){
//            throw new ResourceException('上传参数异常');
//        }
//        if($timestamp > (time() + 120) || $timestamp < (time() - 120)){
//            throw new ResourceException('环境参数异常');
//        }
        $key = $this->getRedisKey($company_id,$user_id);
        $num = env('USER_UPLOAD_IMAGE_NUM',10);
        $redis = app('redis')->connection('members');
        $count =  $redis->get($key);
        if($count >= $num) {
            throw new ResourceException('今日图片上传超过限制');
        }
        $redis->incr($key);
        $redis->expire($key, 3600 * 24);
        if (!$request->hasFile('file')) {
            throw new ResourceException('Upload File Error');
        }
        $file = $request->file('file');
        $filesize = $file->getSize();
        if ($filesize > 1024 * 1024 * 2) {
            throw new ResourceException('图片上传最大为2M');
        }
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])) {
            throw new ResourceException('仅支持jpg，jpeg，png图片的上传');
        }
        $auth = $request->get('auth');
        if (isset($auth['company_id']) && $auth['company_id']) {
            $fileName = $auth['company_id'] . '/' . date('Y/m/d') . '/' . md5(date('YmdHis')) . md5_file($file->getRealPath()) . '.' . $file->getClientOriginalExtension();
        } else {
            $fileName = date('Y/m/d') . '/' . md5(date('YmdHis')) . md5_file($file->getRealPath()) . '.' . $file->getClientOriginalExtension();
        }
        app('filesystem')->disk('import-image')->put($fileName, file_get_contents($file->getRealPath()));
        $result['url'] = app('filesystem')->disk('import-image')->url($fileName);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/uploadeLocalImage",
     *     path="/wxapp/espier/uploadlocal",
     *     summary="上传图片",
     *     tags={"系统"},
     *     description="保存图片",
     *     operationId="uploadeLocalImage",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="images",
     *         in="formData",
     *         description="图片",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="filetype",
     *         in="formData",
     *         description="图片类型",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="group",
     *         in="formData",
     *         description="图片标识",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="newfilename",
     *         in="formData",
     *         description="图片保存路径",
     *         required=false,
     *         type="string"
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
     *                     @SWG\Property(property="image_url", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function uploadeLocalImage(Request $request)
    {
        // fe10e2f6 module
        $filename = $request->file('images');
        if (!$filename->isValid()) {
            throw new ResourceException("请上传有效图片");
        }

        $auth = $request->get('auth');
        $companyId = $auth['company_id'];

        $fileType = $request->input('filetype') ?: 'image';
        $group = $request->input('group');

        $result = UploadTokenFactoryService::create($fileType)->uploadeImage($companyId, $group, $filename);
        $return = ['image_url' => $result];
        return $this->response->array($return);
    }

    private function getRedisKey($company_id,$user_id)
    {
        return 'uploadImageNum:'.$company_id.':'.$user_id;
    }
}
