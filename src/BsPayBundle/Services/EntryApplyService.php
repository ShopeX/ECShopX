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
