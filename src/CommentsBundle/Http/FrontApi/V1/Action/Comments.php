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

namespace CommentsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CommentsBundle\Services\CommentService;
use CommentsBundle\Services\Comments\ShopCommentService;
use Dingo\Api\Exception\StoreResourceFailedException;

class Comments extends BaseController
{
    /**
      * @SWG\Definition(
      *     definition="Comment",
      *     type="object",
      *         @SWG\Property(property="comment_id", type="integer", example="1"),
      *         @SWG\Property(property="company_id", type="integer", example="1"),
      *         @SWG\Property(property="user_id", type="integer", example="20347"),
      *         @SWG\Property(property="shop_id", type="integer", example="1"),
      *         @SWG\Property(property="content", type="string", example="测试评论"),
      *         @SWG\Property(property="stuck", type="boolean", example="false"),
      *         @SWG\Property(property="hid", type="boolean", example="false"),
      *         @SWG\Property(property="source", type="string", example=""),
      *         @SWG\Property(property="created", type="string", example="1611114969"),
      *         @SWG\Property(property="updated", type="string", example="1611114969"),
      *         @SWG\Property(property="pics", type="array",
      *               @SWG\Items(
      *                )
      *         ),
      * )
     */






    /**
     * @SWG\Post(
     *     path="/wxapp/comment",
     *     summary="创建评论",
     *     tags={"评论"},
     *     description="创建评论",
     *     operationId="createComment",
     *     @SWG\Parameter(
     *         name="shop_id",
     *         in="query",
     *         description="门店id",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="content",
     *         in="query",
     *         description="评论内容",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="pics",
     *         in="query",
     *         description="评论图片",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/Comment",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommentErrorRespones") ) )
     * )
     */
    public function createComment(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        if (!$companyId) {
            return $this->response->error('无相关企业信息！', 411);
        }
        if (!$authInfo['user_id']) {
            return $this->response->error('成为会员才能评论！', 411);
        }
        $params = $request->all();
        if (strlen($params['content']) < 15) {
            throw new StoreResourceFailedException('评论内容必须大于5个汉字或15个字母！');
        } elseif (strlen($params['content']) > 15000) {
            throw new StoreResourceFailedException('评论内容不超过500个汉字！');
        }
        if (count($params['pics']) > 3) {
            throw new StoreResourceFailedException('最多添加三张评论图片！');
        }
        $params['company_id'] = $companyId;
        $params['user_id'] = $authInfo['user_id'];
        $commentService = new CommentService(new ShopCommentService());
        $result = $commentService->createComment($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/comments",
     *     summary="获取评论列表",
     *     tags={"评论"},
     *     description="获取评论列表",
     *     operationId="getComments",
     *     @SWG\Parameter(
     *         name="page_no",
     *         in="query",
     *         description="当前页面,获取商品列表的初始偏移位置，从1开始计数",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="shop_id",
     *         in="query",
     *         description="店铺id",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="is_hide",
     *         in="query",
     *         description="是否隐藏",
     *         type="boolean",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#definitions/Comment"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommentErrorRespones") ) )
     * )
     */
    public function getComments(Request $request)
    {
        $authInfo = $request->get('auth');
        $pageNo = $request->input('page_no', 1);
        $pageSize = $request->input('pageSize', 50);
        $postdata = $request->all();
        if (isset($postdata['is_hide']) && $postdata['is_hide'] != null) {
            $params['hid'] = $postdata['is_hide'];
        }
        if (isset($postdata['shop_id']) && $postdata['shop_id']) {
            $params['shop_id'] = $postdata['shop_id'];
        }
        $params['company_id'] = $authInfo['company_id'];

        $commentService = new CommentService(new ShopCommentService());
        $orderBy = ['stuck' => 'DESC', 'created' => 'DESC'];
        $result = $commentService->getCommentList($params, $pageNo, $pageSize, $orderBy);

        return $this->response->array($result);
    }
}
