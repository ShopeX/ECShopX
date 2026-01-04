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
use WechatBundle\Services\Material as MaterialService;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;

class Material extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wechat/news/{media_id}",
     *     summary="获取图文素材详情",
     *     tags={"微信"},
     *     description="获取图文素材详情",
     *     operationId="getNewsMaterial",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="media_id", in="path", description="图文素材ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="media_id", type="stirng"),
     *                     @SWG\Property(property="url", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getNewsMaterial($materialId)
    {
        // ShopEx EcShopX Business Logic Layer
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId, false);
        $data = $service->getMaterial($materialId);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/material",
     *     summary="上传素材",
     *     tags={"微信"},
     *     description="上传素材 包含图片素材，语音素材，视频素材",
     *     operationId="uploadImage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="is_temp", in="query", description="是否为临时素材，默认为false", default=false, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="素材类型 image图片 thumb缩略图 video视频 voice声音", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="formData", description="素材文件", required=true, type="file"),
     *     @SWG\Parameter( name="title", in="query", description="视频标题", required=false, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="视频描述", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="media_id", type="stirng"),
     *                     @SWG\Property(property="url", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function uploadMaterial(Request $request)
    {
        $media = [
            'image' => 'uploadImage',
            'thumb' => 'uploadThumb',
            'video' => 'uploadVideo',
            'voice' => 'uploadVoice',
        ];

        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId, $request->input('is_temp', false));

        if ($request->file('file')->getSize() <= 0) {
            throw new StoreResourceFailedException('上传素材失败');
        }

        $oldpath = $request->file('file')->getPathname();
        $path = $request->file('file')->getPath().'/'.$request->file('file')->getClientOriginalName();
        copy($oldpath, $path);

        if ($path && $fun = $media[$request->input('type')]) {
            if ($request->input('type') == 'video') {
                $data = $service->uploadVideo($path, $request->input('title'), $request->input('description'));
            } else {
                $data = $service->$fun($path);
            }
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/news/image",
     *     summary="上传图文内的图片",
     *     tags={"微信"},
     *     description="上传图文内的图片",
     *     operationId="uploadArticleImage",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="file", in="formData", description="图片文件只支持jpg/png格式,必须1MB以下", required=true, type="file"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="url", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function uploadArticleImage(Request $request)
    {
        //实例化
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);

        if ($request->file('file')->getSize() <= 0) {
            throw new StoreResourceFailedException('上传图片失败');
        }
        //获取文件路径
        $path = $request->file('file')->getPathname();
        $newPath = $path.'.'.$request->file('file')->getClientOriginalExtension();
        copy($path, $newPath);

        //上传图片
        $data = $service->uploadArticleImage($newPath);
        $url = $data->url;

        return $this->response->array(['url' => $url]);
    }

    /**
     * @SWG\Delete(
     *     path="/wechat/material",
     *     summary="删除素材",
     *     tags={"微信"},
     *     description="删除素材 包含删除图片 声音 视频 图文等素材",
     *     operationId="deleteMaterial",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="media_id", in="query", description="素材ID，多个用逗号隔开", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function deleteMaterial(Request $request)
    {
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);

        $service->deleteMaterial($request->input('media_id'));
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/material/stats",
     *     summary="获取素材状态",
     *     tags={"微信"},
     *     description="获取素材状态",
     *     operationId="getMaterialStats",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="voice_count", type="string", example="0"),
     *                  @SWG\Property( property="video_count", type="string", example="6"),
     *                  @SWG\Property( property="image_count", type="string", example="330"),
     *                  @SWG\Property( property="news_count", type="string", example="0"),
     *                  @SWG\Property( property="image_limit", type="string", example="4670"),
     *                  @SWG\Property( property="news_limit", type="string", example="5000"),
     *                  @SWG\Property( property="video_limit", type="string", example="994"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getMaterialStats(Request $request)
    {
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);
        $stats = $service->stats();
        return $this->response->array($stats);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/material",
     *     summary="获取永久素材列表",
     *     tags={"微信"},
     *     description="获取永久素材列表",
     *     operationId="getMaterialLists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="type", in="query", description="素材的类型，图片（image）、视频（video）、语音 （voice）、图文（news）", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码 默认为1", required=false, type="string"),
     *     @SWG\Parameter( name="count", in="query", description="返回素材的数量，可选，默认 20, 取值在 1 到 20 之间", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="url", type="图片或视频地址"),
     *                     @SWG\Property(property="media_id", type="素材ID"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getMaterialLists(Request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取微信图片列表出错.', $validator->errors());
        }
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);

        $type = $request->input('type');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $list = $service->getMaterialLists($type, $page, $pageSize);
        if ($list) {
            if ($type == 'video') {
                foreach ($list['item'] as &$value) {
                    $detail = $service->getMaterial($value['media_id']);
                    $value['url'] = $detail['down_url'] ?? '';
                    $value['desc'] = $detail['description'] ?? '';
                }
            }
        }
        return $this->response->array($list);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/news",
     *     summary="创建图文素材",
     *     tags={"微信"},
     *     description="创建图文素材",
     *     operationId="createNews",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="创建图文所需参数",
     *         required=true,
     *         type="array",
     *         @SWG\Schema(type="object", ref="#/definitions/WechatNews")
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
     *                     @SWG\Property(property="media_id", type="string", description="图文素材ID"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function createNews(Request $request)
    {
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);

        $body = $request->input('body');
        if (!$body) {
            return $this->response->error('请填写图文参数', 422);
        }
        $res = $service->uploadArticle($body);

        return $this->response->array(['media_id' => $res->media_id]);
    }

    /**
     * @SWG\Put(
     *     path="/wechat/news/",
     *     summary="修改图文素材",
     *     tags={"微信"},
     *     description="修改图文素材",
     *     operationId="updateArticle",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="media_id", in="query", description="要修改的图文素材ID", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="创建图文所需参数",
     *         required=true,
     *         type="array",
     *         @SWG\Schema(type="object", ref="#/definitions/WechatNews")
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function updateArticle(Request $request)
    {
        $service = new MaterialService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $service = $service->application($authorizerAppId);

        $body = $request->input('body');
        if (!$body) {
            return $this->response->error('请填写图文参数', 422);
        }
        $service->updateArticle($request->input('media_id'), $body);

        return $this->response->array(['status' => true]);
    }
}
