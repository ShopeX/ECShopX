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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\ResourceException;

use CompanysBundle\Services\ArticleService;

use CompanysBundle\Services\ArticleCategoryService;

class ArticleController extends BaseController
{
    private $articleService;

    public function __construct(ArticleService $ArticleService)
    {
        $this->articleService = new $ArticleService();
    }

    /**
     * @SWG\Definition(
     *     definition="Article",
     *     @SWG\Property(property="article_id", type="integer"),
     *     @SWG\Property(property="company_id", type="integer", example="1"),
     *     @SWG\Property(property="title", type="integer", example="1"),
     *     @SWG\Property(property="author", type="integer", example="1"),
     *     @SWG\Property(property="summary", type="string", example="1"),
     *     @SWG\Property(property="content", type="string", example="1"),
     *     @SWG\Property(property="image_url", type="string", example="1"),
     *     @SWG\Property(property="created", type="string", example="1"),
     *     @SWG\Property(property="sort", type="integer", example="1"),
     *     @SWG\Property(property="share_image_url", type="string", example="1"),
     *     @SWG\Property(property="operator_id", type="string", example="1"),
     *     @SWG\Property(property="release_status", type="string", example="1"),
     *     @SWG\Property(property="article_type", type="string", example="1"),
     *     @SWG\Property(property="distributor_id", type="string", example="1"),
     *     @SWG\Property(property="head_portrait", type="string", example="1"),
     *     @SWG\Property(property="city", type="string", example="1"),
     *     @SWG\Property(property="area", type="string", example="1"),
     * )
     */




