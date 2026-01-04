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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use EspierBundle\Services\UploadImageService;
use EspierBundle\Services\UploadTokenFactoryService;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;

class Image extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.image.upload_token",
     *     summary="获取上云存储上传TOKE",
     *     tags={"图片"},
     *     description="获取上云存储上传TOKE",
     *     operationId="getUploadToken",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.image.upload_token" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="0" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="filetype", description="上传文件类型,支持 file,image,viodes" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="driver", type="string", example="qiniu", description=""),
     *                  @SWG\Property( property="token", type="object",
     *                          @SWG\Property( property="host", type="string", example="https://upload-z2.qiniup.com", description=""),
     *                          @SWG\Property( property="token", type="string", example="wTHrpmk-bryNH0=", description=""),
     *                          @SWG\Property( property="domain", type="string", example="http://test.test.com/", description="合法域名配置"),
     *                          @SWG\Property( property="region", type="string", example="z2", description=""),
     *                          @SWG\Property( property="key", type="string", example="image/1/2021/02/05/cab8a95e46b192e6d8fe5efce942e4468srF7OftNlqtbNYplYuK6uHY6CJK63zz", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones") ) )
     * )
     */
    public function getUploadToken(Request $request)
    {
        // Powered by ShopEx EcShopX
        $companyId = $request->get('auth')['company_id'];

        $fileType = $request->input('filetype');
        $group = $request->input('group');
        $filename = $request->input('filename');
        $result = UploadTokenFactoryService::create($fileType)->getToken($companyId, $group, $filename);
        $this->api_response('true', "ok", $result, 'E0000');
    }

    /**
     * @SWG\Post(
     *     path="/ecx.image.upload_localimage",
     *     summary="本地存储上传图片",
     *     tags={"图片"},
     *     description="保存图片",
     *     operationId="uploadeImage",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.image.upload_localimage" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="0" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( name="images", in="query", description="图片", required=true, type="string"),
     *     @SWG\Parameter( name="filetype", in="query", description="图片类型", required=true, type="string"),
     *     @SWG\Parameter( name="group", in="query", description="图片标识", required=false, type="string"),
     *     @SWG\Parameter( name="newfilename", in="query", description="图片保存路径", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="image_url", type="string", example="", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones") ) )
     * )
     */
    public function uploadeImage(Request $request)
    {
        $filename = $request->file('images');
        if (!$filename->isValid()) {
            $this->api_response('fail', "请上传有效图片", null, 'E0001');
        }

        $companyId = $request->get('auth')['company_id'];

        $fileType = $request->input('filetype') ?: 'image';
        $group = $request->input('group');

        $result = UploadTokenFactoryService::create($fileType)->uploadeImage($companyId, $group, $filename);
        $return = ['image_url' => $result];
        $this->api_response('true', "ok", $return, 'E0000');
    }



    /**
     * @SWG\Get(
     *     path="/ecx.image.list",
     *     summary="获取图片列表",
     *     tags={"图片"},
     *     description="获取图片列表",
     *     operationId="getImageList",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.image.list" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="0" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer", ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer", ),
     *     @SWG\Parameter( name="image_cat_id", in="query", description="图片分类id", type="string", ),
     *     @SWG\Parameter( name="image_name", in="query", description="图片名称", type="string", ),
     *     @SWG\Parameter( name="storage", in="query", description="存储引擎", type="string", ),
     *     @SWG\Parameter( name="image_url", in="query", description="图片标识", type="string", ),
     *     @SWG\Parameter( name="disabled", in="query", description="图片是否失效", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="360", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="image_id", type="string", example="2884", description="图片id"),
     *                          @SWG\Property( property="image_name", type="string", example="WX20200319-164136@2x.png", description="图片名称"),
     *                          @SWG\Property( property="image_type", type="string", example="image/png", description="图片类型"),
     *                          @SWG\Property( property="image_url", type="string", example="image/1/2021/02/05/cab8a95e46b192e6d8fe5efce942e4468", description="元素配图"),
     *                          @SWG\Property( property="creat_time", type="string", example="1612516928", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones") ) )
     * )
     */
    public function getImageList(Request $request)
    {
        $params = $request->all('storage', 'image_name', 'disabled', 'image_cat_id');
        $filter['storage'] = $params['storage'] ?: 'image';
        $filter['company_id'] = $request->get('auth')['company_id'];


        if ($params['image_cat_id']) {
            $filter['image_cat_id'] = $params['image_cat_id'];
        }

        $filter['distributor_id'] = $request->input('distributor_id', 0);

        if ($params['image_name']) {
            $filter['image_name|contains'] = $params['image_name'];
        }
        if ($request->input('disabled')) {
            $filter['disabled'] = $params['disabled'];
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->getImagesBy($filter, $page, $pageSize);
        $return['list'] = [];
        $return['total_count'] = $result['total_count'];
        foreach ($result['list'] as $key => $val) {
            $return['list'][$key]['image_id'] = $val['image_id'];
            $return['list'][$key]['image_name'] = $val['image_name'];
            $return['list'][$key]['image_type'] = $val['image_type'];
            $return['list'][$key]['image_url'] = $val['image_full_url'];
            $return['list'][$key]['create_time'] = $val['created'];
        }

        $this->api_response('true', "ok", $return, 'E0000');
    }


    /**
     * @SWG\Post(
     *     path="/ecx.image.save",
     *     summary="保存图片",
     *     tags={"图片"},
     *     description="保存图片",
     *     operationId="saveImage",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.image.save" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="0" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( name="image_cat_id", in="query", description="图片分类id", required=false, type="string"),
     *     @SWG\Parameter( name="image_name", in="query", description="图片名称", required=true, type="string"),
     *     @SWG\Parameter( name="brief", in="query", description="描述", required=false, type="string"),
     *     @SWG\Parameter( name="storage", in="query", description="存储类型", required=true, type="string"),
     *     @SWG\Parameter( name="image_type", in="query", description="图片类型", required=false, type="string"),
     *     @SWG\Parameter( name="image_url", in="query", description="图片标识", required=false, type="string"),
     *     @SWG\Parameter( name="image_full_url", in="query", description="图片链接", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *             @SWG\Property(property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="image_id", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones") ) )
     * )
     */
    public function saveImage(Request $request)
    {
        // EcShopX core
        $params = $request->all();
        $params['image_cat_id'] = $request->input('image_cat_id', 0);
        $params['company_id'] = $request->get('auth')['company_id'];
        $validator = app('validator')->make($params, [
            'company_id' => 'required|integer',
            //'image_cat_id' => 'required|integer',
            'image_name' => 'required|max:100',
            'storage' => 'required',
            'image_type' => 'max:20',
            'image_url' => 'required',
        ], [
            'company_id.*' => '企业id必填,必须为整数',
            //'image_cat_id.*' => '图片分类必填,必须为整数',
            'image_name.*' => '图片名称必填,不能超过50个字符',
            'storage.*' => '存储类型必填',
            'image_type.*' => '图片分类长度不能超过20个字符',
            'image_url.*' => '图片链接必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            $this->api_response('fail', $errmsg, null, 'E0001');
        }

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->saveImage($params);
        $return['image_id'] = $result['image_id'];

        $this->api_response('true', "ok", $return, 'E0000');
    }


    /**
     * @SWG\Post(
     *     path="/ecx.image.del",
     *     summary="删除图片",
     *     tags={"图片"},
     *     description="删除图片",
     *     operationId="deleteImage",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.image.del" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="0" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", name="image_id", description="图片id,id以,分隔，单次最多选择100张图片" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones") ) )
     * )
     */
    public function deleteImage(Request $request)
    {
        $image_ids = $request->input('image_id');
        if (!$image_ids) {
            $this->api_response('fail', '请选择要删除的图片', null, 'E0001');
        }
        $imageIds = explode(',', $image_ids);
        if (count($imageIds) > 100) {
            $this->api_response('fail', '单次最多删除100个图片', null, 'E0002');
        }
        $companyId = $request->get('auth')['company_id'];
        $uploadImageService = new UploadImageService();

        $result = $uploadImageService->delImage($companyId, $imageIds);

        $this->api_response('true', "ok", ['status' => $result], 'E0000');
    }
}
