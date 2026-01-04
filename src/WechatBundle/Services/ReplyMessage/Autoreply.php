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

namespace WechatBundle\Services\ReplyMessage;

use Dingo\Api\Exception\StoreResourceFailedException;

class Autoreply
{
    /**
     * 应用自动回复规则.
     *
     * @return array
     */
    public function handle($message, $authorizerAppId)
    {
        $text = 'test text';
        if ($message['MsgType'] == 'text') {
            $text = $message['Content'];
        } elseif ($message['MsgType'] == 'voice' && $message['MsgType']['Recongnition']) {
            $text = $message['MsgType']['Recongnition'];
        }

        return $this->applyAutorReply($authorizerAppId, $text);
    }

    /**
     * 自动回复
     */
    private function applyAutorReply($authorizerAppId, $text)
    {
        $list = app('redis')->hgetall($this->genId($authorizerAppId));
        $current = [];
        foreach ($list as $ruleName => $rules) {
            $rules = json_decode($rules, true);
            foreach ($rules['keywords_rule'] as $rule) {
                if (
                    ($rule['reply_mode'] == 'equal' && $text === $rule['keyword'])
                    || ($rule['reply_mode'] == 'contain' && strstr($text, $rule['keyword']))
                ) {
                    $current = [
                        'type' => $rules['reply_type'],
                        'content' => $rules['reply_content']
                    ];
                    break;
                }
            }
        }
        return $current;
    }

    /**
     * 获取关键字自动回复规则
     */
    public function getAutorReplyRules($authorizerAppId)
    {
        $list = app('redis')->hgetall($this->genId($authorizerAppId));
        $result = [];
        if ($list) {
            foreach ($list as $ruleName => $rules) {
                $rules = json_decode($rules, true);
                $result[] = [
                    'rule_name' => $rules['rule_name'],
                    'keywords_rule' => $rules['keywords_rule'],
                    'reply_type' => $rules['reply_type'],
                    'reply_content' => $rules['reply_content'],
                    'isopen' => false,
                    'is_new' => false
                ];
            }
        }
        return $result;
    }

    public function deleteAutorReplyRules($authorizerAppId, $ruleName)
    {
        return app('redis')->hdel($this->genId($authorizerAppId), $ruleName);
    }

    /**
     * 判断规则名称是否存在
     */
    public function existsRuleName($authorizerAppId, $ruleName)
    {
        return app('redis')->hexists($this->genId($authorizerAppId), $ruleName);
    }

    /**
     *  新增自动回复规则
     */
    public function addAutorReplyRules($authorizerAppId, $ruleName, $rules)
    {
        if ($ruleName && $this->existsRuleName($authorizerAppId, $ruleName)) {
            throw new StoreResourceFailedException('当前规则已存在，请换一个规则名称');
        }

        return $this->storeRules($authorizerAppId, $ruleName, $rules);
    }

    private function storeRules($authorizerAppId, $ruleName, $rules)
    {
        if (!$rules['keywords_rule']
            || !in_array($rules['reply_type'], ['text', 'image', 'news', 'card'])
            || !$rules['reply_content']
            || !$rules['rule_name']
        ) {
            throw new StoreResourceFailedException('请填写必填参数');
        }

        $isKeyword = false;
        foreach ($rules['keywords_rule'] as $key => $rule) {
            if ($rule['keyword']) {
                if (!in_array($rule['reply_mode'], ['equal', 'contain'])) {
                    throw new StoreResourceFailedException('关键字匹配模式只支持equal或者contain');
                }
                $isKeyword = true;
            } else {
                unset($rules['keywords_rule'][$key]);
            }
        }

        if (!$isKeyword) {
            throw new StoreResourceFailedException('请填写关键字');
        }

        app('redis')->hset($this->genId($authorizerAppId), $ruleName, json_encode($rules));

        return true;
    }

    //更新
    public function updateAutorReplyRules($authorizerAppId, $ruleName, $rules)
    {
        if (!$this->existsRuleName($authorizerAppId, $ruleName)) {
            throw new StoreResourceFailedException('当前更新的规则不存在');
        }

        return $this->storeRules($authorizerAppId, $ruleName, $rules);
    }

    /**
     * undocumented function
     *
     * @return string
     */
    private function genId($authorizerAppId)
    {
        return 'autoreply:'. sha1($authorizerAppId.'keyword_autoreply_info');
    }
}
