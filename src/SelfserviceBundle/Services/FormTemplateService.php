<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\FormTemplate;

class FormTemplateService
{
    /** @var \SelfserviceBundle\Repositories\FormTemplateRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(FormTemplate::class);
    }

    public function saveData($params, $filter = [])
    {
        if ($filter) {
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
