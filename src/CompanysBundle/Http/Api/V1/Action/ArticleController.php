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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\ArticleService;

use Dingo\Api\Exception\StoreResourceFailedException;

class ArticleController extends BaseController
{
    private $articleService;
    private $regions = [
        0 => 'province',
        1 => 'city',
        2 => 'area',
    ];


    public function __construct(ArticleService $ArticleService)
    {
        $this->articleService = new $ArticleService();
    }

    /**
     * @SWG\Definition(
     *     definition="Article",
     *     @SWG\Property(property="article_id", type="integer", description="文章ID"),
     *     @SWG\Property(property="article_type", type="integer", description=""),
     *     @SWG\Property(property="company_id", type="integer", example="1", description=""),
     *     @SWG\Property(property="title", type="integer", example="1", description="标题"),
     *     @SWG\Property(property="summary", type="string", example="1", description="摘要"),
     *     @SWG\Property(property="content", type="string", example="1", description="内容"),
     *     @SWG\Property(property="sort", type="integer", example="1", description="排序"),
     *     @SWG\Property(property="image_url", type="string", example="1", description="发布时间"),
     *     @SWG\Property(property="release_time", type="string", example="1", description="发布时间"),
     *     @SWG\Property(property="release_status", type="string", example="true", description="文章发布状态"),
     *     @SWG\Property(property="operator_id", type="string", example="1", description="作者id"),
     *     @SWG\Property(property="author", type="integer", example="1", description="作者"),
     *     @SWG\Property(property="head_portrait", type="string", example="1", description="作者头像"),
     *     @SWG\Property(property="updated", type="string", example="1", description="更新时间"),
     *     @SWG\Property(property="area", type="string", example="1", description="区"),
     *     @SWG\Property(property="category_id", type="string", example="1", description="栏目ID"),
     *     @SWG\Property(property="city", type="string", example="1", description="城市"),
     *     @SWG\Property(property="created", type="string", example="1", description="创建时间"),
     *     @SWG\Property(property="distributor_id", type="string", example="1", description="分销商id"),
     *     @SWG\Property(property="province", type="string", example="1", description="省"),
     *     @SWG\Property(property="regions", type="string", example="1", description="地区名称集合"),
     *     @SWG\Property(property="regions_id", type="string", example="1", description="地区编号集合"),
     *     @SWG\Property(property="share_image_url", type="string", example="1", description="分享图片"),
     * )
     */




