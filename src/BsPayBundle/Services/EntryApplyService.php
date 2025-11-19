<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace BsPayBundle\Services;

use BsPayBundle\Services\Request\Request;
use Dingo\Api\Exception\ResourceException;

use BsPayBundle\Entities\EntryApply;

/**
 * 用户进件申请
 */
class EntryApplyService
{
    /** @var \BsPayBundle\Repositories\EntryApplyRepository */
    public $entryApplyRepository;

    public function __construct($companyId = 0)
    {
        $this->entryApplyRepository = app('registry')->getManager('default')->getRepository(EntryApply::class);
    }

    

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entryApplyRepository->$method(...$parameters);
    }
}
