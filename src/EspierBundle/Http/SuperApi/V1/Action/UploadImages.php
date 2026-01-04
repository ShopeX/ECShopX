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

namespace EspierBundle\Http\SuperApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use EspierBundle\Services\UploadTokenFactoryService;

class UploadImages extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/espier/image_upload_token",
     *     summary="获取上传图片token",
     *     tags={"平台管理"},
     *     description="获取上传图片token",
     *     operationId="getPicUploadToken",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
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
    public function getPicUploadToken(Request $request)
    {
        $companyId = 0;
        $filetype = 'image';
        $result = UploadTokenFactoryService::create($filetype)->getToken($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/espier/upload_localimage",
     *     summary="上传图片",
     *     tags={"平台管理"},
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
        $filename = $request->file('images');
        if (!$filename->isValid()) {
            throw new ResourceException("请上传有效图片");
        }

        $companyId = 0;
        $fileType = 'image';
        $result = UploadTokenFactoryService::create($fileType)->uploadeImage($companyId, null, $filename);
        $return = ['image_url' => $result];
        return $this->response->array($return);
    }
}
