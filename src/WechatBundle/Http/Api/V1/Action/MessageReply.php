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
use WechatBundle\Services\ReplyMessage\Autoreply;
use WechatBundle\Services\ReplyMessage\DefaultReply;
use WechatBundle\Services\ReplyMessage\Transfer;
use WechatBundle\Services\ReplyMessage\SubscribeReply;
use WechatBundle\Services\MessageService;

// 消息回复配置
class MessageReply extends Controller
{
    /**
     * 设置关键字回复
     * 一个关键字对于一个回复
     * 不需要像微信一样一个关键字可以发送多个回复
     *
     * @SWG\Post(
     *     path="/wechat/keyword/reply",
     *     summary="新增关键字消息回复",
     *     tags={"微信"},
     *     description="新增关键字消息回复",
     *     operationId="addKeywordReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rule_name", in="query", description="规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="keywords_rule", in="query", description="json 包含关键字和关键字匹配规则", required=true, type="string"),
     *     @SWG\Parameter( name="reply_type", in="query", description="回复消息类型  text文字 image图片 news图文 card卡券", type="string"),
     *     @SWG\Parameter( name="reply_content", in="query", description="回复消息内容 media_id 或者 card_id 或者配置的文本信息", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="boolean")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function addKeywordReply(Request $request)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $rules = [
            'rule_name' => $request->input('rule_name'),
            'keywords_rule' => $request->input('keywords_rule'),
            'reply_type' => $request->input('reply_type'),
            'reply_content' => $request->input('reply_content'),
        ];
        $ruleName = $request->input('rule_name');

        $autoreply = new Autoreply();
        $autoreply->addAutorReplyRules($authorizerAppId, $ruleName, $rules);

        return $this->response->array(['status' => true]);
    }

    /**
     * 设置关键字回复
     *
     * @SWG\Put(
     *     path="/wechat/keyword/reply",
     *     summary="更新关键字消息回复",
     *     tags={"微信"},
     *     description="更新关键字消息回复",
     *     operationId="addKeywordReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rule_name", in="query", description="规则名称", required=true, type="string"),
     *     @SWG\Parameter( name="keywords_rule", in="query", description="json 包含关键字和关键字匹配规则", required=true, type="string"),
     *     @SWG\Parameter( name="reply_type", in="query", description="回复消息类型  text文字 image图片 news图文 card卡券", type="string"),
     *     @SWG\Parameter( name="reply_content", in="query", description="回复消息内容 media_id 或者 card_id 或者配置的文本信息", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="boolean")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function updateKeywordReply(Request $request)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $rules = [
            'rule_name' => $request->input('rule_name'),
            'keywords_rule' => $request->input('keywords_rule'),
            'reply_type' => $request->input('reply_type'),
            'reply_content' => $request->input('reply_content'),
        ];
        $ruleName = $request->input('rule_name');

        $autoreply = new Autoreply();
        $autoreply->updateAutorReplyRules($authorizerAppId, $ruleName, $rules);

        return $this->response->array(['status' => true]);
    }

    /**
     * 删除关键字回复规则
     *
     * @SWG\Delete(
     *     path="/wechat/keyword/reply",
     *     summary="删除关键字回复规则",
     *     tags={"微信"},
     *     description="删除关键字回复规则",
     *     operationId="deleteKeywordReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="rule_name", in="query", description="更新的规则名称", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="boolean")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function deleteKeywordReply(Request $request)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $ruleName = $request->input('rule_name');

        $autoreply = new Autoreply();
        $autoreply->deleteAutorReplyRules($authorizerAppId, $ruleName);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/keyword/reply",
     *     summary="获取关键字回复列表",
     *     tags={"微信"},
     *     description="获取关键字回复列表",
     *     operationId="getKeywordReplyList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
     *        @SWG\Property(property="keyword", type="string"),
     *        @SWG\Property(property="reply_mode", type="string"),
     *        @SWG\Property(property="reply_type", type="string"),
     *        @SWG\Property(property="reply_content", type="string")
     *     )))),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getKeywordReplyList()
    {
        $autoreply = new Autoreply();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $list = $autoreply->getAutorReplyRules($authorizerAppId);
        $messageService = new MessageService();
        foreach ($list as &$row) {
            $row['reply_content'] = $messageService->replySettingContent($row['reply_type'], $row['reply_content'], $authorizerAppId);
        }

        return $this->response->array(['list' => $list]);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/default/reply",
     *     summary="设置默认回复",
     *     tags={"微信"},
     *     description="设置默认回复",
     *     operationId="setDefaultReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="reply_type", in="query", description="回复消息类型 text文字 image图片 news图文 card卡券", type="string"),
     *     @SWG\Parameter( name="reply_content", in="query", description="回复消息内容 media_id 或者 card_id 或者配置的文本信息", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="boolean")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setDefaultReply(Request $request)
    {
        $defaultreply = new DefaultReply();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $centent = $request->all('reply_type', 'reply_content');
        $defaultreply->setDefaultReplyContent($authorizerAppId, $centent);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/default/reply",
     *     summary="获取默认消息回复",
     *     tags={"微信"},
     *     description="获取默认消息回复",
     *     operationId="getDefaultReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
     *        @SWG\Property(property="keyword", type="string"),
     *        @SWG\Property(property="reply_type", type="string", description="回复消息类型 text文字 image图片 news图文 card卡券"),
     *        @SWG\Property(property="reply_content", type="string")
     *     )))),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getDefaultReply()
    {
        $defaultreply = new DefaultReply();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');

        $data = $defaultreply->getLastReplyContent($authorizerAppId);
        if (isset($data['reply_content']) && $data['reply_content']) {
            $messageService = new MessageService();
            $data['reply_content'] = $messageService->replySettingContent($data['reply_type'], $data['reply_content'], $authorizerAppId);
        } else {
            $data = [
                'reply_content' => '',
                'reply_type' => 'news',
            ];
        }
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/openkf/reply",
     *     summary="获取多客服回复配置",
     *     tags={"微信"},
     *     description="获取多客服回复配置（是否开启）",
     *     operationId="getOpenKfReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
     *        @SWG\Property(property="isOpenKfReply", type="boolean"),
     *     )))),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getOpenKfReply()
    {
        $transferReply = new Transfer();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');

        $status = $transferReply->getOpenKfReply($authorizerAppId);
        return $this->response->array(['isOpenKfReply' => $status]);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/openkf/reply",
     *     summary="设置多客服回复配置",
     *     tags={"微信"},
     *     description="设置多客服回复配置",
     *     operationId="setOpenKfReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="isOpenKfReply", in="query", description="是否开启多客服", required=true, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
     *        @SWG\Property(property="isOpenKfReply", type="boolean"),
     *     )))),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setOpenKfReply(Request $request)
    {
        $transferReply = new Transfer();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');

        $transferReply->setOpenKfReply($authorizerAppId, $request->input('isOpenKfReply', 'false'));
        return $this->response->array(['isOpenKfReply' => $request->input('isOpenKfReply', 'false')]);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/subscribe/reply",
     *     summary="设置被关注自动回复消息",
     *     tags={"微信"},
     *     description="设置被关注自动回复消息",
     *     operationId="setSubscribeReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="reply_type", in="query", description="回复消息类型  text文字 image图片 news图文 card卡券", type="string"),
     *     @SWG\Parameter( name="reply_content", in="query", description="回复消息内容 media_id 或者 card_id 或者配置的文本信息", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
               @SWG\Property(property="status", type="boolean")))),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function setSubscribeReply(Request $request)
    {
        $subscribeReply = new SubscribeReply();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $centent = $request->all('reply_type', 'reply_content');
        $subscribeReply->setSubscribeReplyContent($authorizerAppId, $centent);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/subscribe/reply",
     *     summary="获取被关注自动回复消息配置",
     *     tags={"微信"},
     *     description="获取被关注自动回复消息配置",
     *     operationId="getSubscribeReply",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema( @SWG\Property( property="data", type="array", @SWG\Items( type="object",
     *        @SWG\Property(property="keyword", type="string"),
     *        @SWG\Property(property="reply_type", type="string"),
     *        @SWG\Property(property="reply_content", type="string")
     *     )))),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getSubscribeReply()
    {
        $subscribeReply = new SubscribeReply();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');

        $data = $subscribeReply->getSubscribeReplyContent($authorizerAppId);
        if ($data['reply_content'] ?? []) {
            $messageService = new MessageService();
            $data['reply_content'] = $messageService->replySettingContent($data['reply_type'], $data['reply_content'], $authorizerAppId);
        } else {
            $data = [
                'reply_content' => '',
                'reply_type' => 'news',
            ];
        }
        return $this->response->array($data);
    }
}
