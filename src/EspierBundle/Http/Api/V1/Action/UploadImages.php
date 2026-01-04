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

namespace EspierBundle\Http\Api\V1\Action;

use EspierBundle\Services\UploadFileService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use EspierBundle\Services\UploadImageService;
use EspierBundle\Services\UploadTokenFactoryService;

use Dingo\Api\Exception\ResourceException;

class UploadImages extends Controller
{
    /**
     * @SWG\Post(
     *     path="/espier/image_upload_token",
     *     summary="获取上传图片token",
     *     tags={"系统"},
     *     description="获取上传图片token",
     *     operationId="getPicUploadToken",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string", ),
     *     @SWG\Parameter( name="filesystem", in="query", description="文件系统名称", required=true, type="string", ),
     *     @SWG\Parameter( name="filename", in="query", description="上传文件名称", required=true, type="string", ),
     *     @SWG\Parameter( name="filetype", in="query", description="上传文件类型,item 商品，aftersales 售后", type="string", ),
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
        $companyId = app('auth')->user()->get('company_id');
        $filesystem = 'image';
        $filename = $request->input('filename');
        $result = UploadTokenFactoryService::create($filesystem)->getToken($companyId, '', $filename);
        return $this->response->array($result['token']);
    }

    /**
     * @SWG\Post(
     *     path="/espier/video_upload_token",
     *     summary="获取上传视频token",
     *     tags={"系统"},
     *     description="获取上传视频token",
     *     operationId="getVideoUploadToken",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="filesystem",
     *         in="query",
     *         description="文件系统名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="filename",
     *         in="query",
     *         description="上传文件名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="filetype",
     *         in="query",
     *         description="上传文件类型,item 商品，aftersales 售后",
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
    public function getVideoUploadToken(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filesystem = 'videos';
        $filename = $request->input('filename');
        $result = UploadTokenFactoryService::create($filesystem)->getToken($companyId, '', $filename);
        return $this->response->array($result['token']);
    }
    /**
     * @SWG\Post(
     *     path="/espier/oss_upload_token",
     *     summary="获取上云存储上传TOKE",
     *     tags={"系统"},
     *     description="获取上云存储上传TOKE",
     *     operationId="getUploadToken",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="filetype", in="query", description="上传文件类型,支持 file,image,viodes", required=true, type="string" ),
     *     @SWG\Parameter( name="group", in="query", description="上传分组名称,item 商品，aftersales 售后", required=false, type="string" ),
     *     @SWG\Parameter( name="filename", in="query", description="上传文件名称", required=false, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getUploadToken(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $fileType = $request->input('filetype');
        $group = $request->input('group');
        $filename = $request->input('filename');
        $result = UploadTokenFactoryService::create($fileType)->getToken($companyId, $group, $filename);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/oss_upload",
     *     summary="上传文件至云存储",
     *     tags={"系统"},
     *     description="上传文件至云存储",
     *     operationId="getUploadToken",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="filetype", in="query", description="上传文件类型,支持 file,image,viodes", required=true, type="string" ),
     *     @SWG\Parameter( name="group", in="query", description="上传分组名称,item 商品，aftersales 售后", required=false, type="string" ),
     *     @SWG\Parameter( name="filename", in="query", description="上传文件名称", required=false, type="string" ),
     *     @SWG\Parameter( name="file", in="query", description="上传文件-文件内容", required=false, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="driver", type="string", example="qiniu", description="云存储厂商"),
     *                  @SWG\Property( property="token", type="object",
     *                          @SWG\Property( property="host", type="string", example="https://upload-z2.qiniup.com", description=""),
     *                          @SWG\Property( property="token", type="string", example="wTHrpmk-bryNH0=", description=""),
     *                          @SWG\Property( property="domain", type="string", example="http://test.test.com/", description="合法域名配置"),
     *                          @SWG\Property( property="region", type="string", example="z2", description=""),
     *                          @SWG\Property( property="key", type="string", example="image/1/2021/02/05/cab8a95e46b192e6d8fe5efce942e4468srF7OftNlqtbNYplYuK6uHY6CJK63zz", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function ossUpload(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $fileType = $request->input('filetype');

        $group = $request->input('group');
        $filename = $request->input('filename');
        $fileObject = $request->file('file');

        $rules = [
            'file' => ['required', '上传文件内容必填'],
            'filetype' => ['required', '文件类型必填'],
        ];
        $params = [
            'file' => $fileObject,
            'filetype' => $fileType
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $result = (new UploadFileService())->uploadOss($companyId, $fileType, $fileObject, $group, $filename);

        return $this->response->array($result);
    }


    /**
     * @SWG\Post(
     *     path="/espier/image/cat",
     *     summary="添加图片分类",
     *     tags={"系统"},
     *     description="添加图片分类",
     *     operationId="editImageCat",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_id",
     *         in="formData",
     *         description="图片分类id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_name",
     *         in="formData",
     *         description="图片分类名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="parent_id",
     *         in="formData",
     *         description="图片分类父id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序",
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
     *                     @SWG\Property(property="image_cat_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="image_cat_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function editImageCat(Request $request)
    {
        $params = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['image_cat_name'] = trim($request->input('image_cat_name'));
        $rules = [
            'image_cat_name' => ['required|max:20', '文件夹名称必填|文件名称不能超过20个字'],
            'parent_id' => ['numeric', '父分类id为数字'],
            'sort' => ['integer', '排序值必须为整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $operatorType = app('auth')->user()->get('operator_type');
        $params['supplier_id'] = 0;
        if ($operatorType == 'supplier') {
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        }

        $params['merchant_id'] = 0;
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->saveImageCat($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/espier/image/cat/{image_cat_id}",
     *     summary="获取分类详情",
     *     tags={"系统"},
     *     description="获取分类详情",
     *     operationId="getAftersalesDetail",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_id",
     *         in="path",
     *         description="分类id",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getCatInfo($image_cat_id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->getImageCatInfo($companyId, $image_cat_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/espier/image/cat/children",
     *     summary="获取分类的子类",
     *     tags={"系统"},
     *     description="getCatChildren",
     *     operationId="getCatChildren",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_id",
     *         in="query",
     *         description="图片分类id",
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
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getCatChildren(Request $request)
    {
        $imageCatId = $request->input('image_cat_id', 0);
        $companyId = app('auth')->user()->get('company_id');
        $uploadImageService = new UploadImageService();
        $params = [
            'company_id' => $companyId,
            'image_cat_id' => $imageCatId
        ];

        $params['distributor_id'] = $request->input('distributor_id', 0);

        $operatorType = app('auth')->user()->get('operator_type');
        $params['supplier_id'] = 0;
        if ($operatorType == 'supplier') {
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        }

        $params['merchant_id'] = 0;
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        $result = $uploadImageService->getImageCatChildren($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/espier/image/cat/{image_cat_id}",
     *     summary="删除图片文件夹",
     *     tags={"系统"},
     *     description="delImgCat",
     *     operationId="delImgCat",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function delImgCat($image_cat_id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $uploadImageService = new UploadImageService();

        $result = $uploadImageService->delImageCat($companyId, $image_cat_id);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/image",
     *     summary="添加图片",
     *     tags={"系统"},
     *     description="保存图片",
     *     operationId="saveImage",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_id",
     *         in="formData",
     *         description="图片分类id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_name",
     *         in="formData",
     *         description="图片名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="storage",
     *         in="formData",
     *         description="存储类型",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_type",
     *         in="formData",
     *         description="图片类型",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_url",
     *         in="formData",
     *         description="图片标识",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_full_url",
     *         in="formData",
     *         description="图片链接",
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
     *                     @SWG\Property(property="image_id", type="string"),
     *                     @SWG\Property(property="image_name", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="image_full_url", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function saveImage(Request $request)
    {
        $params = $request->all();
        $params['image_cat_id'] = $request->input('image_cat_id', 0);
        $params['company_id'] = app('auth')->user()->get('company_id');
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
            'storage.*' => '图片id必填',
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
            throw new ResourceException($errmsg);
        }

        $operatorType = app('auth')->user()->get('operator_type');
        $params['supplier_id'] = 0;
        if ($operatorType == 'supplier') {
            $params['supplier_id'] = app('auth')->user()->get('operator_id');
        }

        $params['merchant_id'] = 0;
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->saveImage($params);
        // 上传后直接获取全路径
        // 店务端上传完成直接修改头像功能
        $filesystem = app('filesystem')->disk('import-image');
        $url = $filesystem->url($result['image_url']);
        $result['url'] = $url;
        $result['image_full_url'] = $url;

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/espier/images",
     *     summary="删除图片",
     *     tags={"系统"},
     *     description="deleteImage",
     *     operationId="deleteImage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="image_id", in="query", description="图片id,id以,分隔，单次最多选择100张图片", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function deleteImage(Request $request)
    {
        $image_ids = $request->input('image_id');
        if (!$image_ids) {
            throw new ResourceException("请选择要删除的图片");
        }
        $imageIds = explode(',', $image_ids);
        if (count($imageIds) > 100) {
            throw new ResourceException("单次最多删除100个图片");
        }
        $companyId = app('auth')->user()->get('company_id');
        $uploadImageService = new UploadImageService();

        $result = $uploadImageService->delImage($companyId, $imageIds);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/espier/image/movecat",
     *     summary="移动图片到指定分类",
     *     tags={"系统"},
     *     description="移动图片到指定分类",
     *     operationId="moveImageCat",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_cat_id",
     *         in="formData",
     *         description="图片分类id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_id",
     *         in="formData",
     *         description="图片id,多个以逗号分离",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_type",
     *         in="formData",
     *         description="图片类型",
     *         required=true,
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
     *                     @SWG\Property(property="image_cat_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="image_id", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function moveImageCat(Request $request)
    {
        $params = $request->all('image_cat_id', 'image_id', 'image_type');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $validator = app('validator')->make($params, [
            'image_cat_id' => 'required|integer',
            'company_id' => 'required|integer',
            'image_id' => 'required',
        ], [
            'image_cat_id.*' => '图片分类必填,必须为整数',
            'company_id.*' => '企业id必填,必须为整数',
            'image_id.*' => '图片id必填',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->moveImgCat($params);

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/espier/images",
     *     summary="获取图片列表",
     *     tags={"系统"},
     *     description="获取图片列表",
     *     operationId="getImageList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string", ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer", ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer", ),
     *     @SWG\Parameter( name="image_cat_id", in="query", description="图片分类id", type="string", ),
     *     @SWG\Parameter( name="image_name", in="query", description="图片名称", type="string", ),
     *     @SWG\Parameter( name="storage", in="query", description="存储引擎", type="string", ),
     *     @SWG\Parameter( name="image_url", in="query", description="图片标识", type="string", ),
     *     @SWG\Parameter( name="disabled", in="query", description="图片是否失效", type="string", ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="360", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="image_id", type="string", example="2884", description="图片id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="storage", type="string", example="image", description="存储引擎，可选值有，image/videos"),
     *                          @SWG\Property( property="image_name", type="string", example="WX20200319-164136@2x.png", description="图片名称"),
     *                          @SWG\Property( property="brief", type="string", example="null", description="图片简介"),
     *                          @SWG\Property( property="image_cat_id", type="string", example="2", description="图片分类id"),
     *                          @SWG\Property( property="image_type", type="string", example="image/png", description="图片类型"),
     *                          @SWG\Property( property="image_full_url", type="string", example="http://test.test.com/image/1/2021/02/05/cab8a95e46b", description="图片完成地址"),
     *                          @SWG\Property( property="image_url", type="string", example="image/1/2021/02/05/cab8a95e46b192e6d8fe5efce942e4468", description="元素配图"),
     *                          @SWG\Property( property="disabled", type="string", example="false", description=""),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="created", type="string", example="1612516928", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612516928", description="修改时间"),
     *                          @SWG\Property( property="url", type="string", example="http://test.test.com/image/1/2021/02/05/cab8a9", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getImageList(Request $request)
    {
        $params = $request->all('storage', 'image_name', 'disabled', 'image_cat_id');
        $filter['storage'] = $params['storage'] ?: 'image';
        $filter['company_id'] = app('auth')->user()->get('company_id');

        if ($params['image_cat_id']) {
            $filter['image_cat_id'] = $params['image_cat_id'];
        }

        $filter['distributor_id'] = $request->input('distributor_id', 0);

        if ($params['image_name']) {
            $filter['image_name'] = $params['image_name'];
        }
        if ($request->input('disabled')) {
            $filter['disabled'] = $params['disabled'];
        }

        $operatorType = app('auth')->user()->get('operator_type');
        $filter['supplier_id'] = 0;
        if ($operatorType == 'supplier') {
            $filter['supplier_id'] = app('auth')->user()->get('operator_id');
        }

        $filter['merchant_id'] = 0;
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = app('auth')->user()->get('merchant_id');
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $uploadImageService = new UploadImageService();
        $result = $uploadImageService->getImagesBy($filter, $page, $pageSize);

        return $this->response->array($result);
    }
    /**
     * @SWG\Post(
     *     path="/espier/uploade_image",
     *     summary="上传图片",
     *     tags={"系统"},
     *     description="保存图片",
     *     operationId="uploadeImage",
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
    public function uploadeImage(Request $request)
    {
        $filename = $request->file('images');

        if (!$filename) {
            throw new ResourceException("请上传图片");
        }

        if (!$filename->isValid()) {
            throw new ResourceException("请上传有效图片");
        }

        $companyId = app('auth')->user()->get('company_id');

        $fileType = $request->input('filetype') ?: 'image';
        $group = $request->input('group');

        $result = UploadTokenFactoryService::create($fileType)->uploadeImage($companyId, $group, $filename);
        $return = ['image_url' => $result];
        return $this->response->array($return);
    }
}
