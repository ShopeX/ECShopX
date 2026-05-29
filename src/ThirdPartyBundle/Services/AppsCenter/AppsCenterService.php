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

namespace ThirdPartyBundle\Services\AppsCenter;

use GuzzleHttp\Client;
use Dingo\Api\Exception\ResourceException;

class AppsCenterService
{
    public function fetchEmbedGoods($channel = null)
    {
        $channel = $channel ?: config('common.appcenter_channel', 'ecshopx');
        $baseUrl = rtrim(config('common.appcenter_base_url'), '/');
        $url = $baseUrl . '/appcenter/open/embed/' . rawurlencode($channel) . '/goods';

        $client = new Client(['timeout' => 15, 'http_errors' => false]);
        $response = $client->get($url);
        $body = json_decode((string) $response->getBody(), true);
        if (!is_array($body)) {
            throw new ResourceException('获取应用中心商品失败');
        }
        if (($body['status'] ?? '') !== 'succ') {
            throw new ResourceException($body['error'] ?: '获取应用中心商品失败');
        }

        return $body['data'] ?? [];
    }

    public function buildAppcenterUrl(array $params, $token)
    {
        $channel = $params['channel'];
        $baseUrl = rtrim(config('common.appcenter_base_url'), '/');
        $base = $baseUrl . '/appcenter/embed/' . rawurlencode($channel);

        $query = [
            'shopexid' => (string) $params['shopexid'],
            'sys_node_id' => (string) $params['sys_node_id'],
            'callback' => (string) $params['callback'],
            'embed' => '1',
            'nonce' => bin2hex(random_bytes(8)),
            'timestamp' => (string) time(),
        ];

        $query['sign'] = $this->signAppcenterParams($channel, $query, $token);

        return $base . '?' . http_build_query($query);
    }

    public function signAppcenterParams($channel, array $query, $token)
    {
        $signData = $query;
        $signData['channel'] = $channel;
        unset($signData['sign']);
        ksort($signData, SORT_STRING);

        $lines = [];
        foreach ($signData as $key => $value) {
            $lines[] = $key . '=' . rawurlencode((string) $value);
        }

        $canonicalString = implode("\n", $lines);

        return hash_hmac('sha256', $canonicalString, $token);
    }
}
