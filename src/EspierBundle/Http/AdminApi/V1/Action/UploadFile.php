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

namespace EspierBundle\Http\AdminApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
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
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="filesystem", in="formData", description="文件系统名称", required=true, type="string"),
     *     @SWG\Parameter( name="filename", in="formData", description="上传文件名称", required=true, type="string"),
     *     @SWG\Parameter( name="group", in="formData", description="上传文件类型,item 商品，aftersales 售后", required=true, type="string"),
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
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones") ) )
     * )
     */
    public function getPicUploadToken(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $filename = $request->input('filename');
        $group = $request->input('group', 'guide');
        $filetype = 'image';
        $result = UploadTokenFactoryService::create($filetype)->getToken($companyId, $group, $filename);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/uploadeLocalImage",
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
        // ShopEx framework
        $filename = $request->file('images');
        if (!$filename->isValid()) {
            throw new ResourceException("请上传有效图片");
        }

        $auth = $this->auth->user();
        $companyId = $auth['company_id'];

        $fileType = $request->input('filetype') ?: 'image';
        $group = $request->input('group');

        $result = UploadTokenFactoryService::create($fileType)->uploadeImage($companyId, $group, $filename);
        $return = ['image_url' => $result];
        return $this->response->array($return);
    }
}
