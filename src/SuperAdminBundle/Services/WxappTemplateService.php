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

namespace SuperAdminBundle\Services;

use SuperAdminBundle\Entities\WxappTemplate;

class WxappTemplateService
{
    /** @var resourcesRepository */
    private $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(WxappTemplate::class);
    }

    public function setDomain($data)
    {
        app('redis')->set('wxappTemplateRequestdomain', trim($data['requestdomain']));
        app('redis')->set('wxappTemplateWsrequestdomain', trim($data['wsrequestdomain']));
        app('redis')->set('wxappTemplateUploaddomain', trim($data['uploaddomain']));
        app('redis')->set('wxappTemplateDownloaddomain', trim($data['downloaddomain']));
        app('redis')->set('wxappTemplateWebViewdomain', trim($data['webviewdomain']));
        return true;
    }

    public function getDomain()
    {
        $data['requestdomain'] = trim(app('redis')->get('wxappTemplateRequestdomain'));
        $data['wsrequestdomain'] = trim(app('redis')->get('wxappTemplateWsrequestdomain'));
        $data['uploaddomain'] = trim(app('redis')->get('wxappTemplateUploaddomain'));
        $data['downloaddomain'] = trim(app('redis')->get('wxappTemplateDownloaddomain'));
        $data['webviewdomain'] = trim(app('redis')->get('wxappTemplateWebViewdomain'));
        return $data;
    }

    public function getDataList($filter = [], $page = 1, $pageSize = 100)
    {
        $result = [];
        $filter['is_disabled'] = false;
        $lists = $this->entityRepository->lists($filter, $page, $pageSize);

        $domainData = $this->getDomain();
        $domainArr = ['requestdomain', 'wsrequestdomain', 'uploaddomain', 'downloaddomain', 'webviewdomain'];
        foreach ($lists['list'] as $list) {
            foreach ($domainArr as $col) {
                $newDomainArr = [];
                $oldDomainArr = [];
                if ($domainData[$col]) {
                    $urlStr = str_replace(array("\r", "\n", "\r\n"), ' ', $domainData[$col]);
                    $newDomainArr = explode(' ', $urlStr);
                }
                if (isset($list['domain'][$col])) {
                    $urlStr = str_replace(array("\r", "\n", "\r\n"), ' ', $list['domain'][$col]);
                    $oldDomainArr = explode(' ', $urlStr);
                }
                $list['domain'][$col] = array_merge($oldDomainArr, $newDomainArr);
                $list['domain'][$col] = array_unique($list['domain'][$col]);
                $list['domain'][$col] = array_values($list['domain'][$col]);
            }

            $list['desc'] = $list['description'];

            $result[$list['key_name']] = $list;
        }
        return $result;
    }

    public function getInfo($filter)
    {
        $list = $this->entityRepository->getInfo($filter);
        if ($list) {
            $domainData = $this->getDomain();
            $domainArr = ['requestdomain', 'wsrequestdomain', 'uploaddomain', 'downloaddomain', 'webviewdomain'];
            foreach ($domainArr as $col) {
                $newDomainArr = [];
                $oldDomainArr = [];
                if ($domainData[$col]) {
                    $urlStr = str_replace(array("\r", "\n", "\r\n"), ' ', $domainData[$col]);
                    $newDomainArr = explode(' ', $urlStr);
                }
                if (isset($list['domain'][$col])) {
                    $urlStr = str_replace(array("\r", "\n", "\r\n"), ' ', $list['domain'][$col]);
                    $oldDomainArr = explode(' ', $urlStr);
                }
                $list['domain'][$col] = array_merge($oldDomainArr, $newDomainArr);
                $list['domain'][$col] = array_unique($list['domain'][$col]);
                $list['domain'][$col] = array_values($list['domain'][$col]);
            }
            $list['desc'] = $list['description'];
        }
        return $list;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