    /**
     * @SWG\Get(
     *     path="/wxapp/article/management",
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
     *         name="article_id",
     *         in="query",
     *         description="文章id",
     *         type="string",
     *     ),
     *
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="文章栏目id",
     *         type="string",
     *     ),
     *
     *     @SWG\Parameter(
     *         name="province",
     *         in="query",
     *         description="省",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="city",
     *         in="query",
     *         description="市",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="area",
     *         in="query",
     *         description="区",
     *         type="string",
     *     ),
     *
     *     @SWG\Parameter(
     *         name="article_type",
     *         in="query",
     *         description="文章类型,general:普通文章; bring:带货文章",
     *         type="string", default="general",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#definitions/Article"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function listDataArticle(Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];

        if ($name = $request->input('title')) {
            $filter['title|contains'] = $name;
        }

        if ($id = $request->input('article_id')) {
            $filter['article_id'] = $id;
        }
        $filter['article_type'] = $request->get('article_type', 'general');
        $pageSize = $request->input('pageSize', 100);
        $page = $request->input('page', 1);
        $filter['authorizer_appid'] = $authInfo['woa_appid'] ?? '';

        if (isset($authInfo['user_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
        }

        if ($categoryId = $request->get('category_id')) {
            $filter['category_id'] = $categoryId;
        }

        if ($area = $request->get('area')) {
            $filter['area'] = $area;
        }
        if ($city = $request->get('city')) {
            $filter['city'] = $city;
        }
        if ($province = $request->get('province')) {
            $filter['province'] = $province;
        }

        $filter['release_status'] = true;
        $orderBy = ['sort' => 'asc', 'release_time' => 'desc'];
        $result = $this->articleService->getArticleList($filter, $page, $pageSize, $orderBy, true);

        $result['province_list'] = $this->articleService->getAllProvince($filter['company_id']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/management/{article_id}",
     *     summary="文章详情",
     *     tags={"企业"},
     *     description="文章详情",
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
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#definitions/Article"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function infoDataArticle($article_id, Request $request)
    {
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['article_id'] = $article_id;
        $rules = [
            'article_id' => ['required|integer|min:1', '种草不存在'],
        ];
        $error = validator_params($filter, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        $filter['authorizer_appid'] = $authInfo['woa_appid'] ?? '';
        if (isset($authInfo['user_id'])) {
            $filter['user_id'] = $authInfo['user_id'];
        }
        $result = $this->articleService->getArticleInfo($filter, true);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/focus/{article_id}",
     *     summary="文章关注",
     *     tags={"企业"},
     *     description="文章关注",
     *     operationId="articleFocus",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function articleFocus($article_id, Request $request)
    {
        $validator = app('validator')->make(['article_id' => $article_id], [
            'article_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取关注信息错误!', $validator->errors());
        }
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['article_id'] = $article_id;
        $result = $this->articleService->articleFocus($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/focus/num/{article_id}",
     *     summary="文章关注总数量",
     *     tags={"企业"},
     *     description="文章关注总数量",
     *     operationId="ArticleFocusNum",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function ArticleFocusNum($article_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['article_id'] = $article_id;
        $result = $this->articleService->articleFocusNum($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/praise/{article_id}",
     *     summary="文章点赞",
     *     tags={"企业"},
     *     description="文章点赞",
     *     operationId="articlePraise",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function articlePraise($article_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['user_id'] = $authInfo['user_id'] ? $authInfo['user_id'] : $params['user_id'];
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['article_id'] = $article_id;
        $result = $this->articleService->articlePraise($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/praise/num/{article_id}",
     *     summary="文章点赞总数",
     *     tags={"企业"},
     *     description="文章点赞总数",
     *     operationId="articlePraiseNum",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function articlePraiseNum($article_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['article_id'] = $article_id;
        $result = $this->articleService->articlePraiseNum($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/praise/check/{article_id}",
     *     summary="文章点赞验证",
     *     tags={"企业"},
     *     description="文章点赞验证",
     *     operationId="articlePraiseCheck",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function articlePraiseCheck($article_id, Request $request)
    {
        $params = $request->input();
        $authInfo = $request->get('auth');
        $params['user_id'] = $authInfo['user_id'] ? $authInfo['user_id'] : $params['user_id'];
        $params['company_id'] = $authInfo['company_id'] ? $authInfo['company_id'] : $params['company_id'];
        $params['article_id'] = $article_id;
        $result = $this->articleService->articlePraiseCheck($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/praise/getcountresult",
     *     summary="批量获取文章点赞数量和点赞状态",
     *     tags={"企业"},
     *     description="批量获取文章点赞数量和点赞状态",
     *     operationId="getArticlePraiseData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="article_ids",
     *         in="path",
     *         description="ids",
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
     *                     @SWG\Property(property="count", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getArticlePraiseData(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $articleIds = $request->get('article_ids');
        $userId = (isset($authInfo['user_id']) && $authInfo['user_id']) ? $authInfo['user_id'] : null;
        $result = $this->articleService->getArticlePraiseResult($articleIds, $companyId, $userId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/category",
     *     summary="获取文章栏目列表",
     *     tags={"企业"},
     *     description="获取文章栏目列表",
     *     operationId="getCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
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
     *                     @SWG\Property(property="category_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="category_name", type="integer", example="1"),
     *                     @SWG\Property(property="parent_id", type="integer", example="1"),
     *                     @SWG\Property(property="category_level", type="string", example="1"),
     *                     @SWG\Property(property="level", type="string", example="1"),
     *                     @SWG\Property(property="category_type", type="string", example="bring"),
     *                     @SWG\Property(property="path", type="string", example="1"),
     *                     @SWG\Property(property="sort", type="integer", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                     @SWG\Property(property="children", type="array", example="1", @SWG\Items()),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getCategory(request $request)
    {
        $authInfo = $request->get('auth');
        $filter['category_type'] = $request->get('category_type', 'bring');
        $filter['company_id'] = $authInfo['company_id'];
        $articleCategoryService = new ArticleCategoryService();
        $result = $articleCategoryService->getArticleCategory($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/article/province",
     *     summary="获取文章的所有省份列表",
     *     tags={"企业"},
     *     description="获取文章的所有省份列表",
     *     operationId="getAllProvince",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     example="230000",
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getAllProvince(request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $result = $this->articleService->getAllProvince($companyId);
        return $this->response->array($result);
    }
}