    /**
     * @SWG\Post(
     *     path="/article/management",
     *     summary="创建文章",
     *     tags={"企业"},
     *     description="创建文章",
     *     operationId="createDataArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         description="文章标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="author",
     *         in="query",
     *         description="文章作者",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="文章排序",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="release_status",
     *         in="query",
     *         description="发布状态",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="summary",
     *         in="query",
     *         description="文章摘要",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="content",
     *         in="query",
     *         description="文章内容",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="image_url",
     *         in="query",
     *         description="文章封面",
     *         type="string",
     *     ),
    *     @SWG\Parameter(
    *         name="share_image_url",
    *         in="query",
    *         description="分享图片",
    *         type="string",
    *     ),
     *     @SWG\Parameter(
     *         name="article_type",
     *         in="query",
     *         description="文章类型,general:普通文章; bring:带货文章",
     *         type="string", default="general",
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="文章栏目",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Article"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createDataArticle(Request $request)
    {
        $params = $request->all('title', 'author', 'summary', 'content', 'sort', 'share_image_url', 'image_url', 'article_type', 'release_status', 'head_portrait', 'category_id', 'regions_id', 'regions', 'share_image_url');

        if (!isset($params['content']) || empty($params['content'])) {
            throw new StoreResourceFailedException('文章内容不能为空');
        }

        if (!array_filter($params)) {
            throw new StoreResourceFailedException('文章编辑出错');
        }
        if (!$request->get('category_id')) {
            throw new StoreResourceFailedException('文章栏目必填');
        }
        if (!$request->get('title')) {
            throw new StoreResourceFailedException('文章标题必填');
        }
        if (!$request->get('content')) {
            throw new StoreResourceFailedException('文章内容必填');
        }
        if ($params['article_type'] == 'bring') {
            if (!$request->get('content')) {
                throw new StoreResourceFailedException('文章内容必填');
            }
            // if (!$request->get('author')) {
            //     throw new StoreResourceFailedException('文章作者必填');
            // }
            // if (!$request->get('head_portrait')) {
            //     throw new StoreResourceFailedException('文章作者头像必填');
            // }

            if (isset($params['regions_id']) && isset($params['regions'])) {
                foreach ($params['regions'] as $k => $value) {
                    $params[$this->regions[$k]] = $value;
                }
            }
        }


        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['operator_id'] = $auth['operator_id'];
        $params['distributor_id'] = $request->input('distributor_id', 0);

        // if (!$params['author']) {
        //     $params['author'] = $auth['username'];
        // }
        // if (!$params['head_portrait']) {
        //     $params['head_portrait'] = $auth['head_portrait'];
        // }

        if (isset($params['release_status']) && (!$params['release_status'] || $params['release_status'] === 'false')) {
            $params['release_status'] = false;
            $params['release_time'] = 0;
        } elseif (isset($params['release_status'])) {
            $params['release_status'] = true;
            $params['release_time'] = time();
        }

        $result = $this->articleService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/article/management/{article_id}",
     *     summary="更新文章",
     *     tags={"企业"},
     *     description="更新文章",
     *     operationId="updateDataArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="article_id",
     *         in="path",
     *         description="文章标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         description="文章标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="author",
     *         in="query",
     *         description="文章作者",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="summary",
     *         in="query",
     *         description="文章摘要",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="content",
     *         in="query",
     *         description="文章内容",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="image_url",
     *         in="query",
     *         description="文章封面",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="share_image_url",
     *         in="query",
     *         description="分享图片",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="release_status",
     *         in="query",
     *         description="文章发布状态",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="article_type",
     *         in="query",
     *         description="文章类型,general:普通文章; bring:带货文章",
     *         type="string", default="general",
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="query",
     *         description="文章排序",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="文章栏目",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Article"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateDataArticle($article_id, Request $request)
    {
        $params = $request->all('title', 'author', 'summary', 'content', 'sort', 'image_url', 'share_image_url', 'article_type', 'release_status', 'head_portrait', 'category_id', 'regions_id', 'regions', 'share_image_url');
        if (!array_filter($params)) {
            throw new StoreResourceFailedException('文章编辑出错');
        }

        if (!isset($params['content']) || empty($params['content'])) {
            throw new StoreResourceFailedException('文章内容不能为空');
        }

        if (isset($params['regions_id']) && isset($params['regions'])) {
            foreach ($params['regions'] as $k => $value) {
                $params[$this->regions[$k]] = $value;
            }
        }
        if (isset($params['regions']) && count($params['regions']) == 2) {
            $params['area'] = '';
        }

        $auth = app('auth')->user()->get();
        $params['company_id'] = $auth['company_id'];
        $params['operator_id'] = $auth['operator_id'];
        $params['summary'] = $params['summary'] ?? '';
        $params['author'] = $params['author'] ?? '';
        $params['head_portrait'] = $params['head_portrait'] ?? '';
        if ($params['head_portrait'] == '0') {
            $params['head_portrait'] = '';
        }

        if (isset($params['release_status']) && (!$params['release_status'] || $params['release_status'] === 'false')) {
            $params['release_status'] = false;
            $params['release_time'] = 0;
        } elseif (isset($params['release_status'])) {
            $params['release_status'] = true;
            $params['release_time'] = time();
        }

        $filter['company_id'] = $auth['company_id'];
        ;
        $filter['article_id'] = $article_id;
        $result = $this->articleService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/article/management/{article_id}",
     *     summary="删除文章",
     *     tags={"企业"},
     *     description="删除文章",
     *     operationId="deleteDataArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="article_id",
     *         in="path",
     *         description="id",
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteDataArticle($article_id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['article_id'] = $article_id;
        $result = $this->articleService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/article/management",
     *     summary="文章列表",
     *     tags={"企业"},
     *     description="文章列表",
     *     operationId="listDataArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="title",
     *         in="query",
     *         description="标题",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="article_type",
     *         in="query",
     *         description="文章类型,general:普通文章; bring:带货文章",
     *         type="string", default="general",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="分销商id",
     *         type="string", default="1",
     *     ),
     *     @SWG\Parameter(
     *         name="article_id",
     *         in="query",
     *         description="文章id",
     *         type="string", default="1",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         type="integer", default="1",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量",
     *         type="integer", default="1",
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
     *                     @SWG\Property(property="total_count", type="integer"),
     *                     @SWG\Property(property="list", type="array", description="文章列表", @SWG\Items(
     *                         ref="#/definitions/Article"
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function listDataArticle(Request $request)
    {
        
        $filter['company_id'] = app('auth')->user()->get('company_id');

        if ($name = $request->input('title')) {
            $filter['title|contains'] = $name;
        }

        $filter['distributor_id'] = $request->input('distributor_id', 0);

        if ($articleIds = $request->input('article_id')) {
            $filter['article_id'] = $articleIds;
        }

        //release_status 0为未发布 1 为已发布 2为全部 其余状态都为已发布
        if (isset($request->all()['release_status']) && $request->input('release_status') != 2) {
            $filter['release_status'] = boolval($request->input('release_status'));
        }
        $filter['article_type'] = $request->get('article_type', 'general');
        $pageSize = $request->input('pageSize', 100);
        $page = $request->input('page', 1);
        $filter['authorizer_appid'] = app('auth')->user()->get('authorizer_appid');
        $orderBy = ['sort' => 'asc','article_id' => 'desc'];
        $result = $this->articleService->getArticleList($filter, $page, $pageSize, $orderBy, true);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/article/management/{article_id}",
     *     summary="获取文章详情",
     *     tags={"企业"},
     *     description="获取文章详情",
     *     operationId="infoDataArticle",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="article_id",
     *         in="path",
     *         description="id",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/Article"
     *
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function infoDataArticle($article_id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['article_id'] = $article_id;
        $filter['authorizer_appid'] = app('auth')->user()->get('authorizer_appid');
        $result = $this->articleService->getArticleInfo($filter, true);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/article/updatestatusorsort",
     *     summary="更新文章发布状态或者排序",
     *     tags={"企业"},
     *     description="更新文章发布状态或者排序",
     *     operationId="updateArticleStatusOrSort",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="inputdata",
     *         in="path",
     *         description="要编辑的文章内容集合 [{article_id:0,sort:3,release_status:true}]",
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
     *                     @SWG\Property(property="status", type="boolean"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateArticleStatusOrSort(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $inputdata = $request->get('inputdata');
        $result = $this->articleService->updateArticleStatusOrSort($companyId, $inputdata);
        return $this->response->array(['status' => $result]);
    }
}
