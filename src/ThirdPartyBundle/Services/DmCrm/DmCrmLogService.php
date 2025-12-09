<?php

namespace ThirdPartyBundle\Services\DmCrm;

use ThirdPartyBundle\Entities\DmCrmLog;

class DmCrmLogService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(DmCrmLog::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

}
