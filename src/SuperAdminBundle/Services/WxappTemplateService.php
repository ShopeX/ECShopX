<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
