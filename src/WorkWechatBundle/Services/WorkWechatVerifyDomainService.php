<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Services;

use WorkWechatBundle\Entities\WorkWechatVerifyDomainFile;
use WorkWechatBundle\Repositories\WorkWechatVerifyDomainFileRepository;

class WorkWechatVerifyDomainService
{
    /** @var WorkWechatVerifyDomainFileRepository $verifyFileRepo */
    private $verifyFileRepo;

    public function __construct()
    {
        $this->verifyFileRepo = app('registry')->getManager('default')->getRepository(WorkWechatVerifyDomainFile::class);
    }

    public function saveVerifyInfo($params)
    {
        $filter = ['name' => $params['name']];
        $verifyFile = $this->verifyFileRepo->getInfo($filter);

        $params['created'] = time();
        if ($verifyFile) {
            $result = $this->verifyFileRepo->updateOneBy($filter, $params);
        } else {
            $result = $this->verifyFileRepo->create($params);
        }

        return $result;
    }

    public function getVerifyInfoByName($name)
    {
        $filter = ['name' => $name];
        return $this->verifyFileRepo->getInfo($filter);
    }

    public function getVerifyInfoByCompanyId($company_id)
    {
        $filter = ['company_id' => $company_id];
        $result = $this->verifyFileRepo->getLists($filter, $cols = '*', $page = 1, $pageSize = 1, $orderBy = ['created' => 'DESC']);
        if ($result) {
            return $result[0];
        }
        return false;
    }
}
