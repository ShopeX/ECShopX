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

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */
namespace AdaPayBundle\Services;

use GuzzleHttp\Client;

class AdaPayRequests
{
    public function curl_request($url, $postFields = null, $headers = null, $is_json = false, $has_file = false)
    {
        $response = '';
        $statusCode = 0;
        try {
            $client = new Client([
                'headers' => $headers,
                'http_errors' => false,
                'timeout' => 30,
            ]);
            app('log')->info('请求地址：' . $url);
            if (is_array($postFields)) {
                if ($is_json) {
                    $resp = $client->request('POST', $url, ['json' => $postFields]);
                } else {
                    if ($has_file) {
                        $multipart = [];
                        foreach ($postFields as $k => $v) {
                            $multipart[] = [
                                'name' => $k,
                                'contents' => $v,
                            ];
                        }
                        //文件上传
                        $resp = $client->request('POST', $url, [
                            'multipart' => $multipart,
                        ]);
                        app('log')->info(' 文件上传 ');
                    } else {
                        $resp = $client->request('POST', $url, ['form_params' => $postFields]);
                    }
                }
            } else {
                $resp = $client->get($url);
            }
            $response = $resp->getBody()->getContents();
            $statusCode = $resp->getStatusCode();
            app('log')->info('curl返回参数:' . $response);
        } catch (\Throwable $throwable) {
            var_dump($throwable->getMessage());
            app('log')->error($throwable->getMessage());
        }

        return [$statusCode, $response];
    }
}
