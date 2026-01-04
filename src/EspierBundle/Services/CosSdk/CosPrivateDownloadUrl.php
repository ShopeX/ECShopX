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

namespace EspierBundle\Services\CosSdk;

use Carbon\Carbon;
use League\Flysystem\Plugin\AbstractPlugin;

class CosPrivateDownloadUrl extends AbstractPlugin
{
    /**
     * getTemporaryUrl.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'privateDownloadUrl';
    }

    /**
     * handle.
     *
     * @param       $path
     * @param       $expiration
     * @param array $options
     *
     * @return mixed
     */
    public function handle($path, $expiration = 3600, array $options = [])
    {
        $ts = time()+$expiration;
        $expiration = Carbon::createFromTimestamp($ts);
        return $this->filesystem->getAdapter()->getTemporaryUrl($path, $expiration, $options);
    }
}
